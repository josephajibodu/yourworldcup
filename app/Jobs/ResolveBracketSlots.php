<?php

namespace App\Jobs;

use App\Bracket\BracketResolverService;
use App\Cache\TournamentCache;
use App\Models\Fixture;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ResolveBracketSlots implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $fixtureId) {}

    public function handle(BracketResolverService $resolver, TournamentCache $cache): void
    {
        $fixture = Fixture::query()->with(['homeTeam', 'awayTeam'])->find($this->fixtureId);

        if ($fixture === null) {
            return;
        }

        $resolved = $resolver->resolveFromFixture($fixture);

        if ($resolved > 0) {
            $cache->bump('bracket');
        }
    }
}
