<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;   // ✅ FACade (NO el contrato)
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('manage-users', function (User $user) {
            return in_array($user->role, ['CPD', 'Decanato']);
        });
    }
}
