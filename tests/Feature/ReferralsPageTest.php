<?php

use App\Models\Referral;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('renders the referrals page publicly', function () {
    $this->get(route('referrals'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('referrals')
            ->where('rules.pointsPerReferral', 1)
            ->where('rules.dailyCap', 5)
            ->where('share', null)
            ->where('stats', null)
        );
});

it('shows the users share link and stats when signed in', function () {
    $user = User::factory()->create();
    $referred = User::factory()->create(['referred_by_id' => $user->id]);

    Referral::factory()->create([
        'referrer_id' => $user->id,
        'referred_id' => $referred->id,
        'points' => 1,
    ]);

    actingAs($user)
        ->get(route('referrals'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('referrals')
            ->where('share.code', $user->referral_code)
            ->where('share.url', route('register', ['ref' => $user->referral_code], absolute: true))
            ->where('stats.totalReferrals', 1)
            ->where('stats.totalPoints', 1)
            ->where('stats.isVerified', true)
            ->where('stats.hasMadePrediction', false)
        );
});

it('generates a referral code for legacy users without one', function () {
    $user = User::factory()->create();
    $user->forceFill(['referral_code' => null])->save();

    actingAs($user)
        ->get(route('referrals'))
        ->assertOk();

    expect($user->fresh()->referral_code)->not->toBeNull();
});
