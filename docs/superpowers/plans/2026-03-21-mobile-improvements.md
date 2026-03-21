# Mobile Improvements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the player-facing navbar, in-game topbar, and join page work correctly on phones down to 320px without changing any desktop layout.

**Architecture:** Three independent UI changes — (1) hamburger nav in `layouts/app.blade.php`, (2) two-row game topbar in `layouts/game.blade.php`, (3) fluid type and condensed footer on `play/join.blade.php` — all backed by new CSS rules appended to `public/css/app.css`. No backend changes. Each task is independently testable by resizing a browser window.

**Tech Stack:** Laravel 11 Blade templates, vanilla CSS (no preprocessor), vanilla JS (no framework). Tests use PHPUnit + Laravel HTTP test helpers.

---

## File Map

| File | What changes |
|------|-------------|
| `resources/views/layouts/app.blade.php` | Add `id` to `.navbar-nav`; add hamburger button; add `#nav-dropdown` inside `<nav>`; add toggle JS |
| `resources/views/layouts/game.blade.php` | Replace anonymous topbar divs with named classes; strip layout rules from inline `<style>` |
| `resources/views/play/join.blade.php` | `clamp()` heading, smaller PIN font, condensed footer |
| `public/css/app.css` | Hamburger + dropdown styles; game topbar flex+grid responsive rules |
| `tests/Feature/MobileImprovementsTest.php` | New test file — structural assertions for the three views |

---

## How to run tests

```bash
./vendor/bin/phpunit tests/Feature/MobileImprovementsTest.php --colors=always
```

All tests should pass before and after each task (no regressions).

---

## Task 1: CSS foundations — hamburger and nav-dropdown styles

**Files:**
- Modify: `public/css/app.css` (append to end, after line 132)

### Context
`app.css` currently ends with a single-line `@media(max-width:600px)` block on line 132. The new CSS must be appended after that line. The hamburger button starts hidden (`display:none`) and is shown only on mobile via a media query.

- [ ] **Step 1: Append hamburger + dropdown CSS to `public/css/app.css`**

Open `public/css/app.css` and append the following after line 132 (the existing `@media(max-width:600px)` block):

```css

/* ── Mobile nav: hamburger button ── */
.hamburger-btn {
  display: none;
  background: rgba(0,0,0,0.3);
  border: 1px solid rgba(255,255,255,0.2);
  border-radius: var(--radius);
  color: #fff;
  padding: 6px 11px;
  font-size: 1.1rem;
  cursor: pointer;
  line-height: 1;
}

/* ── Mobile nav: dropdown panel ── */
.nav-dropdown {
  background: var(--qb-purple);
  border-bottom: 3px solid rgba(0,0,0,0.3);
}
.nav-dropdown-link {
  display: block;
  width: 100%;
  padding: 13px 1.5rem;
  color: rgba(255,255,255,0.85);
  font-size: 0.88rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
  background: none;
  text-align: left;
  cursor: pointer;
}
.nav-dropdown-link:last-child { border-bottom: none; }
.nav-dropdown-btn { font-family: inherit; border: none; }

@media (max-width: 767px) {
  #nav-links { display: none; }
  .hamburger-btn { display: block; }
}
@media (min-width: 768px) {
  #nav-dropdown { display: none !important; }
}
```

- [ ] **Step 2: Verify the file ends correctly**

```bash
tail -20 public/css/app.css
```

Expected: the file ends with the `@media (min-width: 768px)` closing brace. No extra blank lines or syntax errors.

- [ ] **Step 3: Commit**

```bash
git add public/css/app.css
git commit -m "style: add hamburger and nav-dropdown CSS foundations"
```

---

## Task 2: Navbar HTML — hamburger button and dropdown

**Files:**
- Modify: `resources/views/layouts/app.blade.php`

### Context

Current `app.blade.php` structure (lines 15–43):
```html
<nav class="navbar">
  <div class="navbar-inner">
    <a href="..." class="navbar-brand">...</a>
    <div class="navbar-nav">          ← line 18, add id="nav-links" here
      ... nav links ...
    </div>
                                      ← add hamburger-btn here (last child of .navbar-inner)
  </div>                              ← line 42, insert dropdown AFTER this
</nav>                                ← line 43
```

Three changes: (a) add `id` to existing div, (b) add hamburger button, (c) add dropdown between `</div>` and `</nav>`.

- [ ] **Step 1: Write a structural test first**

Create `tests/Feature/MobileImprovementsTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileImprovementsTest extends TestCase
{
    /** Navbar layout contains hamburger button and dropdown */
    public function test_navbar_has_hamburger_and_dropdown(): void
    {
        $response = $this->get(route('play.join'));

        $response->assertStatus(200);
        $response->assertSee('id="nav-toggle"', false);
        $response->assertSee('id="nav-dropdown"', false);
        $response->assertSee('class="hamburger-btn"', false);
        $response->assertSee('id="nav-links"', false);
    }
}
```

- [ ] **Step 2: Run the test — expect FAIL**

```bash
./vendor/bin/phpunit tests/Feature/MobileImprovementsTest.php --colors=always
```

Expected: FAIL — `assertSee` fails because `nav-toggle` / `nav-dropdown` are not in the HTML yet.

- [ ] **Step 3: Add `id="nav-links"` to the existing `.navbar-nav` div**

In `resources/views/layouts/app.blade.php`, change line 18 from:
```html
    <div class="navbar-nav">
```
to:
```html
    <div class="navbar-nav" id="nav-links">
```

- [ ] **Step 4: Add the hamburger button inside `.navbar-inner`**

In `app.blade.php`, add the following line immediately before `</div>` that closes `.navbar-inner` (currently before line 42):

```html
    <button id="nav-toggle" class="hamburger-btn" aria-label="Menu" aria-expanded="false" aria-controls="nav-dropdown">☰</button>
```

The result around that area should look like:
```html
    </div>{{-- /.navbar-nav #nav-links --}}
    <button id="nav-toggle" class="hamburger-btn" aria-label="Menu" aria-expanded="false" aria-controls="nav-dropdown">☰</button>
  </div>{{-- /.navbar-inner --}}
</nav>
```

- [ ] **Step 5: Add the nav dropdown inside `<nav>`, between `</div>` and `</nav>`**

Insert the following block after the `</div>` that closes `.navbar-inner` and before `</nav>`:

```html
  <div id="nav-dropdown" class="nav-dropdown" hidden>
    <a href="{{ route('library') }}" class="nav-dropdown-link">Library</a>
    @auth
      <a href="{{ route('dashboard') }}" class="nav-dropdown-link">Dashboard</a>
      <a href="{{ route('quizzes.create') }}" class="nav-dropdown-link">New Quiz</a>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="nav-dropdown-link nav-dropdown-btn">Log out</button>
      </form>
    @else
      <a href="{{ route('play.join') }}" class="nav-dropdown-link">Join Game</a>
      @if(session('player_account_id'))
        <a href="{{ route('player.stats') }}" class="nav-dropdown-link">My Stats</a>
        <form method="POST" action="{{ route('player.logout') }}">
          @csrf
          <button type="submit" class="nav-dropdown-link nav-dropdown-btn">Log out</button>
        </form>
      @else
        <a href="{{ route('player.login') }}" class="nav-dropdown-link">My Stats</a>
        <a href="{{ route('login') }}" class="nav-dropdown-link">Host Login</a>
        <a href="{{ route('register') }}" class="nav-dropdown-link" style="color:var(--qb-yellow)">Host Sign Up</a>
      @endif
    @endauth
  </div>
```

- [ ] **Step 6: Run the test — expect PASS**

```bash
./vendor/bin/phpunit tests/Feature/MobileImprovementsTest.php --colors=always
```

Expected: PASS — all four `assertSee` checks pass.

- [ ] **Step 7: Commit**

```bash
git add resources/views/layouts/app.blade.php tests/Feature/MobileImprovementsTest.php
git commit -m "feat: add hamburger menu HTML to main navbar"
```

---

## Task 3: Navbar JS — hamburger toggle

**Files:**
- Modify: `resources/views/layouts/app.blade.php`

### Context

The JS toggle lives in the inline `<script>` block at the bottom of `app.blade.php` (lines 57–65), alongside the existing alert-dismissal script. Add the hamburger toggle to that same block.

- [ ] **Step 1: Add a test for the JS presence**

Add to `tests/Feature/MobileImprovementsTest.php`:

```php
/** Navbar toggle script is present */
public function test_navbar_toggle_script_is_present(): void
{
    $response = $this->get(route('play.join'));

    $response->assertStatus(200);
    $response->assertSee('nav-toggle', false);
    $response->assertSee('dropdown.hidden', false);
}
```

- [ ] **Step 2: Run the new test — expect FAIL**

```bash
./vendor/bin/phpunit tests/Feature/MobileImprovementsTest.php --colors=always
```

Expected: FAIL on `test_navbar_toggle_script_is_present` — `dropdown.hidden` is not in the page yet.

- [ ] **Step 3: Add the toggle JS inside the existing `<script>` block**

In `app.blade.php`, find the existing `<script>` block (around lines 57–65) that contains the alert-dismissal code. Add the hamburger toggle **after** the alert code, inside the same `<script>` block:

```js
(function() {
  var toggle = document.getElementById('nav-toggle');
  var dropdown = document.getElementById('nav-dropdown');
  if (!toggle || !dropdown) return;
  toggle.addEventListener('click', function() {
    var isOpen = !dropdown.hidden;
    dropdown.hidden = isOpen;
    toggle.textContent = isOpen ? '☰' : '✕';
    toggle.setAttribute('aria-expanded', String(!isOpen));
  });
})();
```

The `<script>` block should now contain both the alert-dismissal code and the hamburger toggle code.

- [ ] **Step 4: Run all tests — expect all PASS**

```bash
./vendor/bin/phpunit tests/Feature/MobileImprovementsTest.php --colors=always
```

Expected: both navbar tests PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/layouts/app.blade.php tests/Feature/MobileImprovementsTest.php
git commit -m "feat: add hamburger toggle JS to main navbar"
```

---

## Task 4: CSS for game topbar — flex desktop + grid mobile

**Files:**
- Modify: `public/css/app.css` (append after the navbar CSS added in Task 1)

### Context

The game topbar CSS currently lives entirely in an inline `<style>` block inside `game.blade.php`. After Task 5 strips the layout properties from that inline block, `app.css` must provide them. Add the `app.css` rules **first** (this task), then strip the inline block in Task 5 — this ordering prevents a flash of broken layout if both changes were in one commit.

- [ ] **Step 1: Append game topbar CSS to `public/css/app.css`**

Open `public/css/app.css` and append the following after the nav dropdown CSS added in Task 1:

```css

/* ── Game topbar — desktop: 3-element single row ── */
.game-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 58px;
  padding: 0 1.25rem;
}
.topbar-center {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  justify-content: center;
}
.topbar-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

/* ── Game topbar — mobile: brand+right row 1, chips row 2 ── */
@media (max-width: 600px) {
  .game-topbar {
    display: grid;
    grid-template-areas:
      "brand right"
      "chips chips";
    grid-template-columns: 1fr auto;
    height: auto;
    padding: 0;
  }
  .game-topbar-brand {
    grid-area: brand;
    align-self: center;
    padding: 0 0.75rem;
    line-height: 44px;
  }
  .topbar-center {
    grid-area: chips;
    gap: 6px;
    padding: 0 0.75rem 8px;
  }
  .topbar-right {
    grid-area: right;
    align-self: center;
    padding: 0 0.75rem;
  }
}
```

- [ ] **Step 2: Verify the file still ends correctly**

```bash
tail -5 public/css/app.css
```

Expected: ends with the closing `}` of the `@media (max-width: 600px)` block.

- [ ] **Step 3: Commit**

```bash
git add public/css/app.css
git commit -m "style: add game topbar responsive CSS (flex desktop, grid mobile)"
```

---

## Task 5: Game topbar HTML — named classes and inline style cleanup

**Files:**
- Modify: `resources/views/layouts/game.blade.php`

### Context

Current `game.blade.php` topbar (lines 37–43):
```html
<div class="game-topbar">
  <span class="game-topbar-brand">⚡ Quiz<span>Blast</span></span>
  <div style="display:flex;gap:0.75rem;align-items:center">
    @yield('topbar-center')
  </div>
  <div>@yield('topbar-right')</div>
</div>
```

And the inline `<style>` block (lines 13–22) contains:
```css
.game-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.25rem;
  height: 58px;
  background: var(--qb-purple);
  border-bottom: 3px solid rgba(0,0,0,0.3);
  flex-shrink: 0;
}
```

Two changes: (a) replace the topbar HTML, (b) strip layout properties from the inline `.game-topbar` rule. The `.game-topbar-brand` and `.game-topbar-brand span` rules in the inline style must be left **untouched**.

**Critical:** The inline `<style>` block loads after `app.css`. Any `display`, `height`, `padding`, `align-items`, or `justify-content` left in the inline `.game-topbar` rule will override the `app.css` mobile grid rule and break the two-row layout on phones.

- [ ] **Step 1: Write a structural test**

Add to `tests/Feature/MobileImprovementsTest.php`:

```php
/** Game topbar uses named classes for center and right slots */
public function test_game_topbar_has_named_slot_classes(): void
{
    // The game layout is used by play/game. Create a game and player to access it.
    // We only need to check the layout HTML, so a GET to any game route will do.
    // The join page uses layouts.app, not layouts.game — use lobby instead.
    $game = \App\Models\Game::factory()->create(['status' => 'waiting']);
    $player = \App\Models\GamePlayer::factory()->create([
        'game_id' => $game->id,
        'is_spectator' => false,
    ]);

    session(['player_id_' . $game->pin => $player->id]);

    $response = $this->get(route('play.lobby', $game->pin));

    $response->assertStatus(200);
    $response->assertSee('class="topbar-right"', false);
    // topbar-center is conditionally rendered; lobby yields it, so check it exists
}
```

**Note:** If `Game::factory()` or `GamePlayer::factory()` don't exist yet, skip this step and rely on manual browser testing after implementation. Run the existing tests to make sure they still pass:

```bash
./vendor/bin/phpunit tests/Feature/MobileImprovementsTest.php --colors=always
```

- [ ] **Step 2: Replace the topbar HTML block in `game.blade.php`**

Replace lines 37–43 (the `<div class="game-topbar">...</div>` block) with:

```html
<div class="game-topbar">
  <span class="game-topbar-brand">⚡ Quiz<span>Blast</span></span>
  @hasSection('topbar-center')
  <div class="topbar-center">@yield('topbar-center')</div>
  @endif
  <div class="topbar-right">@yield('topbar-right')</div>
</div>
```

- [ ] **Step 3: Strip layout properties from the inline `.game-topbar` rule**

In the inline `<style>` block (lines 13–22), replace the `.game-topbar` rule:

**Before:**
```css
.game-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.25rem;
  height: 58px;
  background: var(--qb-purple);
  border-bottom: 3px solid rgba(0,0,0,0.3);
  flex-shrink: 0;
}
```

**After:**
```css
.game-topbar {
  background: var(--qb-purple);
  border-bottom: 3px solid rgba(0,0,0,0.3);
  flex-shrink: 0;
}
```

**Do not change `.game-topbar-brand` or `.game-topbar-brand span` — leave those rules exactly as they are.**

- [ ] **Step 4: Verify the inline `<style>` block is correct**

The complete inline `<style>` block should now look like this (and only this):

```css
.game-wrap { min-height: 100vh; display: flex; flex-direction: column; }
.game-topbar {
  background: var(--qb-purple);
  border-bottom: 3px solid rgba(0,0,0,0.3);
  flex-shrink: 0;
}
.game-topbar-brand {
  font-family: 'Montserrat', sans-serif;
  font-weight: 900;
  font-size: 1.15rem;
  color: #fff;
  text-transform: uppercase;
  letter-spacing: -0.3px;
}
.game-topbar-brand span { color: var(--qb-yellow); }
```

Check that `display`, `align-items`, `justify-content`, `padding`, and `height` are all **absent** from the inline `.game-topbar` rule.

- [ ] **Step 5: Run tests**

```bash
./vendor/bin/phpunit tests/Feature/MobileImprovementsTest.php --colors=always
```

Expected: all passing tests continue to pass.

- [ ] **Step 6: Commit**

```bash
git add resources/views/layouts/game.blade.php tests/Feature/MobileImprovementsTest.php
git commit -m "feat: update game topbar HTML to named classes, move layout to app.css"
```

---

## Task 6: Join page — fluid heading, smaller PIN input, condensed footer

**Files:**
- Modify: `resources/views/play/join.blade.php`

### Context

All changes are inline style edits in the Blade file. No CSS changes needed. Current values to replace:
- Line 10: `font-size:2.8rem` on the `<h1>` → fluid `clamp()`
- Line 25: `font-size:2.2rem` on the PIN input → `1.7rem`
- Lines 43–52: two-paragraph footer div → single condensed `<p>`

- [ ] **Step 1: Write a test for the join page changes**

Add to `tests/Feature/MobileImprovementsTest.php`:

```php
/** Join page uses fluid heading and smaller PIN input */
public function test_join_page_uses_clamp_heading_and_condensed_footer(): void
{
    $response = $this->get(route('play.join'));

    $response->assertStatus(200);
    $response->assertSee('clamp(1.8rem,9vw,2.6rem)', false);
    $response->assertSee('font-size:1.7rem', false);
    // Footer is condensed to one element — the old two-paragraph wrapper is gone
    $response->assertDontSee('flex-wrap:wrap', false);
}
```

- [ ] **Step 2: Run the new test — expect FAIL**

```bash
./vendor/bin/phpunit tests/Feature/MobileImprovementsTest.php --colors=always
```

Expected: FAIL on `test_join_page_uses_clamp_heading_and_condensed_footer` — `clamp(` and `font-size:1.7rem` are not in the page yet.

- [ ] **Step 3: Update the heading font-size on line 10**

Change `font-size:2.8rem` to `font-size:clamp(1.8rem,9vw,2.6rem)` on the `<h1>` line:

**Before (line 10):**
```html
      <h1 style="font-size:2.8rem;font-family:'Montserrat',sans-serif;font-weight:900;text-transform:uppercase;letter-spacing:-1px">QuizBlast</h1>
```

**After:**
```html
      <h1 style="font-size:clamp(1.8rem,9vw,2.6rem);font-family:'Montserrat',sans-serif;font-weight:900;text-transform:uppercase;letter-spacing:-1px">QuizBlast</h1>
```

- [ ] **Step 4: Update the PIN input font-size on line 25**

Change `font-size:2.2rem` to `font-size:1.7rem`:

**Before (line 25):**
```html
                 style="font-family:'Montserrat',sans-serif;font-size:2.2rem;font-weight:900;letter-spacing:.25em;text-align:center;padding:1rem"
```

**After:**
```html
                 style="font-family:'Montserrat',sans-serif;font-size:1.7rem;font-weight:900;letter-spacing:.25em;text-align:center;padding:1rem"
```

- [ ] **Step 5: Replace the footer div (lines 43–52) with a condensed single `<p>`**

**Before (lines 43–52):**
```html
    <div style="margin-top:1.25rem;display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
      <p style="color:rgba(255,255,255,.5);font-size:.82rem;text-align:center">
        Want to host? <a href="{{ route('register') }}" style="color:var(--qb-yellow);font-weight:700">Create a host account</a>
      </p>
      @if(!session('player_account_id'))
        <p style="color:rgba(255,255,255,.5);font-size:.82rem;text-align:center">
          Track your stats? <a href="{{ route('player.register') }}" style="color:var(--qb-cyan);font-weight:700">Create a player account</a>
        </p>
      @endif
    </div>
```

**After:**
```html
    <p style="color:rgba(255,255,255,.5);font-size:.82rem;text-align:center;margin-top:1.25rem">
      Want to host? <a href="{{ route('register') }}" style="color:var(--qb-yellow);font-weight:700">Create a host account</a>@if(!session('player_account_id')) · <a href="{{ route('player.register') }}" style="color:var(--qb-cyan);font-weight:700">Player account</a>@endif
    </p>
```

- [ ] **Step 6: Run all tests — expect all PASS**

```bash
./vendor/bin/phpunit tests/Feature/MobileImprovementsTest.php --colors=always
```

Expected: all tests PASS, including `test_join_page_uses_clamp_heading_and_condensed_footer`.

- [ ] **Step 7: Commit**

```bash
git add resources/views/play/join.blade.php tests/Feature/MobileImprovementsTest.php
git commit -m "feat: fluid heading, smaller PIN input, condensed footer on join page"
```

---

## Task 7: Final verification

- [ ] **Step 1: Run the full test suite**

```bash
./vendor/bin/phpunit --colors=always
```

Expected: all tests pass. The `MobileImprovementsTest` should show 4 tests (or 3 if the topbar factory test was skipped).

- [ ] **Step 2: Smoke-check in a browser**

Open the app (likely `http://localhost` or the configured dev URL). Resize the browser window to 375px wide and verify:

1. **Join page** (`/play`): heading scales down, PIN input is smaller, footer is one line.
2. **Navbar**: hamburger `☰` button appears, horizontal links are hidden. Click it — dropdown opens. Click `✕` — dropdown closes.
3. **In-game topbar** (join a game and proceed to lobby/game): brand and Leave button on row 1, stat chips on row 2.
4. **Desktop (> 768px)**: navbar links show horizontally, no hamburger, topbar is the original single row.

- [ ] **Step 3: Commit final state if any tweaks were made**

```bash
git add -p   # stage only intentional changes
git commit -m "fix: mobile improvements smoke-check tweaks"
```

---

## Summary

| Task | Files touched | Commit message |
|------|--------------|----------------|
| 1 | `app.css` | `style: add hamburger and nav-dropdown CSS foundations` |
| 2 | `app.blade.php`, test | `feat: add hamburger menu HTML to main navbar` |
| 3 | `app.blade.php`, test | `feat: add hamburger toggle JS to main navbar` |
| 4 | `app.css` | `style: add game topbar responsive CSS (flex desktop, grid mobile)` |
| 5 | `game.blade.php`, test | `feat: update game topbar HTML to named classes, move layout to app.css` |
| 6 | `join.blade.php`, test | `feat: fluid heading, smaller PIN input, condensed footer on join page` |
| 7 | — | final verification |
