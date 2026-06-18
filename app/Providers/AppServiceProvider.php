<?php

namespace App\Providers;

use App\Bracket\Contracts\BestThirdQualifier;
use App\Bracket\PointsBestThirdQualifier;
use App\FootballData\FootballDataLinker;
use App\Http\Client\LogOutgoingApiRequest;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\PasskeyLoginResponse;
use App\Http\Responses\RedirectAsIntended as AppRedirectAsIntended;
use App\Http\Responses\RegisterResponse;
use App\Http\Responses\TwoFactorLoginResponse;
use App\Http\Responses\VerifyEmailResponse;
use App\Predictions\Scoring\ScorerRegistry;
use App\Predictions\Settlement\SettlerRegistry;
use App\Support\ExternalIdOrder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Http\Responses\RedirectAsIntended as FortifyRedirectAsIntended;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;

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

        $this->app->bind(FortifyRedirectAsIntended::class, fn ($app, array $params) => new AppRedirectAsIntended($params['name'] ?? 'login'));
        $this->app->bind(BestThirdQualifier::class, PointsBestThirdQualifier::class);
        $this->app->singleton(FootballDataLinker::class, fn (): FootballDataLinker => FootballDataLinker::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthResponses();
        $this->configureOutgoingApiLogging();
    }

    protected function configureOutgoingApiLogging(): void
    {
        $logger = $this->app->make(LogOutgoingApiRequest::class);

        Event::listen(ResponseReceived::class, [$logger, 'handleResponseReceived']);
        Event::listen(ConnectionFailed::class, [$logger, 'handleConnectionFailed']);
    }

    protected function configureAuthResponses(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);
        $this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);
        $this->app->singleton(PasskeyLoginResponseContract::class, PasskeyLoginResponse::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Builder::macro('orderByExternalId', function (): Builder {
            return ExternalIdOrder::apply($this);
        });

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
