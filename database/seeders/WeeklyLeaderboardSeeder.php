<?php

namespace Database\Seeders;

use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\Prediction;
use App\Models\PredictionMarket;
use App\Models\Team;
use App\Models\User;
use App\Predictions\Leaderboard\LeaderboardService;
use App\Predictions\Settlement\SettlementService;
use App\Rewards\WeeklyRewardService;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class WeeklyLeaderboardSeeder extends Seeder
{
    /**
     * @var list<array{email: string, name: string, points: int}>
     */
    private const PLAYERS = [
        ['email' => 'weekly-rank1@example.test', 'name' => '@weekly_rank1', 'points' => 5],
        ['email' => 'weekly-rank2@example.test', 'name' => '@weekly_rank2', 'points' => 4],
        ['email' => 'weekly-rank3@example.test', 'name' => '@weekly_rank3', 'points' => 2],
        ['email' => 'weekly-rank4@example.test', 'name' => '@weekly_rank4', 'points' => 1],
        ['email' => 'weekly-rank5@example.test', 'name' => '@weekly_rank5', 'points' => 0],
    ];

    public function run(): void
    {
        $this->call(PredictionMarketSeeder::class);

        $timezone = config('predictions.timezone');
        $weekStart = Carbon::now($timezone)->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekSunday = Carbon::parse($weekStart, $timezone)->addDays(6)->toDateString();
        $weekSaturday = Carbon::parse($weekStart, $timezone)->addDays(5)->toDateString();

        $matchWinner = PredictionMarket::query()->firstWhere('key', 'match_winner');
        $exactScore = PredictionMarket::query()->firstWhere('key', 'exact_score');

        if ($matchWinner === null || $exactScore === null) {
            $this->command?->error('Prediction markets are missing. Run PredictionMarketSeeder first.');

            return;
        }

        $home = Team::factory()->create(['name' => 'Seed Home FC']);
        $away = Team::factory()->create(['name' => 'Seed Away FC']);

        $saturdayFixture = $this->fixtureForDay(
            weekStart: $weekStart,
            day: $weekSaturday,
            suffix: 'saturday',
            homeTeamId: $home->id,
            awayTeamId: $away->id,
            kickoffTime: '15:00:00',
            homeScore: 1,
            awayScore: 0,
        );

        $sundayFixture = $this->fixtureForDay(
            weekStart: $weekStart,
            day: $weekSunday,
            suffix: 'sunday',
            homeTeamId: $home->id,
            awayTeamId: $away->id,
            kickoffTime: '21:00:00',
            homeScore: 2,
            awayScore: 1,
        );

        $saturdayWinner = $this->market($saturdayFixture, $matchWinner);
        $saturdayScore = $this->market($saturdayFixture, $exactScore);
        $sundayWinner = $this->market($sundayFixture, $matchWinner);
        $sundayScore = $this->market($sundayFixture, $exactScore);

        $users = collect(self::PLAYERS)->map(function (array $player): User {
            return User::query()->updateOrCreate(
                ['email' => $player['email']],
                [
                    'name' => $player['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );
        });

        $this->prediction($users[0], $sundayScore, ['home' => 2, 'away' => 1], now()->subHours(10));
        $this->prediction($users[0], $sundayWinner, ['selected' => 'home'], now()->subHours(10));
        $this->prediction($users[0], $saturdayWinner, ['selected' => 'home'], now()->subHours(12));

        $this->prediction($users[1], $saturdayScore, ['home' => 1, 'away' => 0], now()->subHours(11));
        $this->prediction($users[1], $sundayWinner, ['selected' => 'home'], now()->subHours(9));

        $this->prediction($users[2], $saturdayWinner, ['selected' => 'home'], now()->subHours(13));
        $this->prediction($users[2], $sundayWinner, ['selected' => 'home'], now()->subHours(8));

        $this->prediction($users[3], $sundayWinner, ['selected' => 'home'], now()->subHours(7));
        $this->prediction($users[3], $saturdayWinner, ['selected' => 'away'], now()->subHours(6));

        $this->prediction($users[4], $sundayWinner, ['selected' => 'away'], now()->subHours(5));
        $this->prediction($users[4], $saturdayWinner, ['selected' => 'draw'], now()->subHours(4));

        app(SettlementService::class)->settleFixture($saturdayFixture->fresh(), includeSettled: true);
        app(SettlementService::class)->settleFixture($sundayFixture->fresh(), includeSettled: true);

        $leaderboard = app(LeaderboardService::class)->weekly($weekStart);
        $rewardsReady = app(WeeklyRewardService::class)->isWeekReadyForClaims($weekStart);

        $this->command?->newLine();
        $this->command?->info("Seeded weekly leaderboard for {$weekStart} (Mon) – {$weekSunday} (Sun).");
        $this->command?->info('All test users use password: password');
        $this->command?->newLine();

        foreach ($leaderboard->take(5) as $row) {
            $this->command?->line(sprintf(
                '  #%d  %-16s  %d pts',
                $row['rank'],
                $row['name'],
                $row['points'],
            ));
        }

        $this->command?->newLine();
        $this->command?->line('Reward claims: '.($rewardsReady ? 'open' : 'waiting for Sunday final'));
        $this->command?->line('Leaderboard: /leaderboard?date='.$weekStart);
        $this->command?->newLine();
    }

    private function fixtureForDay(
        string $weekStart,
        string $day,
        string $suffix,
        int $homeTeamId,
        int $awayTeamId,
        string $kickoffTime,
        int $homeScore,
        int $awayScore,
    ): Fixture {
        $timezone = config('predictions.timezone');
        $kickoff = Carbon::parse("{$day} {$kickoffTime}", $timezone)->utc();

        return Fixture::query()->updateOrCreate(
            ['external_id' => "dev-weekly-{$weekStart}-{$suffix}"],
            [
                'stage' => FixtureStage::Group,
                'group_code' => 'A',
                'home_team_id' => $homeTeamId,
                'away_team_id' => $awayTeamId,
                'kickoff_at' => $kickoff,
                'lock_at' => $kickoff->copy()->subHour(),
                'status' => FixtureStatus::Final,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'winner_team_id' => $homeTeamId,
            ],
        );
    }

    private function market(Fixture $fixture, PredictionMarket $market): FixtureMarket
    {
        return FixtureMarket::query()->firstOrCreate(
            [
                'fixture_id' => $fixture->id,
                'prediction_market_id' => $market->id,
            ],
            [
                'is_enabled' => true,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function prediction(
        User $user,
        FixtureMarket $market,
        array $value,
        CarbonInterface $submittedAt,
    ): Prediction {
        return Prediction::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'fixture_market_id' => $market->id,
            ],
            [
                'value' => $value,
                'is_banker' => false,
                'submitted_at' => $submittedAt,
                'points_awarded' => null,
                'scored_at' => null,
            ],
        );
    }
}
