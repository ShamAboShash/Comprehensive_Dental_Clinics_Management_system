<?php

use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\Auth\PatientAuthController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Dentist\DentistProfileController;
use App\Http\Controllers\Employee\EmployeeProfileController;
use App\Http\Controllers\Patient\PatientProfileController;
use App\Http\Controllers\Generals\ShowThingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ====================== SHARED-APIs ======================

Route::middleware(['auth:sanctum', 'active'])->group(function () {

    Route::get('/medications', [ShowThingsController::class, 'medications']);
    Route::get('/specializations', [ShowThingsController::class, 'specializations']);
    Route::get('/roles', [ShowThingsController::class, 'roles']);
    Route::get('/showAllEmployees', [EmployeeProfileController::class, 'showAllEmployees']);
    Route::get('/showAllPatients', [PatientProfileController::class, 'showAllPatients']);


});

// ====================== EMPLOYEE ======================

Route::prefix('employee')->group(function () {

    Route::post('/login', [EmployeeAuthController::class, 'login']);
    Route::post('/sendVerification', [EmployeeAuthController::class, 'sendVerification']);
    Route::post('/verifyCode', [EmployeeAuthController::class, 'verifyCode']);
    Route::post('/setPassword', [EmployeeAuthController::class, 'setPassword']);

    Route::middleware(['auth:sanctum', 'active'])->group(function () {

        Route::post('/logout', [EmployeeAuthController::class, 'logout']);
        Route::get('/showProfile', [EmployeeProfileController::class, 'showProfile']);
        Route::post('/editProfile', [EmployeeProfileController::class, 'editProfile']);



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
        Route::get('showProfile', [PatientProfileController::class, 'showProfile']);
        Route::post('/editProfile', [PatientProfileController::class, 'editProfile']);


    });

});

// ====================== SECRETARY ======================



// ====================== ADMIN ======================

Route::prefix('admin')->middleware(['auth:sanctum', 'active'])->group(function () {

    Route::get('/showProfile', [AdminProfileController::class, 'showProfile']);
    Route::post('/createEmployee', [EmployeeController::class, 'createEmployee']);
    Route::put('/updateEmployee/{employee}', [EmployeeController::class, 'updateEmployee']);
    Route::patch('/toggleUser/{user}', [EmployeeController::class, 'toggleUser']);

});

// ====================== DENTIST ======================

Route::prefix('dentist')->middleware(['auth:sanctum', 'active'])->group(function () {

    Route::get('/showProfile', [DentistProfileController::class, 'showProfile']);
    Route::post('/editProfile', [DentistProfileController::class, 'editProfile']);
    Route::post('/addMedicalInfo/{patientId}', [DentistProfileController::class, 'addMedicalInfo']);


});

// ====================== WAREHOUSE MANAGER ======================



// ====================== LAB MANAGER ======================

