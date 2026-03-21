# Architecture

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.2+ |
| Framework | Laravel 11 |
| Database | MySQL · MariaDB |
| Real-time | Laravel Reverb (WebSockets) |
| Frontend | Blade templates · Vanilla CSS · Vanilla JS |
| Testing | PHPUnit 11 |
| Build pipeline | None — CSS/JS served directly, no Node.js required |

---

## Project Structure

```
app/
├── Events/                 # Broadcast events (WebSocket messages)
│   ├── GameStateChanged.php
│   ├── PlayerJoined.php
│   ├── PlayerLeft.php
│   ├── PlayerKicked.php
│   ├── AnswerCountUpdated.php
│   ├── EmojiReacted.php
│   └── PowerUpUsed.php
├── Http/
│   ├── Controllers/
│   │   ├── Auth/           # Host login/register, player login/register
│   │   ├── DashboardController.php
│   │   ├── GameController.php    # Host game flow + polling API
│   │   ├── LibraryController.php
│   │   ├── PlayerController.php  # Player join, answer, heartbeat
│   │   ├── QuizController.php    # Quiz CRUD
│   │   └── SpectatorController.php
│   └── Middleware/
│       └── Authenticate.php
└── Models/
    ├── User.php            # Host accounts
    ├── Quiz.php
    ├── Question.php
    ├── Answer.php
    ├── Game.php
    ├── GamePlayer.php
    ├── GameAnswer.php
    ├── PlayerAccount.php   # Optional player accounts
    └── GameReaction.php

database/migrations/        # 8 migration files (schema history)
resources/views/
├── layouts/                # app.blade.php, game.blade.php
├── auth/                   # login, register, player-login, player-register
├── dashboard/
├── quizzes/                # create, edit
├── host/                   # lobby, question, final
├── play/                   # join, lobby, game, final
├── spectator/
└── library/

public/
├── css/app.css             # Single-file custom design system
├── js/
│   ├── confetti.js         # Canvas confetti
│   └── sounds.js           # Web Audio API sound effects
└── audio/                  # Sound effect files

routes/
├── web.php                 # All HTTP routes
└── console.php             # Artisan scheduler

tests/Feature/              # PHPUnit feature tests
```

---

## Database Schema

### Core Tables

```
users               — Host accounts (name, email, password)
quizzes             — Quiz metadata (title, description, category, is_public)
questions           — Questions per quiz (text, time_limit, points, order)
answers             — Answer options per question (text, is_correct, order)
```

### Game Session Tables

```
games               — Live game instance (pin, status, current_question, quiz_id)
game_players        — Players in a game (nickname, score, streak, power_ups JSON)
game_answers        — Each player's answer per question (response_time_ms, points_earned)
game_reactions      — Emoji reactions during a game
```

### Status Flow

```
games.status:
  waiting  →  question  →  reviewing  →  question  →  ...  →  finished
```

### Optional Tables

```
player_accounts     — Optional player identity (email, total_score, games_played, wins)
sessions            — Laravel database session storage
```

---

## Real-Time Architecture

QuizBlast uses a **dual-channel** approach:

```
                ┌─────────────────────────────────────────┐
                │              Browser (player)            │
                │                                          │
                │  WebSocket ──────────────────────────┐  │
                │  (Reverb)   instant push events      │  │
                │                                      │  │
                │  HTTP Poll  /api/game/{pin}/state  ◄─┘  │
                │  (1.5s)     fallback if WS drops        │
                └─────────────────────────────────────────┘
                                     │
                             nginx (port 443)
                                     │
                   ┌─────────────────┴──────────────────┐
                   │                                    │
              PHP-FPM                          Reverb server
         (Laravel app logic)             (WebSocket server :7001)
                   │                                    │
                   └─────────── Laravel app ────────────┘
                                     │
                              SQLite / MySQL
```

### WebSocket Events (Reverb)

All events broadcast on the `game.{pin}` channel:

| Event | Payload | Trigger |
|---|---|---|
| `game-state-changed` | `{status, question, answers, timer, leaderboard}` | Host advances game |
| `player-joined` | `{id, nickname, count}` | Player joins lobby |
| `player-left` | `{count, players}` | Player leaves or times out |
| `player-kicked` | `{player_id}` | Host kicks a player |
| `answer-count-updated` | `{counts[]}` | Any player submits an answer |
| `emoji-reacted` | `{emoji, nickname}` | Player sends a reaction |
| `power-up-used` | `{type, nickname}` | Player activates a power-up |

### Polling Fallback (API)

| Endpoint | Rate limit | Returns |
|---|---|---|
| `GET /api/game/{pin}/state` | 60/min | Full game state (status, question, timer, leaderboard) |
| `GET /api/game/{pin}/players` | 60/min | Player list + count |

Players poll every **1.5 seconds**. Hosts poll every **1.5 seconds** for the answer bar chart.

### Presence / Heartbeat

- Players call `POST /play/{pin}/heartbeat` every 5 seconds
- Players with `last_seen_at` older than **60 seconds** are automatically removed
- This fires a `PlayerLeft` event, updating the lobby count for remaining players

---

## Scoring System

```
base_points = question.points  (default 1000)

time_bonus = base_points × (remaining_time / time_limit) × 0.5

points_earned = base_points + time_bonus
              = 500 to 1000 depending on speed
```

**Power-up modifiers:**
- `double_points`: `× 2` if correct, `-base_points` if wrong
- `fifty_fifty`: `× 0.5` if correct
- `spy`: `× 0.6` if correct

**Streak bonus:** Each question in a streak of 3+ adds a fixed bonus to `points_earned`.

---

## CSS Design System

`public/css/app.css` is a single-file design system with no build pipeline:

```css
/* CSS custom properties (design tokens) */
--qb-bg:      #0e0b1e   /* page background */
--qb-darker:  #080612   /* deeper dark */
--qb-purple:  #6c3ee8   /* primary brand */
--qb-cyan:    #00d2ff   /* accent */
--qb-yellow:  #ffd000   /* highlight */
--qb-green:   #26890c   /* correct/success */
--qb-red:     #e21b3c   /* wrong/danger */
--qb-muted:   rgba(255,255,255,0.45)
--radius:     10px
```

Fonts are loaded from Google Fonts: **Montserrat** (headings, scores), **Source Sans 3** (body).
