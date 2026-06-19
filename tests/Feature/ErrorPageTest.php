<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Route::get('/__pest-http-error/{status}', function (string $status) {
        abort((int) $status);
    });
});

it('renders the branded 404 page', function () {
    $this->get('/this-route-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->where('status', 404)
        );
});

it('renders the branded 403 page for non-admin users', function () {
    actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->where('status', 403)
        );
});

it('renders the branded 419 page', function () {
    $this->get('/__pest-http-error/419')
        ->assertStatus(419)
        ->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->where('status', 419)
        );
});

it('renders the branded 500 page', function () {
    $this->get('/__pest-http-error/500')
        ->assertStatus(500)
        ->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->where('status', 500)
        );
});

it('renders the branded 503 page', function () {
    $this->get('/__pest-http-error/503')
        ->assertStatus(503)
        ->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->where('status', 503)
        );
});
