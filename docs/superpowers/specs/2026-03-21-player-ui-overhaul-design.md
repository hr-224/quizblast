# Player UI Overhaul — Design Spec

**Goal:** Evolve the player-facing lobby and in-game screens with polished visuals, live player count in the lobby, improved power-up UI, a more expressive locked-in screen, verdict cards on the review screen, and highlighted leaderboard rows.

**Scope:** Player-facing views only — `play/lobby.blade.php` and `play/game.blade.php`. No host views, no answer logic, no scoring changes.

**Backend changes required:** None. All needed data (`PlayerJoined` WS event with `nickname`+`count`, `PlayerLeft` WS event with `count`, `/api/game/{pin}/players` endpoint) already exist.

---

## 1. Player Lobby (`play/lobby.blade.php`)

### Current state
Simple centered screen: game icon, "You're in!" heading, nickname badge, three pulsing dots, status text.

### Changes

#### 1a. Player count pill
On page load, fetch `/api/game/{pin}/players` and display the returned `count` in a green pill:

```html
<div id="player-count-pill" class="lobby-count-pill">
  <span id="lobby-count">…</span>
  <span class="lobby-count-label">players joined</span>
</div>
```

#### 1b. Join ticker
A one-line text element below the pill shows the nickname of the last player who joined:

```html
<div id="join-ticker" class="lobby-join-ticker"></div>
```

When empty it takes no visual space (zero height, `visibility:hidden`). When a player joins it shows "✦ {nickname} just joined" for 3 seconds then fades out.

#### 1c. WS listeners
Subscribe to `game.{pin}` channel and bind:

- `player-joined` — `data.count` updates the pill count; `data.nickname` triggers the ticker
- `player-left` — `data.count` updates the pill count; ticker is cleared

#### 1d. Updated HTML structure

Replace the content `<div>` inside `@section('content')` with:

```blade
<div class="lobby-wrap">
  <div style="font-size:3.5rem">🎮</div>
  <h1 class="lobby-heading">You're in!</h1>
  <p class="lobby-sub">Waiting for the host to start…</p>

  <div id="player-count-pill" class="lobby-count-pill">
    <span id="lobby-count">…</span>
    <span class="lobby-count-label">players joined</span>
  </div>
  <div id="join-ticker" class="lobby-join-ticker" style="visibility:hidden">​</div>

  <div class="lobby-nick-badge">
    <p class="lobby-nick-label">Playing as</p>
    <p class="lobby-nick-name">{{ $player->nickname }}</p>
  </div>

  <div class="lobby-dots">
    <div class="pulse-dot" id="ws-dot"  style="background:var(--qb-muted)"></div>
    <div class="pulse-dot" id="ws-dot2" style="animation-delay:.2s;background:var(--qb-muted)"></div>
    <div class="pulse-dot" id="ws-dot3" style="animation-delay:.4s;background:var(--qb-muted)"></div>
    <span class="text-muted lobby-status-text" id="status-text">Connecting…</span>
  </div>
</div>
```

#### 1e. JS additions (inside the existing `@push('scripts')` block)

After the existing Pusher connect/disconnect/game-state-changed/player-kicked handlers, add:

```js
// Initial player count
fetch('/api/game/' + pin + '/players')
  .then(r => r.json())
  .then(data => {
    const el = document.getElementById('lobby-count');
    if (el && data.count !== undefined) el.textContent = data.count;
  }).catch(() => {});

// Live player join/leave
channel.bind('player-joined', function(data) {
  const countEl = document.getElementById('lobby-count');
  if (countEl && data.count !== undefined) countEl.textContent = data.count;
  showJoinTicker(data.nickname);
});

channel.bind('player-left', function(data) {
  const countEl = document.getElementById('lobby-count');
  if (countEl && data.count !== undefined) countEl.textContent = data.count;
});

var tickerTimer = null;
function showJoinTicker(nickname) {
  const el = document.getElementById('join-ticker');
  if (!el) return;
  if (tickerTimer) clearTimeout(tickerTimer);
  el.textContent = '✦ ' + nickname + ' just joined';
  el.style.visibility = 'visible';
  el.style.opacity = '1';
  tickerTimer = setTimeout(function() {
    el.style.opacity = '0';
    setTimeout(function() { el.style.visibility = 'hidden'; }, 400);
  }, 3000);
}
```

---

## 2. Game Screen (`play/game.blade.php`)

### 2a. Power-ups bar

**Current:** Three `<button class="btn btn-outline btn-sm">` elements with text labels.

**New:** Replace with icon+label card tiles:

```html
<div id="power-ups-bar" style="display:none;position:fixed;bottom:12px;left:0;right:0;z-index:25;justify-content:center;gap:8px;padding:6px 12px">
  <button class="pu-card pu-double" id="pu-double" onclick="usePowerUp('double_points')" title="2× points if correct, LOSE points if wrong">
    <div class="pu-icon">⚡</div>
    <div class="pu-label">2× pts</div>
  </button>
  <button class="pu-card pu-fifty" id="pu-fifty" onclick="usePowerUp('fifty_fifty')" title="-50% points if correct">
    <div class="pu-icon">✂️</div>
    <div class="pu-label">50/50</div>
  </button>
  <button class="pu-card pu-spy" id="pu-spy" onclick="usePowerUp('spy')" title="-40% points if correct">
    <div class="pu-icon">🕵️</div>
    <div class="pu-label">Spy</div>
  </button>
</div>
```

The existing `updatePowerUpButtons()` JS function sets `btn.disabled` and `btn.style.opacity`. It targets by ID (`pu-double`, `pu-fifty`, `pu-spy`) which are preserved — no JS change needed.

### 2b. Locked-in / answered screen

**Current:** Full-screen overlay with ⏳ icon, "Answer locked in!" heading, "Waiting for results…" subtext.

**New:** Replace the `#answered-msg` div with a version that shows the tapped answer block:

```html
<div id="answered-msg" style="display:none;position:fixed;inset:0;top:61px;z-index:20;background:var(--qb-darker);flex-direction:column;align-items:center;justify-content:center;gap:14px">
  <div id="answered-block" class="answered-block-preview"></div>
  <h3 style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1.4rem;text-transform:uppercase">Locked in!</h3>
  <p class="text-muted">Waiting for host to reveal…</p>
  <div class="answered-score-badge">
    <div class="answered-score-label">Current Score</div>
    <div class="answered-score-val" id="answered-score-display">0</div>
  </div>
</div>
```

In `submitAnswer()`, before hiding the answer grid, capture the tapped block and populate `#answered-block`:

```js
// After answerSubmitted = true, before hiding the grid:
// BG_COLORS and SHAPES are already defined in the outer IIFE — do NOT re-declare them.
const tappedIdx = currentAnswerIds.indexOf(answerIds[0]);
const ab = document.getElementById('answered-block');
if (ab && tappedIdx >= 0) {
  ab.style.background = BG_COLORS[tappedIdx];
  ab.textContent = SHAPES[tappedIdx];
}
const asd = document.getElementById('answered-score-display');
if (asd) asd.textContent = myScore.toLocaleString();
```

### 2c. Review screen verdict card

**Current:** Icon + title + points text stacked without visual grouping.

**New:** Wrap the verdict section in a colored card. Replace **lines 87–90 only** (the four verdict elements: `#review-icon`, `#review-title`, `#review-points`, `#review-streak`). **Do not touch line 91** (`#review-answers` div) — it is used by existing JS to render the answer blocks.

```html
<div id="review-verdict-card" class="review-verdict-card">
  <div style="font-size:3.2rem;margin-bottom:8px" id="review-icon">—</div>
  <h2 id="review-title" style="font-size:1.5rem;margin-bottom:4px">—</h2>
  <p class="text-muted" id="review-points">—</p>
  <p id="review-streak" style="display:none;font-weight:700;font-size:.88rem;margin-top:.3rem"></p>
</div>
```

In `handleStateChange()`, when `status === 'reviewing'`, set the card class based on result:

```js
const vc = document.getElementById('review-verdict-card');
if (vc) {
  vc.className = 'review-verdict-card ' +
    (lastAnswerCorrect === true  ? 'review-verdict-correct' :
     lastAnswerCorrect === false ? 'review-verdict-wrong'   : 'review-verdict-neutral');
}
```

### 2d. Leaderboard overlay — row highlights + "you" badge

**Current:** Plain list items, no visual distinction between self and others.

**New:** In `showLbOverlay()`, replace the template literal to add rank-based background classes and a "you" badge on the player's own row:

```js
list.innerHTML = (leaderboard || []).map((p, i) => {
  const rankClass = i === 0 ? 'lb-rank-1' : i === 1 ? 'lb-rank-2' : i === 2 ? 'lb-rank-3' : '';
  const isMe      = p.id == playerId;
  const youBadge  = isMe ? '<span class="lb-you-badge">you</span>' : '';
  const streak    = p.best_streak >= 3 ? ` <span class="lb-streak-badge">🔥${p.best_streak}</span>` : '';
  return `<li class="leaderboard-item ${rankClass}${isMe ? ' lb-me' : ''}">
    <div class="leaderboard-rank">${medals[i] || i+1}</div>
    <div class="leaderboard-name">${p.nickname}${streak}${youBadge}</div>
    <div class="leaderboard-score">${Number(p.score).toLocaleString()}</div>
  </li>`;
}).join('');
```

The leaderboard overlay already has `player-id` available as `playerId` in the outer closure.

---

## 3. CSS additions (`public/css/app.css`)

Append after existing rules:

```css
/* ── Lobby ── */
.lobby-wrap { min-height: calc(100vh - 64px); display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 2rem; gap: 0; }
.lobby-heading { font-size: 1.8rem; margin: 0.75rem 0 0.3rem; }
.lobby-sub { font-size: .82rem; color: var(--qb-muted); margin: 0 0 1rem; }
.lobby-count-pill { display: inline-flex; align-items: center; gap: 8px; background: rgba(38,137,12,.18); border: 1px solid rgba(38,137,12,.4); border-radius: 20px; padding: 5px 16px; font-size: .75rem; font-weight: 700; }
.lobby-count-pill #lobby-count { font-size: 1.15rem; font-weight: 900; color: var(--qb-green); }
.lobby-count-pill .lobby-count-label { color: rgba(255,255,255,.55); font-size: .7rem; }
.lobby-join-ticker { height: 18px; font-size: .7rem; color: rgba(255,255,255,.35); font-weight: 700; margin: 6px 0 0; transition: opacity .4s ease; }
.lobby-nick-badge { margin: 1.2rem 0 0; padding: 1rem 2.5rem; background: rgba(108,62,232,.18); border: 2px solid rgba(108,62,232,.4); border-radius: var(--radius); }
.lobby-nick-label { font-size: .72rem; color: var(--qb-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: .2rem; }
.lobby-nick-name  { font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 2rem; color: var(--qb-cyan); margin: 0; }
.lobby-dots { display: flex; align-items: center; gap: .75rem; margin-top: 1.2rem; }
.lobby-status-text { font-size: .88rem; }

/* ── Power-up cards ── */
.pu-card { background: rgba(255,255,255,.07); border: 1.5px solid rgba(255,255,255,.15); border-radius: 10px; color: #fff; padding: 7px 16px; text-align: center; cursor: pointer; font-family: inherit; min-width: 68px; transition: opacity .2s, transform .1s; }
.pu-card:active { transform: scale(.95); }
.pu-card:disabled { opacity: .3; cursor: default; }
.pu-double { background: rgba(255,208,0,.1);   border-color: rgba(255,208,0,.35); }
.pu-fifty  { background: rgba(0,210,255,.1);   border-color: rgba(0,210,255,.35); }
.pu-spy    { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.12); }
.pu-icon   { font-size: 1.1rem; line-height: 1; margin-bottom: 2px; pointer-events: none; }
.pu-label  { font-size: .6rem; font-weight: 800; text-transform: uppercase; letter-spacing: .3px; pointer-events: none; }
.pu-double .pu-label { color: #ffd000; }
.pu-fifty  .pu-label { color: #00d2ff; }
.pu-spy    .pu-label { color: rgba(255,255,255,.55); }

/* ── Answered/locked-in screen ── */
.answered-block-preview { width: 90px; height: 90px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 3rem; outline: 4px solid rgba(255,255,255,.55); outline-offset: -4px; box-shadow: 0 0 28px rgba(0,0,0,.4); }
.answered-score-badge { background: rgba(108,62,232,.2); border: 2px solid rgba(108,62,232,.4); border-radius: var(--radius); padding: .75rem 2rem; }
.answered-score-label { font-size: .62rem; color: var(--qb-muted); text-transform: uppercase; letter-spacing: .4px; margin-bottom: .2rem; }
.answered-score-val   { font-family: 'Montserrat', sans-serif; font-weight: 900; font-size: 1.6rem; color: var(--qb-yellow); }

/* ── Review verdict card ── */
.review-verdict-card { width: 100%; border-radius: 12px; padding: 18px 16px 14px; margin-bottom: 1rem; display: flex; flex-direction: column; align-items: center; }
.review-verdict-correct { background: rgba(38,137,12,.15);  border: 2px solid rgba(38,137,12,.4); }
.review-verdict-wrong   { background: rgba(226,27,60,.12);   border: 2px solid rgba(226,27,60,.35); }
.review-verdict-neutral { background: rgba(255,255,255,.05); border: 2px solid rgba(255,255,255,.1); }
#review-title.correct-title { color: var(--qb-green); }
#review-title.wrong-title   { color: #ff4d6a; }

/* ── Leaderboard row highlights ── */
.lb-rank-1 { background: rgba(255,208,0,.12)    !important; border-color: rgba(255,208,0,.3)    !important; }
.lb-rank-2 { background: rgba(192,192,220,.08)  !important; border-color: rgba(192,192,220,.2)  !important; }
.lb-rank-3 { background: rgba(205,127,50,.08)   !important; border-color: rgba(205,127,50,.2)   !important; }
.lb-me     { background: rgba(108,62,232,.18)   !important; border-color: rgba(108,62,232,.4)   !important; }
.lb-you-badge { font-size: .6rem; font-weight: 700; background: var(--qb-purple); color: #fff; border-radius: 10px; padding: 1px 6px; letter-spacing: .3px; text-transform: uppercase; margin-left: 4px; }
.lb-streak-badge { font-size: .72rem; color: #ff8c00; margin-left: 3px; }
```

---

## 4. What does NOT change

- Answer grid colors, shapes, layout, or interaction logic
- Scoring, streak calculation, power-up effects
- Host views (dashboard, host lobby, host question)
- Any backend routes, controllers, or models
- The reading countdown overlay

---

## 5. Files to touch

| File | Change |
|------|--------|
| `resources/views/play/lobby.blade.php` | New layout HTML; player count pill + ticker; WS listeners for `player-joined` / `player-left`; initial count fetch |
| `resources/views/play/game.blade.php` | Power-up bar HTML; locked-in screen with block preview; review verdict card; leaderboard row highlights in `showLbOverlay()` |
| `public/css/app.css` | All new visual rules (lobby, power-up cards, answered screen, verdict card, leaderboard highlights) |
