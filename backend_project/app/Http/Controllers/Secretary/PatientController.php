<?php

namespace App\Http\Controllers\Secretary;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\StorePatientRequest;
use App\Http\Requests\Secretary\UpdatePatientRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\Secretary\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Dentist;

class PatientController extends Controller
{
    public function __construct()
    {
        Gate::authorize('is-secretary');
    }

    /**
     * 28. عرض كل المرضى
     * GET /secretary/showAllPatients
     */
    public function index(Request $request): JsonResponse
    {
        $query = Patient::with('user')
            ->whereHas('user', function ($q) use ($request) {
                if ($request->filled('search')) {
                    $search = $request->search;
                    $q->where(function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                           ->orWhere('phone', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            })
            ->orderByDesc('id');

        $patients = $query->get();

        $data = $patients->map(fn (Patient $p) => [
            'id'              => $p->id,
            'user_id'         => $p->user_id,
            'name'            => $p->user?->name,
            'email'           => $p->user?->email,          // may be null
            'phone'           => $p->user?->phone,
            'gender'          => $p->user?->gender,
            'date_of_birth'   => $p->user?->date_of_birth,
            'address'         => $p->user?->address,
            'is_active'       => $p->user?->is_active,
            'profile_picture' => $p->user?->profile_picture_url,
            'has_app_account' => !empty($p->user?->email) && !empty($p->user?->password),
        ]);

        return response()->json([
            'success' => true,
            'count'   => $data->count(),
            'data'    => $data,
        ]);
    }

    /**
     * 29. عرض المريض حسب id + الدفعات + العلاجات
     * GET /secretary/showPatient/{id}
     */
    public function show(int $id): JsonResponse
    {
        $patient = Patient::with([
            'user',
            'treatments.treatmentType',
            'treatments.dentist.employee.user',
            'allergies',
            'chronicDiseases',
        ])->find($id);

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'المريض غير موجود.',
            ], 404);
        }

        // Invoices + payments for this patient
        $invoices = Invoice::with(['payments', 'treatment.treatmentType'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($inv) => [
                'id'           => $inv->id,
                'treatment_id' => $inv->treatment_id,
                'treatment'    => $inv->treatment?->treatmentType?->name,
                'status'       => $inv->status,
                'total_amount' => (float) $inv->total_amount,
                'paid_amount'  => (float) $inv->paid_amount,
                'remaining'    => (float) $inv->total_amount - (float) $inv->paid_amount,
                'payments'     => $inv->payments->map(fn ($pay) => [
                    'id'             => $pay->id,
                    'amount'         => (float) $pay->amount,
                    'payment_method' => $pay->payment_method,
                    'created_at'     => $pay->created_at?->toDateTimeString(),
                ]),
                'created_at'   => $inv->created_at?->toDateTimeString(),
            ]);

        $treatments = $patient->treatments->map(fn ($t) => [
            'id'                   => $t->id,
            'treatment_type'       => $t->treatmentType?->name,
            'dentist'              => $t->dentist?->employee?->user?->name,
            'status'               => $t->status,
            'total_sessions_needed'=> $t->total_sessions_needed,
            'sessions_completed'   => $t->sessions_completed,
            'notes'                => $t->notes,
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'      => $patient->id,
                'user_id' => $patient->user_id,
                'user'    => [
                    'id'              => $patient->user?->id,
                    'name'            => $patient->user?->name,
                    'email'           => $patient->user?->email,
                    'phone'           => $patient->user?->phone,
                    'date_of_birth'   => $patient->user?->date_of_birth,
                    'gender'          => $patient->user?->gender,
                    'address'         => $patient->user?->address,
                    'profile_picture' => $patient->user?->profile_picture_url,
                    'is_active'       => $patient->user?->is_active,
                ],
                'emergency_contact_name'     => $patient->emergency_contact_name,
                'emergency_contact_relation' => $patient->emergency_contact_relation,
                'emergency_contact_phone'    => $patient->emergency_contact_phone,
                'is_pregnant'                => $patient->is_pregnant,
                'is_breastfeeding'           => $patient->is_breastfeeding,
                'is_smoker'                  => $patient->is_smoker,
                'drinks_alcohol_frequently'  => $patient->drinks_alcohol_frequently,
                'teeth_cleaning_frequency'   => $patient->teeth_cleaning_frequency,
                'allergies'                  => $patient->allergies,
                'chronic_diseases'           => $patient->chronicDiseases,
                'treatments'                 => $treatments,
                'invoices'                   => $invoices,
                'has_app_account'            => !empty($patient->user?->email) && !empty($patient->user?->password),
            ],
        ]);
    }

    /**
     * 30. إضافة مريض (email اختياري — بدون حساب تطبيق)
     * POST /secretary/addPatient
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Default profile picture by gender
            $profilePicturePath = null;
            if ($request->hasFile('profile_picture')) {
                $image = $request->file('profile_picture');
                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $profilePicturePath = $image->storeAs('profile_pictures', $filename, 'public');
            } elseif ($request->gender == 1) {
                $profilePicturePath = 'profile_pictures/male.png';
            } elseif ($request->gender == 2) {
                $profilePicturePath = 'profile_pictures/female.png';
            }

            $user = User::create([
                'name'            => $request->name,
                'email'           => $request->email,          // can be null
                'phone'           => $request->phone,
                'date_of_birth'   => $request->date_of_birth,
                'gender'          => $request->gender,
                'address'         => $request->address,
                'role'            => UserRole::PATIENT,
                'password'        => null,                     // no app login
                'is_active'       => true,                     // usable in clinic
                'profile_picture' => $profilePicturePath,
            ]);

            $patient = Patient::create([
                'user_id'                    => $user->id,
                'emergency_contact_name'     => $request->emergency_contact_name,
                'emergency_contact_relation' => $request->emergency_contact_relation,
                'emergency_contact_phone'    => $request->emergency_contact_phone,
                'is_pregnant'                => $request->boolean('is_pregnant'),
                'is_breastfeeding'           => $request->boolean('is_breastfeeding'),
                'is_smoker'                  => $request->boolean('is_smoker'),
                'drinks_alcohol_frequently'  => $request->boolean('drinks_alcohol_frequently'),
                'teeth_cleaning_frequency'   => $request->teeth_cleaning_frequency,
            ]);

            DB::commit();

            $patient->load('user');

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المريض بنجاح.',
                'data'    => [
                    'id'              => $patient->id,
                    'user_id'         => $user->id,
                    'name'            => $user->name,
                    'email'           => $user->email,
                    'phone'           => $user->phone,
                    'gender'          => $user->gender,
                    'date_of_birth'   => $user->date_of_birth,
                    'address'         => $user->address,
                    'is_active'       => $user->is_active,
                    'has_app_account' => false,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة المريض.',
            ], 500);
        }
    }

    /**
     * 31. تعديل مريض (معلومات أساسية فقط)
     * POST /secretary/updatePatient/{id}
     */
    public function update(UpdatePatientRequest $request, int $id): JsonResponse
    {
        $patient = Patient::with('user')->find($id);

        if (!$patient || !$patient->user) {
            return response()->json([
                'success' => false,
                'message' => 'المريض غير موجود.',
            ], 404);
        }

        DB::beginTransaction();

        try {
            $userData = array_filter([
                'name'          => $request->name,
                'email'         => $request->email,       // can set/clear
                'phone'         => $request->phone,
                'date_of_birth' => $request->date_of_birth,
                'gender'        => $request->gender,
                'address'       => $request->address,
            ], fn ($v) => !is_null($v));

            // Allow explicitly clearing email
            if ($request->has('email') && $request->email === null) {
                $userData['email'] = null;
            }

            if ($request->hasFile('profile_picture')) {
                $image = $request->file('profile_picture');
                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $userData['profile_picture'] = $image->storeAs('profile_pictures', $filename, 'public');
            }

            if (!empty($userData)) {
                $patient->user->update($userData);
            }

            $patientData = array_filter([
                'emergency_contact_name'     => $request->emergency_contact_name,
                'emergency_contact_relation' => $request->emergency_contact_relation,
                'emergency_contact_phone'    => $request->emergency_contact_phone,
                'teeth_cleaning_frequency'   => $request->teeth_cleaning_frequency,
            ], fn ($v) => !is_null($v));

            foreach (['is_pregnant', 'is_breastfeeding', 'is_smoker', 'drinks_alcohol_frequently'] as $boolField) {
                if ($request->has($boolField)) {
                    $patientData[$boolField] = $request->boolean($boolField);
                }
            }

            if (!empty($patientData)) {
                $patient->update($patientData);
            }

            DB::commit();

            $patient->load('user');

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات المريض بنجاح.',
                'data'    => [
                    'id'      => $patient->id,
                    'user_id' => $patient->user_id,
                    'name'    => $patient->user->name,
                    'email'   => $patient->user->email,
                    'phone'   => $patient->user->phone,
                    'gender'  => $patient->user->gender,
                    'address' => $patient->user->address,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المريض.',
            ], 500);
        }
    }


    /**
 * 32. إضافة دفعة مالية
 * POST /secretary/addPayment
 *
 * Body: invoice_id, amount, payment_method (cash|card)
 */
    public function addPayment(StorePaymentRequest $request): JsonResponse
    {
        $invoice = Invoice::with(['patient.user', 'treatment.treatmentType', 'payments'])
            ->find($request->invoice_id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'الفاتورة غير موجودة.',
            ], 404);
        }

        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'الفاتورة مدفوعة بالكامل مسبقاً.',
            ], 422);
        }

        $remaining = (float) $invoice->total_amount - (float) $invoice->paid_amount;

        if ($request->amount > $remaining + 0.001) {
            return response()->json([
                'success' => false,
                'message' => "المبلغ أكبر من المتبقي ({$remaining}).",
            ], 422);
        }

        DB::beginTransaction();

        try {
            $payment = Payment::create([
                'invoice_id'     => $invoice->id,
                'received_by'    => $request->user()->id,
                'amount'         => $request->amount,
                'payment_method' => $request->payment_method,
            ]);

            $invoice->paid_amount = (float) $invoice->paid_amount + (float) $request->amount;

            $invoice->status = match (true) {
                $invoice->paid_amount >= (float) $invoice->total_amount && (float) $invoice->total_amount > 0 => 'paid',
                $invoice->paid_amount > 0 => 'partial',
                default => 'unpaid',
            };

            $invoice->save();

            DB::commit();

            $invoice->load(['patient.user', 'treatment.treatmentType', 'payments']);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدفعة بنجاح.',
                'data'    => [
                    'payment' => [
                        'id'             => $payment->id,
                        'amount'         => (float) $payment->amount,
                        'payment_method' => $payment->payment_method,
                        'received_by'    => $request->user()->name,
                        'paid_at'        => $payment->created_at?->toDateTimeString(),
                    ],
                    'invoice' => [
                        'id'           => $invoice->id,
                        'patient'      => $invoice->patient?->user?->name,
                        'treatment'    => $invoice->treatment?->treatmentType?->name,
                        'status'       => $invoice->status,
                        'total_amount' => (float) $invoice->total_amount,
                        'paid_amount'  => (float) $invoice->paid_amount,
                        'remaining'    => (float) $invoice->total_amount - (float) $invoice->paid_amount,
                    ],
                    // بيانات الإيصال للطباعة
                    'receipt' => [
                        'receipt_no'     => 'PAY-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                        'patient_name'   => $invoice->patient?->user?->name,
                        'patient_phone'  => $invoice->patient?->user?->phone,
                        'treatment'      => $invoice->treatment?->treatmentType?->name,
                        'amount'         => (float) $payment->amount,
                        'payment_method' => $payment->payment_method === 'cash' ? 'نقداً' : 'بطاقة',
                        'received_by'    => $request->user()->name,
                        'date'           => $payment->created_at?->toDateTimeString(),
                        'invoice_status' => $invoice->status,
                        'remaining'      => (float) $invoice->total_amount - (float) $invoice->paid_amount,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدفعة.',
            ], 500);
        }
    }

    /**
     * عرض فواتير مريض (مفيد قبل تسجيل دفعة)
     * GET /secretary/patientInvoices/{patientId}
     */
    public function patientInvoices(int $patientId): JsonResponse
    {
        $patient = Patient::with('user')->find($patientId);

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'المريض غير موجود.',
            ], 404);
        }

        $invoices = Invoice::with(['treatment.treatmentType', 'payments'])
            ->where('patient_id', $patientId)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($inv) => [
                'id'           => $inv->id,
                'treatment_id' => $inv->treatment_id,
                'treatment'    => $inv->treatment?->treatmentType?->name,
                'status'       => $inv->status,
                'total_amount' => (float) $inv->total_amount,
                'paid_amount'  => (float) $inv->paid_amount,
                'remaining'    => (float) $inv->total_amount - (float) $inv->paid_amount,
                'payments'     => $inv->payments->map(fn ($p) => [
                    'id'             => $p->id,
                    'amount'         => (float) $p->amount,
                    'payment_method' => $p->payment_method,
                    'paid_at'        => $p->created_at?->toDateTimeString(),
                ]),
                'created_at'   => $inv->created_at?->toDateTimeString(),
            ]);

        return response()->json([
            'success' => true,
            'patient' => [
                'id'    => $patient->id,
                'name'  => $patient->user?->name,
                'phone' => $patient->user?->phone,
            ],
            'count'   => $invoices->count(),
            'data'    => $invoices,
        ]);
    }

    /**
     * عرض فاتورة محددة + دفعاتها
     * GET /secretary/showInvoice/{invoiceId}
     */
    public function showInvoice(int $invoiceId): JsonResponse
    {
        $invoice = Invoice::with([
            'patient.user',
            'treatment.treatmentType',
            'items',
            'payments.receivedBy',
        ])->find($invoiceId);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'الفاتورة غير موجودة.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => $invoice->id,
                'patient'      => [
                    'id'    => $invoice->patient_id,
                    'name'  => $invoice->patient?->user?->name,
                    'phone' => $invoice->patient?->user?->phone,
                ],
                'treatment'    => $invoice->treatment?->treatmentType?->name,
                'status'       => $invoice->status,
                'total_amount' => (float) $invoice->total_amount,
                'paid_amount'  => (float) $invoice->paid_amount,
                'remaining'    => (float) $invoice->total_amount - (float) $invoice->paid_amount,
                'items'        => $invoice->items->map(fn ($item) => [
                    'id'          => $item->id,
                    'description' => $item->description,
                    'unit_price'  => (float) $item->unit_price,
                ]),
                'payments'     => $invoice->payments->map(fn ($p) => [
                    'id'             => $p->id,
                    'amount'         => (float) $p->amount,
                    'payment_method' => $p->payment_method,
                    'received_by'    => $p->receivedBy?->name,
                    'paid_at'        => $p->created_at?->toDateTimeString(),
                ]),
            ],
        ]);
    }

    public function indexDentist()
    {
        $dentists = Dentist::with([
            'employee.user',
            'specialization.appointmentTypes',
            'workSchedules',
            'treatments.treatmentType',
        ])
            ->whereHas('employee.user', fn ($q) => $q->where('is_active', true))
            ->orderBy('id')
            ->get();

        $data = $dentists->map(function (Dentist $d) {
            // Distinct treatment types this dentist has actually performed
            $treatmentTypes = $d->treatments
                ->pluck('treatmentType')
                ->filter()
                ->unique('id')
                ->values()
                ->map(fn ($t) => [
                    'id'      => $t->id,
                    'name'    => $t->name,
                    'name_en' => $t->name_en,
                ]);

            // Appointment types linked to their specialization
            // $appointmentTypes = $d->specialization?->appointmentTypes
            //     ->map(fn ($a) => [
            //         'id'      => $a->id,
            //         'name'    => $a->name,
            //         'name_en' => $a->name_en,
            //     ]) ?? collect();

            return [
                'id'              => $d->id,
                'name'            => $d->employee?->user?->name,
                'phone'           => $d->employee?->user?->phone,
                'profile_picture' => $d->employee?->user?->profile_picture_url,
                'specialization'  => $d->specialization ? [
                    'id'      => $d->specialization->id,
                    'name'    => $d->specialization->name,
                    'name_en' => $d->specialization->name_en,
                ] : null,
                'average_rating'  => (float) $d->average_rating,
                // 'years_of_experience' => $d->years_of_experience,
                // 'bio'             => $d->bio,
                'treatment_types' => $treatmentTypes,
                // 'appointment_types' => $appointmentTypes,
                'work_schedule'   => $d->workSchedules
                    ->sortBy(fn ($s) => array_search($s->day_of_week, [
                        'saturday', 'sunday', 'monday', 'tuesday',
                        'wednesday', 'thursday', 'friday',
                    ]))
                    ->values()
                    ->map(fn ($s) => [
                        'id'          => $s->id,
                        'day_of_week' => $s->day_of_week,
                        'start_time'  => $s->start_time,
                        'end_time'    => $s->end_time,
                    ]),
            ];
        });

        return response()->json([
            'success' => true,
            'count'   => $data->count(),
            'data'    => $data,
        ]);
    }
}
