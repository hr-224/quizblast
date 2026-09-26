# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

QuizBlast is a Kahoot-style live quiz platform: Laravel 11 / PHP 8.2+, MySQL/MariaDB, Blade + vanilla CSS/JS. There is **no Node build step** and no `package.json`; static assets in `public/css/app.css`, `public/js/`, `public/audio/` are served as-is. Real-time uses Laravel Reverb (Pusher protocol) with HTTP polling as a fallback.

## Commands

```bash
composer install
php artisan migrate
php artisan reverb:start --host=127.0.0.1 --port=7001   # optional WebSocket server; app works without it via polling
./vendor/bin/phpunit                                     # all tests
./vendor/bin/phpunit tests/Feature/MultiCorrectScoringTest.php
./vendor/bin/phpunit --filter=test_method_name
./vendor/bin/pint                                        # code style (only linter installed)
```

- There is no `composer test` script; call phpunit directly.
- `tests/Unit/` exists only so bare `phpunit` runs. Until the redesign's Phase 2 lands, `MobileImprovementsTest::test_join_page_uses_clamp_heading_and_condensed_footer` is a known stale failure.
- `phpunit.xml` forces `DB_CONNECTION=mysql` with database `kahoot_testing` (the dev `.env` uses `kahoot`). Tests use `RefreshDatabase`, so that database must exist and is wiped on each run. Broadcasting is set to `log`, so no Reverb is needed in tests.
- Feature tests that POST typically call `withoutMiddleware(ValidateCsrfToken::class)` in `setUp`, and simulate a joined player by setting `session(['player_id_<PIN>' => $player->id])`.
- `install/index.php` is a browser-based installer (locked by `install/.installed`); `setup.sh` is the headless equivalent; `deploy.sh` is specific to the `quizblast.ultmods.com` server.

## Architecture

**Game lifecycle.** `games.status` moves `waiting → question → reviewing → question … → finished`. The host drives it through `GameController` (`launch`, `reveal`, `next`/`skip`, `end`, `final`); each transition updates the `Game` row and then `broadcast(new GameStateChanged(...))`. The current question is just an index (`games.current_question`) into `quiz->questions`, and the countdown is derived server-side from `question_started_at` (`Game::timeRemaining()`), not stored.

**Two state channels that must stay in sync.** Clients get game state both from Reverb events on the public channel `game.{pin}` (`app/Events/*`, all `ShouldBroadcastNow`) and from the polling endpoints `GET /api/game/{pin}/state` and `/players` (`GameController::state/players`). The event payload built in `GameStateChanged::broadcastWith()` and the JSON in `GameController::state()` are hand-duplicated and already differ slightly (e.g. `state()` includes `image_url`, `youtube_id`, `team_mode`, a top-10 leaderboard; the event has top-5). When adding a field to the question/answer payload, update both, and keep `is_correct` hidden (`null`) unless status is `reviewing`. Player views (`play/game.blade.php`) poll once immediately, then open a Pusher client and fall back to polling if it isn't connected.

**Two identity systems, no player auth guard.**
- Hosts are `User` records using the default `web` guard; the custom `auth` middleware alias is in `bootstrap/app.php`, and host-only actions check `$game->user_id === auth()->id()`. Public quizzes (`is_public`) can be hosted by any authenticated user.
- Players are anonymous: `PlayerController::join` creates a `GamePlayer` and stores its id in the session under **`player_id_{pin}`**. Every player endpoint (`answer`, `heartbeat`, `react`, `powerup`, `spy`, `leave`) authorizes by reading that session key. Optional `PlayerAccount`s (session key `player_account_id`) only attach lifetime stats; `LoginController` tries a `User` first, then a `PlayerAccount`, and the `/account/login` and `/account/register` GET routes just redirect to the unified `/login` and `/register`.

**Presence.** Players POST `/play/{pin}/heartbeat` every ~5s, which calls `GamePlayer::removeStale()`; players whose `last_seen_at` is older than 20s (and non-spectators) are deleted and `PlayerLeft` is broadcast. Note that `docs/architecture.md` says 60s and is partly out of date; trust the code. `play/*/leave` is exempt from CSRF (for `sendBeacon`).

**Scoring** lives entirely in `PlayerController::submitAnswer`: `points × partialFraction × speedFactor (1.0→0.5 over the time limit) × double_points multiplier × help penalty (fifty_fifty 0.5, spy 0.6)`, plus a streak bonus at 3+ consecutive fully-correct answers (+10% per step past 2, capped at +50%). Multi-correct questions yield a verdict of `true | 'partial' | false` (partial credit = `(correct − wrong)/totalCorrect`); a wrong `double_points` answer subtracts points. One `GameAnswer` row is written per selected answer, so aggregate by `game_player_id` + `question_id` when counting players rather than rows.

**Views/frontend.** Blade layouts in `resources/views/layouts/` (`app` and `game`); `host/`, `play/`, `spectator/` each contain their own inline JS for polling/Pusher/timers, so behavior changes usually mean editing the Blade file rather than a JS module. The `.env` broadcast setting is `BROADCAST_DRIVER=reverb`; the Reverb key/host values are rendered into the player views for the Pusher client.

**Design system.** `public/css/app.css` is one ordered stylesheet: fonts → tokens (`:root`) → base → components → effects → legacy → screens. Legacy token names (`--qb-*`, `--ans-red`…) and legacy font names (`Montserrat`, `Source Sans 3`) are aliases of the new ones and stay until every screen is migrated. Never use `@layer`, `:has()`, `color-mix()` or container queries (old school iPads); a test enforces it. Answer colors are always paired with a shape (`<x-answer-shape>` / `QB.Shapes.svg()`). Effects mode (`data-fx`) and mute live in `localStorage` and are driven by `public/js/qb-ui.js`. The redesign spec and per-phase plans are in `docs/superpowers/` (git-ignored).

## Repo notes

- Working-tree leftovers such as `app/Models/GamePlayer.php.save*` are editor backups, not part of the app.
- `docs/superpowers/` and `.superpowers/` are git-ignored planning/brainstorm artifacts from earlier sessions; `docs/*.md` are the user-facing docs.
