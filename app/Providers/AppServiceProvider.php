<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('manage-users', fn (User $u) => in_array($u->role, ['CPD','Decanato']));
        try { DB::statement("SET search_path TO academia, public"); } catch (\Throwable $e) {}
    }
}
