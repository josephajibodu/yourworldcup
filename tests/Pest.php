<?php

use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\PredictionMarket;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A finished fixture (kicked off three hours ago) carrying a winner and an
 * exact-score market, ready to settle.
 *
 * @return array{fixture: Fixture, winner: FixtureMarket, score: FixtureMarket}
 */
function finalFixtureWithMarkets(int $home, int $away): array
{
    $kickoff = now()->subHours(3);

    $fixture = Fixture::factory()->final($home, $away)->create([
        'home_team_id' => Team::factory()->create()->id,
        'away_team_id' => Team::factory()->create()->id,
        'kickoff_at' => $kickoff,
        'lock_at' => $kickoff->copy()->subHour(),
    ]);

    $winner = FixtureMarket::factory()->create([
        'fixture_id' => $fixture->id,
        'prediction_market_id' => PredictionMarket::factory()->matchWinner()->create()->id,
    ]);

    $score = FixtureMarket::factory()->create([
        'fixture_id' => $fixture->id,
        'prediction_market_id' => PredictionMarket::factory()->exactScore()->create()->id,
    ]);

    return ['fixture' => $fixture, 'winner' => $winner, 'score' => $score];
}
