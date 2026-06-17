<?php

namespace App\Console\Commands;

use App\Bracket\BracketResolverService;
use App\Models\Fixture;
use Illuminate\Console\Command;

class ResolveBracketSlotsCommand extends Command
{
    protected $signature = 'bracket:resolve
                            {--group= : Resolve winner and runner-up slots for a completed group}
                            {--best-thirds : Resolve best third-place slots}
                            {--fixture= : Resolve knockout feeder slots for a finished fixture external id}
                            {--all : Resolve every slot that can be filled right now}';

    protected $description = 'Resolve bracket slot placeholders into fixture teams';

    public function handle(BracketResolverService $resolver): int
    {
        $resolved = 0;

        if ($group = $this->option('group')) {
            $resolved += $resolver->resolveGroup(strtoupper((string) $group));
            $this->info("Resolved {$resolved} slot(s) for group {$group}.");

            return self::SUCCESS;
        }

        if ($this->option('best-thirds')) {
            $resolved += $resolver->resolveBestThirds();
            $this->info("Resolved {$resolved} best third-place slot(s).");

            return self::SUCCESS;
        }

        if ($fixtureExternalId = $this->option('fixture')) {
            $fixture = Fixture::query()
                ->where('external_id', (string) $fixtureExternalId)
                ->with(['homeTeam', 'awayTeam'])
                ->first();

            if ($fixture === null) {
                $this->error("Fixture [{$fixtureExternalId}] was not found.");

                return self::FAILURE;
            }

            $resolved += $resolver->resolveKnockoutFeeders($fixture);
            $this->info("Resolved {$resolved} knockout feeder slot(s) for match {$fixtureExternalId}.");

            return self::SUCCESS;
        }

        if ($this->option('all')) {
            $resolved = $resolver->resolveAllAvailable();
            $this->info("Resolved {$resolved} bracket slot(s).");

            return self::SUCCESS;
        }

        $this->line('Nothing to do. Pass --group, --best-thirds, --fixture, or --all.');

        return self::SUCCESS;
    }
}
