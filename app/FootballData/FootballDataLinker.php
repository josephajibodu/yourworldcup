<?php

namespace App\FootballData;

use App\Enums\FixtureStage;
use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FootballDataLinker
{
    /** @var array<string, string> */
    private array $teamCodeAliases;

    public function __construct(
        private readonly string $dataPath,
        private readonly string $provider,
    ) {
        /** @var array<string, string> $aliases */
        $aliases = config('football_data.team_code_aliases', []);

        $this->teamCodeAliases = $aliases;
    }

    public static function fromConfig(): self
    {
        return new self(
            dataPath: (string) config('football_data.data_path'),
            provider: (string) config('football_data.provider'),
        );
    }

    /**
     * @return array{linked: int, skipped: int, unmatched: list<string>}
     */
    public function linkTeams(): array
    {
        $payload = $this->readTeamsPayload();
        $linked = 0;
        $skipped = 0;
        $unmatched = [];

        foreach ($payload as $row) {
            $providerTeamId = (string) ($row['id'] ?? '');

            if ($providerTeamId === '') {
                continue;
            }

            $team = $this->findLocalTeam($row);

            if ($team === null) {
                $unmatched[] = sprintf(
                    'Team %s (%s)',
                    $providerTeamId,
                    (string) ($row['tla'] ?? $row['name'] ?? 'unknown'),
                );

                continue;
            }

            if ($team->provider === $this->provider && $team->provider_team_id === $providerTeamId) {
                $skipped++;

                continue;
            }

            $team->update([
                'provider' => $this->provider,
                'provider_team_id' => $providerTeamId,
            ]);

            $linked++;
        }

        return compact('linked', 'skipped', 'unmatched');
    }

    /**
     * @return array{linked: int, skipped: int, unmatched: list<string>}
     */
    public function linkFixtures(): array
    {
        $payload = $this->readMatchesPayload();
        $teamsByProviderId = Team::query()
            ->where('provider', $this->provider)
            ->whereNotNull('provider_team_id')
            ->pluck('id', 'provider_team_id');

        $linked = 0;
        $skipped = 0;
        $unmatched = [];

        foreach ($payload as $row) {
            $providerMatchId = (string) ($row['id'] ?? '');

            if ($providerMatchId === '') {
                continue;
            }

            $fixture = $this->findLocalFixture($row, $teamsByProviderId);

            if ($fixture === null) {
                $unmatched[] = sprintf(
                    'Match %s (%s)',
                    $providerMatchId,
                    (string) ($row['utcDate'] ?? 'unknown kickoff'),
                );

                continue;
            }

            if ($fixture->provider === $this->provider && $fixture->provider_match_id === $providerMatchId) {
                $skipped++;

                continue;
            }

            $fixture->update([
                'provider' => $this->provider,
                'provider_match_id' => $providerMatchId,
            ]);

            $linked++;
        }

        return compact('linked', 'skipped', 'unmatched');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function findLocalTeam(array $row): ?Team
    {
        $code = $this->normalizeTeamCode((string) ($row['tla'] ?? ''));

        if ($code === '') {
            return null;
        }

        return Team::query()->where('code', $code)->first();
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  Collection<string, int|string>  $teamsByProviderId
     */
    private function findLocalFixture(array $row, Collection $teamsByProviderId): ?Fixture
    {
        $kickoff = Carbon::parse((string) $row['utcDate'])->utc();
        $stage = $this->mapStage((string) ($row['stage'] ?? ''));

        if ($stage === null) {
            return null;
        }

        /** @var array<string, mixed>|null $homeTeam */
        $homeTeam = $row['homeTeam'] ?? null;
        /** @var array<string, mixed>|null $awayTeam */
        $awayTeam = $row['awayTeam'] ?? null;

        $homeTeamId = isset($homeTeam['id']) && $homeTeam['id'] !== null
            ? $teamsByProviderId->get((string) $homeTeam['id'])
            : null;
        $awayTeamId = isset($awayTeam['id']) && $awayTeam['id'] !== null
            ? $teamsByProviderId->get((string) $awayTeam['id'])
            : null;

        $query = Fixture::query()
            ->where('kickoff_at', $kickoff)
            ->where('stage', $stage);

        if ($homeTeamId !== null && $awayTeamId !== null) {
            $exact = Fixture::query()
                ->where('kickoff_at', $kickoff)
                ->where('stage', $stage)
                ->where('home_team_id', $homeTeamId)
                ->where('away_team_id', $awayTeamId)
                ->first();

            if ($exact !== null) {
                return $exact;
            }

            $fallback = Fixture::query()
                ->where('home_team_id', $homeTeamId)
                ->where('away_team_id', $awayTeamId)
                ->where('stage', $stage);

            if ($stage === FixtureStage::Group) {
                $groupCode = $this->parseGroupCode($row['group'] ?? null);

                if ($groupCode !== null) {
                    $fallback->where('group_code', $groupCode);
                }
            }

            return $fallback->first();
        }

        if ($stage === FixtureStage::Group) {
            $groupCode = $this->parseGroupCode($row['group'] ?? null);

            if ($groupCode !== null) {
                $query->where('group_code', $groupCode);
            }
        }

        return $query
            ->whereNull('home_team_id')
            ->whereNull('away_team_id')
            ->first()
            ?? $query->first();
    }

    private function normalizeTeamCode(string $code): string
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return '';
        }

        return $this->teamCodeAliases[$code] ?? $code;
    }

    private function mapStage(string $stage): ?FixtureStage
    {
        return match ($stage) {
            'GROUP_STAGE' => FixtureStage::Group,
            'LAST_32' => FixtureStage::Round32,
            'LAST_16' => FixtureStage::Round16,
            'QUARTER_FINALS' => FixtureStage::QuarterFinal,
            'SEMI_FINALS' => FixtureStage::SemiFinal,
            'THIRD_PLACE' => FixtureStage::ThirdPlace,
            'FINAL' => FixtureStage::Final,
            default => null,
        };
    }

    private function parseGroupCode(mixed $group): ?string
    {
        if (! is_string($group) || ! preg_match('/GROUP_([A-L])/', $group, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readTeamsPayload(): array
    {
        $payload = $this->readJson((string) config('football_data.teams_file'));

        /** @var list<array<string, mixed>> $teams */
        $teams = $payload['teams'] ?? [];

        return $teams;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readMatchesPayload(): array
    {
        $payload = $this->readJson((string) config('football_data.matches_file'));

        /** @var list<array<string, mixed>> $matches */
        $matches = $payload['matches'] ?? [];

        return $matches;
    }

    /**
     * @return array<string, mixed>
     */
    private function readJson(string $file): array
    {
        $path = rtrim($this->dataPath, '/').'/'.$file;
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException("Unable to read football-data file [{$path}].");
        }

        return json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
    }
}
