<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Fortify\Features;

function enableTurnstile(): void
{
    config([
        'turnstile.enabled' => true,
        'turnstile.site_key' => 'test-site-key',
        'turnstile.secret_key' => 'test-secret-key',
    ]);
}

function fakeTurnstileVerification(bool $success = true): void
{
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => $success]),
    ]);
}

test('login succeeds without turnstile token when turnstile is disabled', function () {
    config(['turnstile.enabled' => false]);

    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('predict', absolute: false));

    $this->assertAuthenticated();
});

test('login rejects missing turnstile token when turnstile is enabled', function () {
    enableTurnstile();

    $user = User::factory()->create();

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('cf-turnstile-response');

    $this->assertGuest();
});

test('login accepts a valid turnstile token when turnstile is enabled', function () {
    enableTurnstile();
    fakeTurnstileVerification();

    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'cf-turnstile-response' => 'valid-token',
    ])->assertRedirect(route('predict', absolute: false));

    $this->assertAuthenticated();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request['secret'] === 'test-secret-key'
            && $request['response'] === 'valid-token';
    });
});

test('registration rejects an invalid turnstile token when turnstile is enabled', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    enableTurnstile();
    fakeTurnstileVerification(success: false);

    $this->from(route('register'))
        ->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'cf-turnstile-response' => 'invalid-token',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('cf-turnstile-response');

    $this->assertGuest();
});

test('inertia shares turnstile config', function () {
    config([
        'turnstile.enabled' => true,
        'turnstile.site_key' => 'shared-site-key',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('turnstile.enabled', true)
            ->where('turnstile.siteKey', 'shared-site-key')
        );
});
