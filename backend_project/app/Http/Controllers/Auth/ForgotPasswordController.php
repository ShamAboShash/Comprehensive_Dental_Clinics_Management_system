<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeActivateRequest;
use App\Http\Requests\EmployeeVerifyCodeRequest;
use App\Http\Requests\EmployeeSetPasswordRequest;
use App\Mail\VerificationMail;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    // Step 1 — send verification code
    public function sendCode(EmployeeActivateRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        $existing = VerificationCode::where('email', $user->email)->first();

        $currentAttempts = $existing ? $existing->attempts : 0;
        $newAttempts     = $currentAttempts + 1;

        if ($newAttempts > 3) {
            return response()->json([
                'message' => 'You have reached the maximum number of attempts. Please try again tomorrow.'
            ], 429);
        }

        $code = rand(100000, 999999);

        VerificationCode::updateOrCreate(
            ['email'      => $user->email],
            [
                'code'      => $code,
                'expires_at'=> now()->addMinutes(5),
                'attempts'  => $newAttempts,
            ]
        );

        Mail::to($user->email)->send(new VerificationMail($user, $code));

        return response()->json([
            'message' => 'Verification code has been sent to your email. Code is valid for 2 minutes.'
        ]);
    }

    // Step 2 — verify the code
    public function verifyCode(EmployeeVerifyCodeRequest  $request)
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

        // mark email as verified so resetPassword knows verifyCode was called
        $user->email_verified_at = now();
        $user->save();

        return response()->json([
            'message' => 'Verification code is valid. You may now reset your password.'
        ]);
    }

    // Step 3 — reset the password
    public function resetPassword(EmployeeSetPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        // make sure the code record still exists (not already used)
        $verification = VerificationCode::where('email', $user->email)->first();

        if (!$verification) {
            return response()->json([
                'message' => 'Password has already been reset or session expired. Please request a new code.'
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // delete the code so it can't be reused
        VerificationCode::where('email', $user->email)->delete();

        // revoke all tokens to force re-login
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password reset successfully. Please login with your new password.'
        ]);
    }
}
