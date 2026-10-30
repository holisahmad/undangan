<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Password::defaults(static function (): Password {
            $rule = Password::min(8);

            return app()->isProduction() ? $rule->mixedCase()->numbers()->uncompromised() : $rule;
        });

        RateLimiter::for('public-invitation', static function (Request $request): Limit {
            return Limit::perMinute((int) config('platform.public.rate_limit_per_minute', 60))
                ->by($request->ip());
        });
    }
}
