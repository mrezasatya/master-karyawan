<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Staging/produksi wajib HTTPS (diatur lewat FORCE_HTTPS di .env).
        if (config('karyawan.force_https')) {
            URL::forceScheme('https');
        }

        Paginator::useTailwind();
    }
}
