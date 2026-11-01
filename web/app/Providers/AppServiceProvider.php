<?php

declare(strict_types=1);

namespace App\Providers;

use App\Data\PublicInvitation;
use App\Models\User;
use App\Support\Guests\GuestCsvParser;
use App\Support\Invitations\WishFeed;
use App\Support\Mail\SandboxMailbox;
use App\Support\Media\UploadScanner;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GuestCsvParser::class, static fn (): GuestCsvParser => new GuestCsvParser(
            maxRows: (int) config('platform.guests.import_max_rows', 500),
            maxPartySize: (int) config('platform.guests.max_party_size', 20),
        ));

        $this->app->bind(UploadScanner::class, static fn (): UploadScanner => new UploadScanner(
            command: config('platform.media.scan_command'),
            failClosed: (bool) config('platform.media.scan_fail_closed', true),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        // While the sender is Resend's onboarding address, only the account
        // owner can receive mail, so every message is funneled to that inbox.
        $this->app->make(SandboxMailbox::class)->apply(config('mail.redirect_to'));

        Password::defaults(static function (): Password {
            $rule = Password::min(8);

            return app()->isProduction() ? $rule->mixedCase()->numbers()->uncompromised() : $rule;
        });

        RateLimiter::for('public-invitation', static function (Request $request): Limit {
            return Limit::perMinute((int) config('platform.public.rate_limit_per_minute', 60))
                ->by($request->ip());
        });

        // Personal link lookups and RSVP submissions are unauthenticated and
        // token-guessable, so both are capped per IP (docs/SECURITY.md, 9).
        RateLimiter::for('guest-invitation', static function (Request $request): Limit {
            return Limit::perMinute((int) config('platform.guests.rate_limit_per_minute', 60))
                ->by($request->ip());
        });

        RateLimiter::for('guest-rsvp', static function (Request $request): Limit {
            return Limit::perMinute((int) config('platform.guests.rsvp_rate_limit_per_minute', 10))
                ->by($request->ip());
        });

        // The public gallery is unauthenticated like the invitation page.
        RateLimiter::for('public-gallery', static function (Request $request): Limit {
            return Limit::perMinute((int) config('platform.media.public_rate_limit_per_minute', 30))
                ->by($request->ip());
        });

        // Contact form: unauthenticated and writes a row, so it is capped per IP
        // (docs/SECURITY.md, section 9).
        RateLimiter::for('lead-capture', static function (Request $request): Limit {
            return Limit::perMinute((int) config('platform.marketing.lead_rate_limit_per_minute', 6))
                ->by($request->ip());
        });

        // Registration writes an account and sends mail, so it is capped per IP
        // like the other unauthenticated write endpoints (docs/SECURITY.md, 9).
        RateLimiter::for('register', static function (Request $request): Limit {
            return Limit::perMinute((int) config('platform.security.register_rate_limit_per_minute', 5))
                ->by($request->ip());
        });

        // Operator area. The role column is the only source of truth; a route
        // prefix or a UUID in a URL never grants access (docs/SECURITY.md).
        Gate::define('access-admin', static fn (User $user): bool => $user->isAdmin());

        // The wish wall is rendered by a shared partial, so the feed is attached
        // here instead of being passed by every controller. Demo and showcase
        // pages get the fictional list: a portfolio must never publish a real
        // guest's message (docs/PRIVACY_AND_RETENTION.md).
        View::composer('invitations.partials.power', static function ($view): void {
            $invitation = $view->getData()['invitation'] ?? null;

            if (! $invitation instanceof PublicInvitation) {
                $view->with('wishes', null);

                return;
            }

            $sample = (bool) ($view->getData()['demo'] ?? false)
                || (bool) ($view->getData()['showcase'] ?? false);

            $view->with('wishes', WishFeed::for(
                $invitation,
                max(1, (int) request()->integer('ucapan', 1)),
                $sample,
            ));
        });
    }
}
