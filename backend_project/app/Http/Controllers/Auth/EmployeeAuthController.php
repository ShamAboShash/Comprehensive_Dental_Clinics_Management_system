<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeActivateRequest;
use App\Http\Requests\EmployeeSetPasswordRequest;
use App\Mail\VerificationMail;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LoginRequest;

use Illuminate\Validation\ValidationException;

class EmployeeAuthController extends Controller
{

    public function sendVerification(EmployeeActivateRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account found with this email.'
            ], 404);
        }

        if ($user->password !== null) {
            return response()->json([
                'message' => 'This account is already activated.'
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

        // Save verification code
        VerificationCode::updateOrCreate([
            'email'       => $user->email],[
            'code'        => $code,
            'expires_at'  => now()->addMinutes(2),
            'attempts'    => $newAttempts,
        ]);

        // Send email
        Mail::to($user->email)->send(new VerificationMail($user, $code));

        return response()->json([
            'message' => 'Verification code has been sent to your email. Code is valid for 2 minutes.'
        ]);
    }


    public function setPassword(EmployeeSetPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || $user->password !== null) {
            return response()->json([
                'message' => 'This account is already activated.'], 422);
        }

        // Find valid code
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
            'password'  => Hash::make($request->password),
            'is_active' => true,
            'email_verified_at'  => now()
        ]);

        // Delete used code
        VerificationCode::where('email', $user->email)->delete();

        // This creates the session
       $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('employee-auth-token', ['*'])->plainTextToken;

        return response()->json([
        'message' => 'Account activated and logged in successfully.',
        'user'    => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role->label() ,
            'token' => $token
            ]
        ]);

    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Your account is not active. Please contact administration.'
            ], 403);
        }


        // Prevent patients from logging in here
        if ($user->role === 'patient') {
            return response()->json([
                'message' => 'Patients should use the mobile app.'
            ], 403);
        }

        $user->tokens()->delete();

        $token = $user->createToken('employee-auth-token', ['*'])->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role->label() ,
                'is_active'  => $user->is_active,
                'token'   => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }


    public function logout(Request $request)
    {

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);

    }

}
