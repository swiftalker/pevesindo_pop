<?php

namespace App\Providers;

use App\Engine\Odoo\Client\OdooClient;
use App\Engine\Odoo\Client\OdooGateway;
use App\Engine\Odoo\Domain;
use App\Engine\Odoo\Events\OdooSyncCompleted;
use App\Engine\Odoo\Events\OdooSyncFailed;
use App\Engine\Odoo\Events\OdooSyncRequested;
use App\Engine\Odoo\Events\OdooSyncStarted;
use App\Engine\Odoo\Listeners\DispatchOdooSyncJob;
use App\Engine\Odoo\Listeners\TrackSyncProgress;
use App\Engine\Odoo\Reports\ReportService;
use App\Engine\Odoo\Services\PullService;
use App\Engine\Odoo\Services\PushService;
use App\Engine\Odoo\Sessions\SessionManager;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        foreach (glob(app_path().'/Helpers/*.php') as $filename) {
            require_once $filename;
        }
        $this->app->singleton(OdooClient::class);
        $this->app->singleton(OdooGateway::class);
        $this->app->singleton(SessionManager::class);
        $this->app->singleton(ReportService::class);
        $this->app->singleton(PushService::class);
        $this->app->singleton(PullService::class);

        // Register Odoo sync domain jobs (registry-based)
        Domain::bootEventListeners();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiters();
        $this->configureEvents();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure rate limiters for the application.
     */
    protected function configureRateLimiters(): void
    {
        RateLimiter::for('odoo-api', function (object $job) {
            return Limit::perDay(config('odoo.throttle_allow', 3200));
        });
    }

    /**
     * Register event-listener mappings for the application.
     */
    protected function configureEvents(): void
    {
        Event::listen(
            OdooSyncRequested::class,
            DispatchOdooSyncJob::class,
        );

        Event::listen(OdooSyncStarted::class, TrackSyncProgress::class);
        Event::listen(OdooSyncCompleted::class, TrackSyncProgress::class);
        Event::listen(OdooSyncFailed::class, TrackSyncProgress::class);
    }
}
