<?php

namespace Database\Seeders;

use App\Models\Complaint;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class ComplaintSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::with('user')->get();

        if ($patients->isEmpty()) {
            $this->command->warn('لا يوجد مرضى في قاعدة البيانات، يرجى تشغيل PatientSeeder أولاً.');
            return;
        }

        $sampleComplaints = [
            [
                'title'       => 'تأخر الموعد',
                'description' => 'انتظرت أكثر من ساعة عن الموعد المحدد دون أي تنبيه مسبق.',
                'status'      => 'pending',
                'priority'    => 'medium',
            ],
            [
                'title'       => 'سوء تعامل من الاستقبال',
                'description' => 'واجهت أسلوب تعامل غير لائق من موظف الاستقبال عند الحجز.',
                'status'      => 'in_review',
                'priority'    => 'high',
            ],
            [
                'title'       => 'خطأ في الفاتورة',
                'description' => 'المبلغ المطلوب في الفاتورة لا يطابق ما تم الاتفاق عليه مع الطبيب.',
                'status'      => 'resolved',
                'priority'    => 'high',
            ],
            [
                'title'       => 'استفسار عن العلاج',
                'description' => 'أرغب بمعرفة تفاصيل إضافية حول خطة علاج تقويم الأسنان الخاصة بي.',
                'status'      => 'pending',
                'priority'    => 'low',
            ],
            [
                'title'       => 'ملاحظة على النظافة',
                'description' => 'لاحظت أن غرفة الانتظار تحتاج إلى تنظيف بشكل أفضل.',
                'status'      => 'rejected',
                'priority'    => 'low',
            ],
        ];

        foreach ($sampleComplaints as $index => $data) {
            $patient = $patients[$index % $patients->count()];

            Complaint::create([
                'patient_id'     => $patient->id,
                'submitted_by'   => $patient->user->id,
                'title'          => $data['title'],
                'description'    => $data['description'],
                'status'         => $data['status'],
                'priority'       => $data['priority'],
                'phone_number'   => $patient->user->phone ?? '0999999999',
                'admin_response' => $data['status'] === 'resolved'
                    ? 'تم التواصل مع المريض ومعالجة الملاحظة.'
                    : null,
            ]);
        }
    }
}
