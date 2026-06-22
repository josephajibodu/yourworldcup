<?php

use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\PredictionMarket;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

function predictionMarkets(): array
{
    $matchWinner = PredictionMarket::query()->firstWhere('key', 'match_winner')
        ?? PredictionMarket::factory()->matchWinner()->create();

    $exactScore = PredictionMarket::query()->firstWhere('key', 'exact_score')
        ?? PredictionMarket::factory()->exactScore()->create();

    return ['matchWinner' => $matchWinner, 'exactScore' => $exactScore];
}

function finalFixtureWithMarkets(int $home, int $away): array
{
    $kickoff = now()->subHours(3);

    $fixture = Fixture::factory()->final($home, $away)->create([
        'home_team_id' => Team::factory()->create()->id,
        'away_team_id' => Team::factory()->create()->id,
        'kickoff_at' => $kickoff,
        'lock_at' => $kickoff->copy()->subHour(),
    ]);

    ['matchWinner' => $matchWinner, 'exactScore' => $exactScore] = predictionMarkets();

    $winner = FixtureMarket::factory()->create([
        'fixture_id' => $fixture->id,
        'prediction_market_id' => $matchWinner->id,
    ]);

    $score = FixtureMarket::factory()->create([
        'fixture_id' => $fixture->id,
        'prediction_market_id' => $exactScore->id,
    ]);

    return ['fixture' => $fixture, 'winner' => $winner, 'score' => $score];
}

function watWeekStart(string $watDate): string
{
    return Carbon::parse($watDate, config('predictions.timezone'))
        ->startOfWeek(Carbon::MONDAY)
        ->toDateString();
}

function siteAdmin(): User
{
    return User::factory()->create([
        'email' => config('app.admin_email'),
    ]);
}

/**
 * An open fixture with winner + exact-score markets on a WAT day relative to today.
 *
 * @return array{fixture: Fixture, winner: FixtureMarket, score: FixtureMarket}
 */
function predictableFixture(int $daysFromToday = 1): array
{
    $kickoff = Carbon::now(config('predictions.timezone'))
        ->startOfDay()
        ->addDays($daysFromToday)
        ->setTime(18, 0)
        ->utc();

    $fixture = Fixture::factory()->create([
        'home_team_id' => Team::factory()->create(['name' => 'Mexico'])->id,
        'away_team_id' => Team::factory()->create(['name' => 'Spain'])->id,
        'kickoff_at' => $kickoff,
        'lock_at' => $kickoff->copy()->subHour(),
    ]);

    ['matchWinner' => $matchWinner, 'exactScore' => $exactScore] = predictionMarkets();

    $winner = FixtureMarket::factory()->create([
        'fixture_id' => $fixture->id,
        'prediction_market_id' => $matchWinner->id,
    ]);

    $score = FixtureMarket::factory()->create([
        'fixture_id' => $fixture->id,
        'prediction_market_id' => $exactScore->id,
    ]);

    return ['fixture' => $fixture, 'winner' => $winner, 'score' => $score];
}
