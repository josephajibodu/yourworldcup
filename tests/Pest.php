<?php

use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\Prediction;
use App\Models\PredictionMarket;
use App\Models\Team;
use App\Models\User;
use App\Predictions\Settlement\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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

function travelToWeeklyClaimWindow(string $weekStart, string $day = 'sunday'): void
{
    $timezone = config('predictions.timezone');
    $weekSunday = Carbon::parse($weekStart, $timezone)->addDays(6);

    $target = $day === 'monday'
        ? $weekSunday->copy()->addDay()->setTime(10, 0)
        : $weekSunday->copy()->setTime(10, 0);

    Carbon::setTestNow($target);
}

/**
 * @return array{weekStart: string, weekSunday: string, fixture: Fixture, winner: FixtureMarket}
 */
function weeklySundayFixture(int $home = 2, int $away = 1, ?string $weekStart = null): array
{
    $timezone = config('predictions.timezone');
    $weekStart ??= Carbon::now($timezone)->startOfWeek(Carbon::MONDAY)->toDateString();
    $weekSunday = Carbon::parse($weekStart, $timezone)->addDays(6)->toDateString();
    $kickoff = Carbon::parse("{$weekSunday} 21:00:00", $timezone)->utc();

    $fixture = Fixture::factory()->create([
        'home_team_id' => Team::factory()->create()->id,
        'away_team_id' => Team::factory()->create()->id,
        'kickoff_at' => $kickoff,
        'lock_at' => $kickoff->copy()->subHour(),
        'status' => FixtureStatus::Scheduled,
        'home_score' => null,
        'away_score' => null,
    ]);

    ['matchWinner' => $matchWinner] = predictionMarkets();

    $winner = FixtureMarket::factory()->create([
        'fixture_id' => $fixture->id,
        'prediction_market_id' => $matchWinner->id,
    ]);

    return compact('weekStart', 'weekSunday', 'fixture', 'winner');
}

function settleWeeklyFixture(Fixture $fixture, int $home, int $away): void
{
    $fixture->update([
        'status' => FixtureStatus::Final,
        'home_score' => $home,
        'away_score' => $away,
        'winner_team_id' => $home > $away ? $fixture->home_team_id : $fixture->away_team_id,
    ]);

    app(SettlementService::class)->settleFixture($fixture->fresh());
}

/**
 * @param  array<int, User>|Collection<int, User>  $users
 */
function rankUsersForWeek(string $weekStart, Fixture $fixture, $winnerMarket, array|Collection $users): void
{
    $users = collect($users);

    $points = $users->count();

    foreach ($users as $index => $user) {
        Prediction::factory()->for($user)->create([
            'fixture_market_id' => $winnerMarket->id,
            'value' => ['selected' => 'home'],
            'submitted_at' => now()->subHours($points - $index),
        ]);
    }

    settleWeeklyFixture($fixture, 2, 1);
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
