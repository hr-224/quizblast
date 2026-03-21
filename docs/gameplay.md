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
5. Save — your quiz is ready to host

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

## Player Accounts (optional)

Players can optionally create a **player account** to track their stats across games:

- Register at `/account/register`
- Log in at `/account/login`
- View your stats at `/account/stats`: total score, games played, wins
