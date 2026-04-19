<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        if (app()->environment('production')) {
            $this->assertProductionConfig();
        }

        RateLimiter::for('clicker-tap', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            return [
                Limit::perSecond(8)->by('tap-second-'.$key),
                Limit::perMinute(240)->by('tap-minute-'.$key),
            ];
        });
    }

    private function assertProductionConfig(): void
    {
        $required = [
            'app.url' => config('app.url'),
            'services.telegram.bot_token' => config('services.telegram.bot_token'),
            'services.telegram.mini_app_url' => config('services.telegram.mini_app_url'),
        ];

        foreach ($required as $key => $value) {
            if (!is_string($value) || trim($value) === '') {
                throw new RuntimeException("Missing required production config: {$key}");
            }
        }

        if (filter_var(config('app.url'), FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('APP_URL must be a valid URL in production.');
        }

        if (filter_var(config('services.telegram.mini_app_url'), FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('TELEGRAM_MINI_APP_URL must be a valid URL in production.');
        }
    }
}
