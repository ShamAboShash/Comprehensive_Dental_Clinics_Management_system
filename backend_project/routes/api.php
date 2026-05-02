<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\EmployeeController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('admin')->name('admin.')->group(function () {

        // Only Admin can access these routes
        Route::apiResource('employees', EmployeeController::class)
             ->only(['store', 'index', 'show', 'update', 'destroy']);

    });


    
});
