# ⚡ QuizBlast — Kahoot-Style Live Quiz Platform

A full-stack Laravel 11 live quiz game, inspired by Kahoot. Hosts create quizzes and launch live games with a PIN; players join on any device and answer in real time.

---

## Features

| Feature | Details |
|---|---|
| 🎯 Quiz Builder | Create quizzes with 2–4 answer options per question |
| ⚡ Live Hosting | Generate a 6-digit PIN and host live games |
| 📱 Player Join | Players join from any browser — no account needed |
| ⏱ Timed Questions | 5–120 second configurable timers per question |
| 🏆 Speed Scoring | Faster correct answers earn more points |
| 📊 Live Bar Chart | Host sees real-time answer distribution |
| 🥇 Leaderboard | Podium + full ranking after every game |
| 🔒 Auth | Host accounts (register / login) |
| 🗄 SQLite/MySQL | Works out of the box with SQLite |

---

## Quick Start

### Requirements
- PHP 8.2+
- Composer
- SQLite (built into PHP) **or** MySQL/MariaDB

### Setup

```bash
git clone <repo>
cd quizblast
bash setup.sh
```

Then start the server:

```bash
php artisan serve
```

Open **http://localhost:8000** in your browser.

### Demo Account

After running `setup.sh`:

```
Email:    demo@quizblast.app
Password: password
```

---

## Using MySQL / MariaDB

Edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=quizblast
DB_USERNAME=root
DB_PASSWORD=yourpassword
```

Then run:

```bash
php artisan migrate --seed
```

---

## How It Works

### Hosting a Game
1. Log in → Dashboard → **Host** on any quiz
2. Share the 6-digit PIN with players
3. Click **Start Game** when players are ready
4. After each question, click **Reveal Answers**, then **Next Question**
5. Final podium is shown at the end

### Joining a Game
1. Go to the site root (`/`)
2. Enter the PIN and pick a nickname
3. Answer each question as fast as possible — speed earns bonus points!

---

## Project Structure

```
app/
  Http/
    Controllers/
      Auth/         LoginController, RegisterController
      DashboardController
      QuizController      (CRUD + question management)
      GameController      (host flow + polling API)
      PlayerController    (player flow + answer submission)
    Middleware/
      Authenticate.php
  Models/
    User, Quiz, Question, Answer, Game, GamePlayer, GameAnswer

database/
  migrations/       4 migration files
  seeders/          DatabaseSeeder (demo quiz)

resources/views/
  layouts/          app.blade.php, game.blade.php
  auth/             login, register
  dashboard/        index
  quizzes/          create, edit
  host/             lobby, question, final
  play/             join, lobby, game, final

public/
  css/app.css       Full custom design system
routes/
  web.php           All routes
```

---

## Polling Architecture

Real-time updates use **simple HTTP polling** (no WebSockets required):

- `/api/game/{pin}/state` — returns current question, timer, answer options, leaderboard
- `/api/game/{pin}/players` — returns player list + count
- Players and host poll every 1.5 seconds

This works on any shared hosting with no extra infrastructure.

---

## License

MIT — free to use and modify.
