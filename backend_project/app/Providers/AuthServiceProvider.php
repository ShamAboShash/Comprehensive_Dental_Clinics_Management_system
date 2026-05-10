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
    }
}
