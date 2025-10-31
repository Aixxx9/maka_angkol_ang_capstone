<?php

namespace App\Providers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Vite;
use App\Models\School;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;


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
       Inertia::share('schools', function () {
            return School::select('id', 'name', 'slug', 'logo_path')->get()->map(function ($s) {
                $s->logo_path = $s->logo_path
                    ? Storage::url($s->logo_path)
                    : asset('images/default-logo.png');
                return $s;
            });
        });
        // Vite::prefetch(concurrency: 3);
    }
}
