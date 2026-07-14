<?php

namespace App\Console\Commands;

use App\Bracket\BracketResolverService;
use App\Cache\TournamentCache;
use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Fixtures\FixtureResult;
use App\Fixtures\FixtureResultRecorder;
use App\Models\Fixture;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class RepairTournamentCommand extends Command
{
    protected $signature = 'tournament:repair
                            {--backfill : Backfill winner_team_id on final fixtures from scores, extra time, and penalties}
                            {--verify : List knockout fixtures and flag missing winners or teams}
                            {--propagate-r32 : Push R32 winners into R16 fixture slots}
                            {--check-r16 : Show R16 team assignments}
                            {--propagate-r16 : Push R16 winners into QF fixture slots}
                            {--check-qf : Show QF team assignments}
                            {--propagate-qf : Push QF winners into SF fixture slots}
                            {--all : Run every step in order}';

    protected $description = 'Repair knockout bracket state in discrete steps after a bad import or missing winners';

    /**
     * @var list<string>
     */
    private const STEPS = [
        'backfill',
        'verify',
        'propagate-r32',
        'check-r16',
        'propagate-r16',
        'check-qf',
        'propagate-qf',
    ];

    public function handle(
        FixtureResultRecorder $recorder,
        BracketResolverService $resolver,
        TournamentCache $cache,
    ): int {
        $steps = $this->resolveSteps();

        if ($steps === []) {
            $this->warn('No step selected.');
            $this->newLine();
            $this->line('Available steps (combine any flags):');
            $this->line('  --backfill       Backfill winner_team_id from scores / ET / pens');
            $this->line('  --verify         Audit knockout fixtures for missing data');
            $this->line('  --propagate-r32  Resolve R32 winners into R16 slots');
            $this->line('  --check-r16      Show R16 home/away assignments');
            $this->line('  --propagate-r16  Resolve R16 winners into QF slots');
            $this->line('  --check-qf       Show QF home/away assignments');
            $this->line('  --propagate-qf   Resolve QF winners into SF slots');
            $this->line('  --all            Run every step above in order');
            $this->newLine();
            $this->line('Example: php artisan tournament:repair --backfill --verify --propagate-r32');

            return self::SUCCESS;
        }

        $this->warn('Tournament repair — running '.count($steps).' step(s).');

        foreach ($steps as $step) {
            $this->newLine();
            $this->components->info(strtoupper(str_replace('-', ' ', $step)));

            match ($step) {
                'backfill' => $this->runBackfill($recorder),
                'verify' => $this->runVerify(),
                'propagate-r32' => $this->runPropagate($resolver, $cache, FixtureStage::Round32),
                'check-r16' => $this->runCheck(FixtureStage::Round16),
                'propagate-r16' => $this->runPropagate($resolver, $cache, FixtureStage::Round16),
                'check-qf' => $this->runCheck(FixtureStage::QuarterFinal),
                'propagate-qf' => $this->runPropagate($resolver, $cache, FixtureStage::QuarterFinal),
            };
        }

        $this->newLine();
        $this->info('Repair step(s) complete.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function resolveSteps(): array
    {
        if ($this->option('all')) {
            return self::STEPS;
        }

        return array_values(array_filter(
            self::STEPS,
            fn (string $step): bool => (bool) $this->option($step),
        ));
    }

    private function runBackfill(FixtureResultRecorder $recorder): void
    {
        $fixed = 0;
        $blocked = [];

        Fixture::query()
            ->where('status', FixtureStatus::Final)
            ->whereNull('winner_team_id')
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->orderBy('external_id')
            ->each(function (Fixture $fixture) use ($recorder, &$fixed, &$blocked): void {
                $recorder->record($fixture, new FixtureResult(
                    homeScore: (int) $fixture->home_score,
                    awayScore: (int) $fixture->away_score,
                    extraTimeHome: $fixture->extra_time_home,
                    extraTimeAway: $fixture->extra_time_away,
                    penaltiesHome: $fixture->penalties_home,
                    penaltiesAway: $fixture->penalties_away,
                    resultDuration: $fixture->result_duration,
                ));

                $fixture->refresh();

                if ($fixture->winner_team_id !== null) {
                    $fixed++;
                    $this->line("  M{$fixture->external_id}: winner set (team {$fixture->winner_team_id})");

                    return;
                }

                $blocked[] = $this->formatFixtureLine($fixture);
            });

        $this->newLine();
        $this->line("Backfilled {$fixed} winner(s).");

        if ($blocked !== []) {
            $this->warn(count($blocked).' fixture(s) still have no winner:');
            foreach ($blocked as $line) {
                $this->line("  {$line}");
            }
        }
    }

    private function runVerify(): void
    {
        $fixtures = $this->knockoutFixtures([
            FixtureStage::Round32,
            FixtureStage::Round16,
            FixtureStage::QuarterFinal,
            FixtureStage::SemiFinal,
        ]);

        if ($fixtures->isEmpty()) {
            $this->line('  No knockout fixtures found.');

            return;
        }

        $missingWinners = 0;
        $missingTeams = 0;

        foreach ($fixtures as $fixture) {
            $issues = [];

            if ($fixture->home_team_id === null || $fixture->away_team_id === null) {
                $issues[] = 'missing teams';
                $missingTeams++;
            }

            if ($fixture->status === FixtureStatus::Final && $fixture->winner_team_id === null) {
                $issues[] = 'missing winner';
                $missingWinners++;
            }

            $suffix = $issues === [] ? 'OK' : implode(', ', $issues);
            $this->line('  '.$this->formatFixtureLine($fixture)." [{$suffix}]");
        }

        $this->newLine();
        $this->line("Checked {$fixtures->count()} fixture(s): {$missingTeams} missing teams, {$missingWinners} missing winners.");
    }

    private function runPropagate(
        BracketResolverService $resolver,
        TournamentCache $cache,
        FixtureStage $feederStage,
    ): void {
        $feeders = Fixture::query()
            ->where('stage', $feederStage)
            ->where('status', FixtureStatus::Final)
            ->whereNotNull('external_id')
            ->orderBy('external_id')
            ->get();

        if ($feeders->isEmpty()) {
            $this->warn("  No final {$feederStage->label()} fixtures found.");

            return;
        }

        $totalResolved = 0;
        $skipped = [];

        foreach ($feeders as $feeder) {
            if ($feeder->winner_team_id === null) {
                $skipped[] = "M{$feeder->external_id} (no winner)";

                continue;
            }

            $resolved = $resolver->resolveKnockoutFeeders($feeder);
            $totalResolved += $resolved;

            $this->line("  M{$feeder->external_id}: resolved {$resolved} slot(s)");
        }

        foreach (TournamentCache::DOMAINS as $domain) {
            $cache->bump($domain);
        }

        $this->newLine();
        $this->line("Propagated {$totalResolved} bracket slot(s) from {$feederStage->label()}.");

        if ($skipped !== []) {
            $this->warn('Skipped feeder(s) without a winner:');
            foreach ($skipped as $line) {
                $this->line("  {$line}");
            }
        }
    }

    private function runCheck(FixtureStage $stage): void
    {
        $fixtures = $this->knockoutFixtures([$stage]);

        if ($fixtures->isEmpty()) {
            $this->line("  No {$stage->label()} fixtures found.");

            return;
        }

        $complete = 0;

        foreach ($fixtures as $fixture) {
            $hasTeams = $fixture->home_team_id !== null && $fixture->away_team_id !== null;

            if ($hasTeams) {
                $complete++;
            }

            $this->line('  '.$this->formatFixtureLine($fixture).' ['.($hasTeams ? 'OK' : 'missing teams').']');
        }

        $this->newLine();
        $this->line("{$stage->label()}: {$complete}/{$fixtures->count()} fixtures have both teams assigned.");
    }

    /**
     * @param  list<FixtureStage>  $stages
     * @return Collection<int, Fixture>
     */
    private function knockoutFixtures(array $stages): Collection
    {
        return Fixture::query()
            ->whereIn('stage', $stages)
            ->whereNotNull('external_id')
            ->with([
                'homeTeam:id,name,code',
                'awayTeam:id,name,code',
                'winnerTeam:id,name,code',
            ])
            ->orderBy('external_id')
            ->get();
    }

    private function formatFixtureLine(Fixture $fixture): string
    {
        $home = $fixture->homeTeam?->code ?? 'NULL';
        $away = $fixture->awayTeam?->code ?? 'NULL';
        $winner = $fixture->winnerTeam?->code ?? 'NULL';
        $score = $fixture->home_score !== null && $fixture->away_score !== null
            ? "{$fixture->home_score}-{$fixture->away_score}"
            : '-';

        $extra = $fixture->extra_time_home !== null || $fixture->extra_time_away !== null
            ? ' ET='.($fixture->extra_time_home ?? 0).'-'.($fixture->extra_time_away ?? 0)
            : '';

        $pens = $fixture->penalties_home !== null || $fixture->penalties_away !== null
            ? ' pens='.($fixture->penalties_home ?? '-').'-'.($fixture->penalties_away ?? '-')
            : '';

        return sprintf(
            'M%s [%s] %s vs %s %s%s%s => %s',
            $fixture->external_id,
            $fixture->stage->value,
            $home,
            $away,
            $score,
            $extra,
            $pens,
            $winner,
        );
    }
}
