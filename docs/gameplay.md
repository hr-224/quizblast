# Gameplay Guide

## For Hosts

### Creating a quiz

1. Register a host account at `/register`
2. From the **Dashboard**, click **New Quiz**
3. Give your quiz a title, description, and category
4. Add questions one by one:
   - Enter the question text
   - Add 2–4 answer options (check the correct one — or multiple for multi-correct questions)
   - Optionally add an image URL or YouTube link
   - Set the time limit (5–120 seconds)
5. Optionally add a **banner image** in the quiz settings (upload a JPG, PNG or WebP up to 2 MB, or paste an image URL — wide images, about 3:1, look best). It replaces the generated cover on library cards and heads the quiz's preview page
6. Save — your quiz is ready to host

### Starting a game

1. Dashboard → find your quiz → click **Host**
2. A unique **6-digit PIN** is generated
3. Share the PIN (or the site URL) with players
4. Watch players appear in the lobby in real time
5. When ready, click **Launch Game**

### During the game

- Each question is shown on the host screen
- Players answer on their own devices
- The **live bar chart** shows answer distribution as responses come in
- When time is up (or all players have answered), click **Reveal Answers**
- See who got it right, points earned, and current streaks
- Click **Next Question** to continue

### If a player disconnects

Players keep their score, streak and power-ups when they disconnect mid-game, and can rejoin on their own (see [Rejoining a game](#rejoining-a-game)). If a player lost their connection *and* can't rejoin — for example they cleared their browser data or switched devices while logged out — open **👥 Players** on the host screen and tap **Let back in** next to their name. They then have 5 minutes to rejoin using their original nickname; the release works once.

### Ending the game

- After the last question, a **Final Leaderboard** is shown with gold/silver/bronze podium
- Game history is saved to your dashboard

---

## For Players

### Joining a game

1. Open the site on any browser (phone, tablet, PC)
2. Enter the **6-digit PIN** given by your host
3. Pick a **nickname** — this is how you appear on the leaderboard
4. Wait in the lobby until the host starts the game

### Rejoining a game

If you lose your connection, close the tab, or your phone sleeps mid-game, you can get back in while the game is still running (you'll miss any questions answered before you return, but keep your score):

- **Same browser:** open the site again and enter the PIN and your **original nickname**. Your browser remembers a private rejoin key for the game, so this just works.
- **Different device or cleared data:** if you were logged in when you joined, log in again and rejoin with your nickname. Otherwise ask the host to tap **Let back in** for you (see above).
- Rejoining on a new device signs the old one out, so only one device controls a player at a time.
- You'll see "Welcome back! You missed N questions." when you're back in. Rejoining works during a question or while answers are being reviewed, not after the game has finished.

### Answering questions

- Each question has 2–4 coloured shape buttons
- Tap/click the answer you think is correct
- **Speed matters** — faster correct answers earn more points
- Once you tap, your answer is locked in — you can't change it

### Scoring

| Outcome | Points |
|---|---|
| Correct answer (first to answer) | 1000 |
| Correct answer (slowest) | ~500 |
| Wrong answer | 0 |
| Time expired without answering | 0 |
| Streak bonus (3+ correct in a row) | +bonus per question |

Exact points depend on your response time relative to the time limit.

---

## Power-ups

Each player has three one-use power-ups, available from the bottom bar during questions.

| Power-up | Icon | Effect |
|---|---|---|
| **Double Points** | ⚡ | If correct: earn 2× points. If wrong: lose points |
| **Fifty-Fifty** | ✂️ | Removes two wrong answers from the grid. Correct answer earns -50% points |
| **Spy** | 🕵️ | See which answer the most players chose. Correct answer earns -40% points |

Power-ups are consumed when activated — use them wisely.

---

## Streaks

Answering multiple questions correctly in a row builds a **streak**:

- 3+ correct answers in a row earns a streak bonus on each subsequent correct answer
- Your best streak is tracked and shown on the final leaderboard
- A wrong answer or timeout resets your streak to zero

---

## Reactions

During gameplay, players can tap reaction buttons to send emoji to everyone:

- 🎉 🔥 😱 👏 💀

Reactions appear briefly on the host screen and in the player game view.

---

## Spectator Mode

If spectator mode is enabled on a game, viewers can watch without playing:

1. Go to `/spectate/{pin}`
2. Watch the question, live answer bar chart, and leaderboard without participating

---

## Accounts and stats (optional)

There is one kind of account. Anyone can register at `/register` and both host quizzes and track their own player stats:

- Playing without an account is always allowed — join with just a PIN and nickname
- If you're **logged in when you join** a game, your score, games played and wins are added to your account when the game finishes (each game is counted once)
- View your stats at `/account/stats`: total score, games played, wins
- The older `/account/register` and `/account/login` URLs still work and redirect to the main pages

---

## Browsing the public library

`/library` lists every public quiz:

- **Search** by title, description, category or tags
- **Category chips** filter by category (with a count each); **sort** by Newest, Most played or Most questions
- Without any filter, the page opens with a **Popular** strip (the most-played quizzes, or the newest if nothing has been played yet) and a row for each larger category
- Each card has **Preview** (see the questions, without answers) and **Host this quiz**. Logged-in users go straight to hosting; visitors are sent to sign up

"Plays" count finished games of a quiz.
