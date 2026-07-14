<?php

namespace App\Console\Commands;

use App\Cache\TournamentCache;
use App\Fixtures\FixtureInspector;
use App\Predictions\Markets\M101PlayerMarkets;
use App\Predictions\Markets\PlayerMarketAttachmentService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class AttachM101PlayerMarketsCommand extends Command
{
    protected $signature = 'markets:attach-m101-players
                            {--force : Attach even if teams do not match France and Spain}';

    protected $description = 'Create M101 player prediction markets and attach them only to match 101';

    public function handle(
        FixtureInspector $inspector,
        PlayerMarketAttachmentService $attachment,
        TournamentCache $cache,
    ): int {
        return $this->attach(
            $inspector,
            $attachment,
            $cache,
            M101PlayerMarkets::MATCH_NUMBER,
            M101PlayerMarkets::EXPECTED_TEAMS,
            M101PlayerMarkets::definitions(),
        );
    }

    /**
     * @param  list<string>  $expectedTeams
     * @param  list<array<string, mixed>>  $definitions
     */
    protected function attach(
        FixtureInspector $inspector,
        PlayerMarketAttachmentService $attachment,
        TournamentCache $cache,
        string $matchNumber,
        array $expectedTeams,
        array $definitions,
    ): int {
        try {
            $fixture = $inspector->findByMatchNumber($matchNumber);

            if (! $this->option('force')) {
                $inspector->assertTeams($fixture, $expectedTeams);
            }
        } catch (ValidationException $exception) {
            $this->error($exception->getMessage());
            $this->line("Use fixture:show {$matchNumber} to verify the match, or pass --force to override.");

            return self::FAILURE;
        }

        $attached = $attachment->attach($fixture, $definitions);

        $cache->bump('predict');

        $summary = $inspector->summarize($fixture->fresh(['homeTeam', 'awayTeam', 'stadium', 'markets.market']));

        $this->info(sprintf(
            'Attached %d player markets to M%s (%s vs %s).',
            $attached,
            $summary['externalId'],
            $summary['homeTeam'] ?? 'TBD',
            $summary['awayTeam'] ?? 'TBD',
        ));

        return self::SUCCESS;
    }
}
