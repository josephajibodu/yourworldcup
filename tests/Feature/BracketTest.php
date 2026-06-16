<?php

use App\Predictions\Importing\WorldCupImporter;
use Database\Seeders\PredictionMarketSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PredictionMarketSeeder::class);
    WorldCupImporter::fromDefaultPath()->import();
});

it('renders the bracket page publicly', function () {
    $this->get(route('bracket'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('bracket'));
});

it('passes the twelve group tables with four teams each', function () {
    $this->get(route('bracket'))->assertInertia(fn (Assert $page) => $page
        ->has('groups', 12)
        ->has('groups.0', fn (Assert $group) => $group
            ->where('code', 'A')
            ->has('teams', 4)
            ->has('teams.0', fn (Assert $team) => $team
                ->hasAll(['id', 'name', 'code', 'flag', 'played', 'gd', 'points'])
                ->etc()
            )
        )
    );
});

it('passes the full thirty-two match knockout tree', function () {
    $this->get(route('bracket'))->assertInertia(fn (Assert $page) => $page
        ->has('knockout', 32)
        ->has('knockout.0', fn (Assert $match) => $match
            ->where('code', 'M73')
            ->where('stage', 'r32')
            ->hasAll(['kickoffAt', 'stageLabel', 'home', 'away', 'feeders'])
            ->etc()
        )
    );
});

it('resolves feeder labels for later rounds and the final feeds the trophy', function () {
    $this->get(route('bracket'))->assertInertia(fn (Assert $page) => $page
        ->has('knockout', 32, fn (Assert $match) => $match->etc())
        // The final (M104) is fed by the two semi-finals (101, 102).
        ->where('knockout.31.code', 'M104')
        ->where('knockout.31.feeders', [101, 102])
        ->where('knockout.31.home.label', 'W101')
        ->where('knockout.31.away.label', 'W102')
    );
});
