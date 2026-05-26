<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\PatientRegisterRequest;
use App\Http\Requests\PatientActivateRequest;
use App\Http\Requests\PatientVerifyEmailRequest;
use App\Http\Requests\LoginRequest;
use App\Mail\VerificationMail;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientChronicDisease;
use App\Models\PatientMedication;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class PatientAuthController extends Controller
{
    /**
     * Register New Patient
     */
    public function register(PatientRegisterRequest $request)
    {

        $user = User::where('email', $request->email)->first();

        if ($user) {
            return response()->json([
                'message' => 'An account with this email already exists.'
            ], 403);
        }

        // 1. Create User
            $userData = [
            'name'          => $request->name,
            'email'         => $request->email,
            'phone'         => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'gender'        => $request->gender,
            'address'       => $request->address,
            'role'          => UserRole::PATIENT,
            'password'      => Hash::make($request->password),
            'is_active'     => false,
        ];

        // Handle Profile Picture Upload
        if ($request->hasFile('profile_picture')) {
            $image = $request->file('profile_picture');
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('profile_pictures', $filename, 'public');
            $userData['profile_picture'] = $path;
        }

        $user = User::create($userData);

        // 2. Create Patient
        $patient = Patient::create([
            'user_id'                     => $user->id,
            'emergency_contact_name'      => $request->emergency_contact_name,
            'emergency_contact_relation'  => $request->emergency_contact_relation,
            'emergency_contact_phone'     => $request->emergency_contact_phone,
            'is_pregnant'                 => $request->is_pregnant ?? false,
            'is_breastfeeding'            => $request->is_breastfeeding ?? false,
            'is_smoker'                   => $request->is_smoker ?? false,
            'drinks_alcohol_frequently'   => $request->drinks_alcohol_frequently ?? false,
            'teeth_cleaning_frequency'    => $request->teeth_cleaning_frequency,
        ]);

        // 3. Store Allergies
        if ($request->has('allergies') && is_array($request->allergies)) {
            foreach ($request->allergies as $allergy) {
                PatientAllergy::create([
                    'patient_id'   => $patient->id,
                    'allergy_name' => $allergy['allergy_name'] ?? '',
                ]);
            }
        }

        // 4. Store Chronic Diseases
        if ($request->has('chronic_diseases') && is_array($request->chronic_diseases)) {
            foreach ($request->chronic_diseases as $disease) {
                PatientChronicDisease::create([
                    'patient_id'   => $patient->id,
                    'category'     => $disease['category'] ?? null,
                    'problem_name' => $disease['problem_name'] ?? '',
                    'details'      => $disease['details'] ?? null,
                    'severity'     => $disease['severity'] ?? null,
                ]);
            }
        }

        // 5. Store Medications
        if ($request->has('medications') && is_array($request->medications)) {
            foreach ($request->medications as $med) {
                PatientMedication::create([
                    'patient_id'   => $patient->id,
                    'medication_id'=> $med['medication_id'] ?? null,
                    'dosage'       => $med['dosage'] ?? null,
                    'frequency'    => $med['frequency'] ?? null,
                    'notes'        => $med['notes'] ?? null,
                ]);
            }
        }

        $this->sendPatientVerificationCode($request);
        return response()->json([
            'message' => 'Patient registered successfully. Please check your email to verify your account.',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role->value,
            ],
        ], 201);
    }



    public function sendPatientVerificationCode(Request $request)
{
    // Fetch the user first
    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'No account found with this email.'
        ], 404);
    }

    if ($user->is_active) {
        return response()->json([
            'message' => 'This account is already verified and active.'
        ], 422);
    }

    $existing = VerificationCode::where('email', $user->email)->first();

    $currentAttempts = $existing ? $existing->attempts : 0;
    $newAttempts = $currentAttempts + 1;

    if ($newAttempts > 3) {
        return response()->json([
            'message' => 'You have reached the maximum number of verification attempts for today. Please try again tomorrow.'
        ], 429);
    }

    // Generate code
    $code = rand(100000, 999999);

    // Save / Update verification code
    VerificationCode::updateOrCreate(
        ['email' => $user->email],
        [
            'code'       => $code,
            'expires_at' => now()->addMinutes(2),   // Better to give more time
            'attempts'   => $newAttempts,
        ]
    );

    // Send email - Pass the $user object, not string
    Mail::to($user->email)->send(new VerificationMail($user, $code));

    return response()->json([
        'message' => 'Verification code has been sent to your email. Code is valid for 2 minutes.'
    ]);
}



    public function verifyEmail(PatientVerifyEmailRequest $request)
{
    $user = User::where('email', $request->email)->first();

    $verification = VerificationCode::where('email', $user->email)
        ->where('code', $request->verification_code)
        ->where('expires_at', '>', now())
        ->first();

    if (!$verification) {
        return response()->json([
            'message' => 'Invalid or expired verification code.'
        ], 422);
    }

    // Activate account
    $user->update([
        'is_active'         => true,
        'email_verified_at' => now(),
    ]);

    // Clean up code
    VerificationCode::where('email', $user->email)->delete();

    $user->tokens()->delete(); // revoke old tokens
    $newToken = $user->createToken('patient-auth-token')->plainTextToken;

    return response()->json([
        'message' => 'you registered successfully.',
        'user'    => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role->value,
        ],
        'token'       => $newToken,
        'token_type'  => 'Bearer',
    ]);

}


    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        if (!Gate::allows('login-patient')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول.'
            ], 403);
        }

        $user->tokens()->delete();

        $token = $user->createToken('patient-auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role->value,
            ],
            'token'       => $token,
            'token_type'  => 'Bearer',
        ]);
    }


public function logout(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'message' => 'Unauthenticated. Please provide a valid token.'
        ], 401);
    }

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.'
        ]);

}
    // public function logout(Request $request)
    // {
    //     $request->user()->currentAccessToken()->delete();

    //     return response()->json([
    //         'message' => 'Logged out successfully'
    //     ]);
    // }



/**
 * Step 2: Set Password (Requires Authentication Token)
 */
// public function setPassword(PatientSetPasswordRequest $request)
// {
//     $user = User::where('email', $request->email)->first();

//     // if ($user->password !== null) {
//     //     return response()->json([
//     //         'message' => 'Password is already set for this account.'
//     //     ], 422);
//     // }

//     $user->update([
//         'password' => Hash::make($request->password),
//     ]);

//     // Optional: Regenerate token after password set
//     $user->tokens()->delete(); // revoke old tokens
//     $newToken = $user->createToken('patient-auth-token')->plainTextToken;

//     return response()->json([
//         'message' => 'Password set successfully.',
//         'user'    => [
//             'id'    => $user->id,
//             'name'  => $user->name,
//             'email' => $user->email,
//             'role'  => $user->role->label() ?? 'patient',
//         ],
//         'token'       => $newToken,
//         'token_type'  => 'Bearer',
//     ]);
// }


    //     public function verifyEmail(PatientActivateRequest $request)
    // {
    //     $user = User::where('email', $request->email)->first();

    //     if (!$user) {
    //         return response()->json([
    //             'message' => 'No account found with this email.'
    //         ], 404);
    //     }

    //     if ($user->is_active) {
    //         return response()->json([
    //             'message' => 'This account is already verified and active.'
    //         ], 422);
    //     }

    //     // Find valid verification code
    //     $verification = VerificationCode::where('email', $user->email)
    //         ->where('code', $request->verification_code)
    //         ->where('expires_at', '>', now())
    //         ->first();

    //     if (!$verification) {
    //         return response()->json([
    //             'message' => 'Invalid or expired verification code.'
    //         ], 422);
    //     }

    //     // Activate the patient account
    //     $user->update([
    //         'is_active'         => true,
    //         'email_verified_at' => now(),
    //     ]);

    //     // Delete used verification code
    //     VerificationCode::where('email', $user->email)->delete();

    //     // ====================== LOGIN WITH SANCTUM TOKEN ======================
    //     $token = $user->createToken('patient-auth-token')->plainTextToken;

    //     return response()->json([
    //         'message' => 'Email verified successfully. Your account is now active.',
    //         'user'    => [
    //             'id'                => $user->id,
    //             'name'              => $user->name,
    //             'email'             => $user->email,
    //             'phone'             => $user->phone,
    //             'role'              => $user->role->label() ?? 'patient',

    //         ],
    //         'token'       => $token,
    //         'token_type'  => 'Bearer',
    //         'expires_in'  => 30 . ' days',
    //     ]);
    // }










}
