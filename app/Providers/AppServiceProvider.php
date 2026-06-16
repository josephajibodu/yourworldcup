<?php

namespace App\Providers;

use App\Predictions\Scoring\ScorerRegistry;
use App\Predictions\Settlement\SettlerRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ScorerRegistry::class, function ($app): ScorerRegistry {
            $registry = new ScorerRegistry;

            foreach (config('predictions.scorers', []) as $key => $scorerClass) {
                $registry->register($key, $app->make($scorerClass));
            }

            return $registry;
        });

        $this->app->singleton(SettlerRegistry::class, function ($app): SettlerRegistry {
            $registry = new SettlerRegistry;

            foreach (config('predictions.settlers', []) as $key => $settlerClass) {
                $registry->register($key, $app->make($settlerClass));
            }

            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
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
}
