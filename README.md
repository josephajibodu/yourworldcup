# YourWorldCup

Daily World Cup predictions, a living bracket, and skill-ranked leaderboards.

## Local setup

```bash
composer setup
```

That installs dependencies, creates `.env`, runs migrations, and builds frontend assets.

For day-to-day development (app server, queue worker, logs, Vite):

```bash
composer dev
```

The app is served by [Laravel Herd](https://herd.laravel.com/) at `https://yourworldcup.test` when using the default `.env.example`.

## Tournament data

### `php artisan worldcup:import`

Loads teams, stadiums, fixtures, and prediction markets from `database/data/*.json`.

What it does:

1. Seeds enabled prediction markets (`PredictionMarketSeeder`).
2. Imports stadiums, teams, and all 104 fixtures via `WorldCupImporter`.
3. Attaches every enabled market to every fixture.
4. Imports knockout **bracket slots** (`bracket_slots`) from each fixture's `home_team_label` / `away_team_label` (e.g. `2A`, `3rd D/E/I/J/L`, `W74`).

Safe to re-run after JSON updates (played scores, finished flags). Existing rows are updated by external id, not duplicated.

```bash
php artisan worldcup:import
```

Run this after refreshing tournament JSON, and on first deploy.

## Recording match results

### `php artisan fixture:result`

Sets final scores on fixtures by tournament match id (`external_id`, e.g. `73` for M73). Marks the fixture `final`, stores `home_score` / `away_score`, and sets `winner_team_id` when both teams are known and the match is not a draw.

**Single match (interactive):**

```bash
php artisan fixture:result 73
```

You will be prompted for home and away scores. The command prints the matchup before asking.

**Bulk JSON:**

```bash
php artisan fixture:result --json='[
  {"id": 1, "home": 2, "away": 1},
  {"id": 2, "home_score": 0, "away_score": 0},
  {"id": 73, "home": 1, "away": 1}
]'
```

Each object needs:

| Field | Required | Notes |
|-------|----------|--------|
| `id` | yes | Match external id (string or number) |
| `home` / `home_score` | yes | Home goals (0–30) |
| `away` / `away_score` | yes | Away goals (0–30) |

**Bulk file:**

```bash
php artisan fixture:result --file=storage/app/results.json
```

The file uses the same JSON array format as `--json`.

**Settle after recording:**

```bash
php artisan fixture:result 73 --settle
php artisan fixture:result --json='[...]' --settle
```

`--settle` runs `predictions:settle` when finished, which scores predictions and queues bracket resolution.

## Predictions & scoring

### `php artisan predictions:settle`

Finds finished or void fixtures that still have open markets, settles each market from match results, scores user predictions, and updates the leaderboard.

After each settled fixture it also queues **`ResolveBracketSlots`** (see below) so the bracket can advance automatically.

```bash
php artisan predictions:settle
```

Typical flow during the tournament:

1. Record results with `fixture:result` (interactive or bulk JSON), **or** update `database/data/football.matches.json` and run `worldcup:import`.
2. `php artisan predictions:settle` (or pass `--settle` on `fixture:result`).

## Bracket resolution

Knockout fixtures start with placeholder slots in `bracket_slots`. When group or knockout results are known, slots resolve to real teams and `fixtures.home_team_id` / `away_team_id` are filled in.

### Automatic resolution

When `predictions:settle` finishes a fixture, it dispatches the **`ResolveBracketSlots`** queue job. Run a queue worker in dev (`composer dev` includes one) or production so jobs process:

```bash
php artisan queue:work
```

### `php artisan bracket:resolve`

Manual trigger for the same logic (useful for backfills or debugging):

| Option | When to use |
|--------|-------------|
| `--group=A` | Group A has played all three matchdays. Fills **1A** and **2A** slots onto R32 fixtures. |
| `--best-thirds` | **All 12 groups** are complete. Ranks third-place teams and fills **3rd …** slots (via `BestThirdQualifier`). |
| `--fixture=73` | Knockout match 73 is final. Fills **W73** / **RU73** feeder slots on later rounds. |
| `--all` | Resolve every slot that can be filled right now. |

Examples:

```bash
php artisan bracket:resolve --group=H
php artisan bracket:resolve --best-thirds
php artisan bracket:resolve --fixture=101
php artisan bracket:resolve --all
```

**Group vs best-third timing:** resolve winner/runner-up per group as soon as that group's table is final (all six group matches played). Best-third slots need every group finished first, because FIFA ranks all twelve third-place teams together.

Slot labels on the bracket UI:

- `1A` / `2B` — group winner / runner-up
- `3rd D/E/I/J/L` — best third-place team from one of those groups
- `W74` / `RU101` — winner / loser of match 74 or 101

Third-place ranking uses `PointsBestThirdQualifier` by default (points, then GD, then GF). Swap the `BestThirdQualifier` binding in `AppServiceProvider` for full FIFA tiebreak rules when needed.

## Quality checks

```bash
composer test          # Pint, PHPStan, Pest
composer ci:check      # Frontend lint/format/types + tests
npm run dev            # Vite only
npm run build          # Production frontend build
```

## Environment

| Variable | Purpose |
|----------|---------|
| `TURNSTILE_ENABLED` | Cloudflare Turnstile on login, register, and password forms (`false` by default). |
| `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` | Turnstile keys when enabled. |
| `ADMIN_EMAIL` | Site admin; can access dashboard and settings (`config/app.php`). |
