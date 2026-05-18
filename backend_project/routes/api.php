<?php

use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\Auth\PatientAuthController;
use App\Http\Controllers\Patient\PatientProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/test-session', function () {
    session(['test_key' => 'Hello Database Session']);
    return [
        'session_id' => session()->getId(),
        'saved' => session('test_key')
    ];
});

// ====================== EMPLOYEE ======================

Route::prefix('employee')->group(function () {

    Route::post('/login', [EmployeeAuthController::class, 'login']);
    Route::post('/sendVerification', [EmployeeAuthController::class, 'sendVerification']);
    Route::post('/setPassword', [EmployeeAuthController::class, 'setPassword']);

    Route::middleware(['auth:sanctum', 'active'])->group(function () {

        Route::post('/logout', [EmployeeAuthController::class, 'logout']);


    });
});

// ====================== PATIENT ======================

Route::prefix('patient')->group(function () {

    Route::post('/register', [PatientAuthController::class, 'register']);
    Route::post('/login', [PatientAuthController::class, 'login']);
    Route::post('/sendPatientVerificationCode', [PatientAuthController::class, 'sendPatientVerificationCode']);
    Route::post('/verifyEmail', [PatientAuthController::class, 'verifyEmail']);

    Route::middleware(['auth:sanctum', 'active'])->group(function () {

        Route::post('/logout', [PatientAuthController::class, 'logout']);
        Route::post('showProfile', [PatientProfileController::class, 'showProfile']);




    });

});

// ====================== SECRETARY ======================



// ====================== ADMIN ======================



// ====================== DENTIST ======================



// ====================== WAREHOUSE MANAGER ======================



// ====================== LAB MANAGER ======================

