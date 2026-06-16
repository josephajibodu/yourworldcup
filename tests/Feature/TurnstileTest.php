<?php

use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\PredictionMarket;
use App\Models\Team;
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

test('predict submission rejects missing turnstile token when turnstile is enabled', function () {
    enableTurnstile();

    $kickoff = now()->addDays(2)->setTime(18, 0);

    $fixture = Fixture::factory()->create([
        'home_team_id' => Team::factory()->create()->id,
        'away_team_id' => Team::factory()->create()->id,
        'kickoff_at' => $kickoff,
        'lock_at' => $kickoff->copy()->subHour(),
    ]);

    $winner = FixtureMarket::factory()->create([
        'fixture_id' => $fixture->id,
        'prediction_market_id' => PredictionMarket::factory()->matchWinner()->create()->id,
    ]);

    $this->actingAs(User::factory()->create())
        ->from(route('predict', ['date' => $fixture->watDate()]))
        ->post(route('predict.store'), [
            'date' => $fixture->watDate(),
            'predictions' => [
                [
                    'fixture_market_id' => $winner->id,
                    'value' => ['selected' => 'home'],
                ],
            ],
            'banker_fixture_market_id' => null,
        ])
        ->assertRedirect(route('predict', ['date' => $fixture->watDate()]))
        ->assertSessionHasErrors('cf-turnstile-response');
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
