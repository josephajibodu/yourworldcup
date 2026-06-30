<?php

namespace App\Http\Middleware;

use App\Auth\ImpersonationManager;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'appUrl' => rtrim((string) config('app.url'), '/'),
            'auth' => [
                'user' => $request->user(),
                'isAdmin' => $request->user()?->isSiteAdmin() ?? false,
            ],
            'impersonating' => $this->impersonating($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'turnstile' => [
                'enabled' => (bool) config('turnstile.enabled'),
                'siteKey' => config('turnstile.site_key'),
            ],
            'predictions' => [
                'matchDurationMinutes' => (int) config('predictions.match_duration_minutes'),
            ],
        ];
    }

    /**
     * @return array{userName: string}|null
     */
    private function impersonating(Request $request): ?array
    {
        if (! $request->session()->has(ImpersonationManager::SESSION_KEY)) {
            return null;
        }

        $user = $request->user();

        if ($user === null) {
            return null;
        }

        return [
            'userName' => $user->name,
        ];
    }
}
