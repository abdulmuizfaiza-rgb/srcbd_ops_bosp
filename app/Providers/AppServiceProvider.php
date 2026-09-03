<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Superadmin: kelola Profil Sekolah & Pengguna, serta memantau Pendataan OPS/BOSP.
        // Admin OPS: hanya Pendataan OPS. Admin BOSP: hanya Pendataan BOSP.
        Gate::define('akses-profil-sekolah', fn (User $user) => $user->isSuperadmin());

        Gate::define('akses-pengguna', fn (User $user) => $user->isSuperadmin());

        Gate::define(
            'akses-pendataan-ops',
            fn (User $user) => $user->isSuperadmin() || $user->isAdminOps()
        );

        Gate::define(
            'akses-pendataan-bosp',
            fn (User $user) => $user->isSuperadmin() || $user->isAdminBosp()
        );
    }
}
