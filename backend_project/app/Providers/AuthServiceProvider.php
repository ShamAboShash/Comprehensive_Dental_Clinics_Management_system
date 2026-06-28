<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     */
    protected $policies = [
        // You can add policies later
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // ================ Gates ================

        Gate::define('manage-employees', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('view-employees', function (User $user) {
            return $user->isAdmin();
            //  || $user->isDentist(); // Dentists might see staff list later
        });

        Gate::define('block-employee', function (User $user) {
            return $user->isAdmin();
        });


        Gate::define('view-medications', function (User $user) {
            return $user->isDentist();
        });

        Gate::define('view-specializations', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('view-roles', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('view-employees', function (User $user) {
            return $user->isAdmin() || $user->isSecretary();
        });

        Gate::define('view-patients', function (User $user) {
            return $user->isAdmin() || $user->isSecretary() ;
        });

         Gate::define('edit-dentist-profile', function (User $user) {
            return $user->isDentist();
        });

        Gate::define('edit-employee-profile', function (User $user) {
            return $user->isAdmin() ||$user->isWarehouseManager() || $user->isLabManager() || $user->isSecretary();
        });

        Gate::define('edit-patient-profile', function (User $user) {
            return $user->isPatient();
        });

        Gate::define('view-admin-profile', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('view-employee-profile', function (User $user) {
            return $user->isStaff();

        });

        Gate::define('view-patient-profile', function (User $user) {
            return $user->isPatient();
        });

        Gate::define('view-dentist-profile', function (User $user) {
            return $user->isDentist();
        });

        Gate::define('add-medical-info', function (User $user) {
            return $user->isDentist();
        });

        Gate::define('login-employee', function (User $user) {
            return $user->isStaff() ;
        });

        Gate::define('login-patient', function (User $user) {
                return $user->isPatient();
        });

        Gate::define('set-employee-password', function (User $user) {
                return $user->isStaff();
        });

        Gate::define('verify-employee-code', function (User $user) {
                return $user->isStaff();
        });

        Gate::define('send-employee-verification', function (User $user) {
                return $user->isStaff();
        });


        Gate::define('view-employee-details', function ($user, $employee) {
            return $user->isAdmin();
        });

        Gate::define('view-patient-profile', function ($user) {
            return $user->isAdmin() || $user->isDentist() ||  $user->isPatient() || $user->isSecretary();
        });

        Gate::define('show-technicians', function (User $user) {
            return $user->isAdmin() || $user->isLabManager();
        });

        Gate::define('is-lab-manager', function ($user) {
            return $user->isLabManager();
        });

        Gate::define('is-dentist', function ($user) {
            return $user->isDentist();
        });

        Gate::define('is-warehouse-manager', function ($user) {
            return $user->isWarehouseManager();
        });

     }
}
