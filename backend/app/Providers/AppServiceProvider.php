<?php

namespace App\Providers;

use App\Services\Football\CompetitionCatalogService;
use App\Services\Football\CompetitionImportService;
use App\Services\FootballData\FootballDataClient;
use App\Services\FootballData\FootballDataRateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Un solo limitador para toda la app: el cupo es del token, no del usuario.
        $this->app->singleton(FootballDataRateLimiter::class, fn () => new FootballDataRateLimiter(
            Cache::store(),
            config('football_data.rate_limit.max_requests'),
            config('football_data.rate_limit.window_seconds'),
        ));

        $this->app->bind(FootballDataClient::class, fn (Application $app) => new FootballDataClient(
            $app->make(HttpFactory::class),
            $app->make(FootballDataRateLimiter::class),
            config('football_data.base_url'),
            config('football_data.token'),
            config('football_data.connect_timeout'),
            config('football_data.timeout'),
        ));

        $this->app->when(CompetitionCatalogService::class)
            ->needs('$ttlMinutes')->giveConfig('football_data.ttl.competitions_minutes');

        $this->app->when(CompetitionImportService::class)
            ->needs('$ttlMinutes')->giveConfig('football_data.ttl.competition_teams_minutes');
    }

    public function boot(): void
    {
        // La sesión de la SPA dura una jornada; al vencer, la SPA pide login de nuevo.
        Passport::personalAccessTokensExpireIn(now()->addHours((int) config('passport.token_ttl_hours')));

        // Límite de nuestra propia API (distinto del de football-data).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
