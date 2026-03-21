# ⚡ QuizBlast

**A self-hosted, Kahoot-style live quiz platform built with Laravel 11.**

Hosts create quizzes and launch live games with a 6-digit PIN. Players join instantly from any browser — no app, no account required. Real-time updates are powered by WebSockets (Laravel Reverb) with a polling fallback for firewalled networks.

![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-11-red?logo=laravel)
![License](https://img.shields.io/badge/license-MIT-green)

---

## Features

| | Feature | Details |
|---|---|---|
| 🎯 | **Quiz Builder** | Create quizzes with 2–4 answers, images, YouTube embeds, multi-correct questions |
| ⚡ | **Live Hosting** | 6-digit PIN, real-time player join/leave, host dashboard |
| 📱 | **Any Device** | Players join from any browser — no install, no account needed |
| ⏱ | **Timed Questions** | Per-question timers from 5–120 seconds |
| 🏆 | **Speed Scoring** | Faster correct answers earn more points |
| 💥 | **Power-ups** | Double Points, Fifty-Fifty, and Spy modifiers |
| 📊 | **Live Bar Chart** | Host sees answer distribution as players respond |
| 🥇 | **Leaderboard** | Podium + full ranking after every question |
| 🎊 | **Streak Bonuses** | Consecutive correct answers earn bonus points |
| 😊 | **Reactions** | Players send emoji reactions during gameplay |
| 👥 | **Spectator Mode** | Watch a game live without playing |
| 🔒 | **Host Accounts** | Register, log in, manage your quiz library |
| 📚 | **Public Library** | Browse and play public quizzes from other hosts |
| 🗄 | **SQLite / MySQL** | Works out of the box — no database setup needed |

---

## Quick Start (60 seconds)

**Requirements:** PHP 8.2+, Composer

```bash
git clone https://github.com/hr-224/quizblast.git
cd quizblast
bash setup.sh
php artisan serve
```

Open **http://localhost:8000** and log in with the demo account:

```
Email:    demo@quizblast.app
Password: password
```

> Real-time WebSocket features require running the Reverb server. See [docs/websockets.md](docs/websockets.md).

---

## Documentation

| Guide | Description |
|---|---|
| [Installation](docs/installation.md) | Full setup walkthrough, MySQL, WebSockets |
| [Configuration](docs/configuration.md) | Every `.env` variable explained |
| [Gameplay Guide](docs/gameplay.md) | Hosting a game, joining, scoring, power-ups |
| [WebSockets](docs/websockets.md) | Running Laravel Reverb locally and in production |
| [Self-Hosting](docs/self-hosting.md) | Deploy to your own server (nginx, SSL, systemd) |
| [Architecture](docs/architecture.md) | Tech stack, database schema, real-time design |

---

## How It Works

### Hosting a game
1. Log in → Dashboard → press **Host** on any quiz
2. Share the **6-digit PIN** shown on screen
3. Wait for players to join, then click **Launch**
4. After each question: **Reveal Answers** → **Next Question**
5. Final podium is shown at the end

### Joining as a player
1. Go to the site URL (no account needed)
2. Enter the PIN + pick a nickname
3. Answer each question as fast as you can — speed earns bonus points!

---

## Tech Stack

- **Backend:** Laravel 11, PHP 8.2+
- **Database:** SQLite (default) or MySQL / MariaDB
- **Real-time:** [Laravel Reverb](https://reverb.laravel.com/) (WebSockets) + HTTP polling fallback
- **Frontend:** Blade templates, vanilla CSS, vanilla JS — no Node.js build step
- **Testing:** PHPUnit 11

---

## License

MIT — free to use, modify, and self-host.
