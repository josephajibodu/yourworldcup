<?php

use Inertia\Testing\AssertableInertia as Assert;

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('shares app url for seo metadata', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('appUrl')
            ->where('appUrl', rtrim((string) config('app.url'), '/')),
        );
});