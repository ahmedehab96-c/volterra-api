<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // Saving builds and sending requests: generous for people, tight for scripts
        RateLimiter::for('public-writes', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
        // Live price updates fire on every option change
        RateLimiter::for('pricing', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        // Per account and per address, against password guessing
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
