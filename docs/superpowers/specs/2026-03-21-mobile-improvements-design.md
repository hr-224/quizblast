# Mobile Improvements — Design Spec

**Goal:** Make the player-facing pages (join page, game topbar, main navbar) work correctly on phones down to 320px without breaking desktop.

**Scope:** Player-facing only. Host dashboard and host game views are out of scope. No changes to game logic, answer layout, or lobby page.

---

## 1. Navbar (`layouts/app.blade.php` + `public/css/app.css`)

**Problem:** The current navbar crams all nav links into a single flex row. At ~400px and below they overflow or wrap badly.

**Solution:** Hamburger menu on mobile.

### Breakpoint
`max-width: 767px` (i.e., < 768px). On **desktop (≥ 768px):** normal horizontal nav, no change. On **mobile (≤ 767px):** hamburger toggle.

Note: the existing grid breakpoint in `app.css` uses `max-width:768px` — this is intentional. At exactly 768px the nav links are shown (our `max-width:767px` rule does not hide them at 768px), which is the correct desktop behaviour.

### HTML changes (`layouts/app.blade.php`)

1. Add `id="nav-links"` to the existing `<div class="navbar-nav">`:
   ```html
   <div class="navbar-nav" id="nav-links">
   ```
   Do **not** add a new wrapper div.

2. Add a hamburger button **inside** `<div class="navbar-inner">`, as the last child (after `#nav-links`):
   ```html
   <button id="nav-toggle" class="hamburger-btn" aria-label="Menu" aria-expanded="false" aria-controls="nav-dropdown">☰</button>
   ```

3. Add the dropdown **inside** `<nav class="navbar">`, immediately after `</div><!-- /.navbar-inner -->`. Keeping it inside `<nav>` preserves the sticky positioning:
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
   **Note on desktop styling parity:** On desktop, the "Host Sign Up" link uses `class="btn btn-white btn-sm"` (white button). In the mobile dropdown it intentionally becomes a plain `nav-dropdown-link` with yellow text — matching the visual weight of the other dropdown items rather than showing a mismatched button inside a list. This is a deliberate design simplification for the mobile menu.

### CSS additions (`public/css/app.css`)

```css
/* Hamburger button — hidden on desktop */
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

/* Nav dropdown */
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

### JS (inline `<script>` in `layouts/app.blade.php`)

Add alongside the existing alert-dismissal script block (before `</body>`):

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

---

## 2. Game topbar (`layouts/game.blade.php` + `public/css/app.css`)

**Problem:** The single 58px flex row (brand | center chips | right slot) overflows at ~375px when center holds PIN + Score + Streak chips.

**Solution:** On mobile, reflow the existing three-element row into two rows using CSS grid — brand + right on row 1, chips centred on row 2. The HTML stays a flat three-child structure; no wrapper divs are needed. The desktop layout is unchanged.

### Breakpoint
`max-width: 600px` (consistent with the existing `app.css` mobile rule at line 132).

### HTML changes (`layouts/game.blade.php`)

Replace the `<div class="game-topbar">...</div>` block (lines 37–43) with:

```html
<div class="game-topbar">
  <span class="game-topbar-brand">⚡ Quiz<span>Blast</span></span>
  <div class="topbar-center">@yield('topbar-center')</div>
  <div class="topbar-right">@yield('topbar-right')</div>
</div>
```

Changes from current:
- The anonymous center div `<div style="display:flex;gap:0.75rem;align-items:center">` becomes `<div class="topbar-center">` (inline style removed — managed by CSS).
- The anonymous right div `<div>` becomes `<div class="topbar-right">`.
- The `@hasSection` conditional is **not used** — empty slots produce empty divs which take no space.

Also update the inline `<style>` block in `game.blade.php`. The block currently contains three rules: `.game-topbar`, `.game-topbar-brand`, and `.game-topbar-brand span`. Only `.game-topbar` changes — leave `.game-topbar-brand` and `.game-topbar-brand span` **exactly as they are**.

Replace **only** the `.game-topbar` rule:

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

The layout properties (`display`, `height`, `padding`, `align-items`, `justify-content`) move to `app.css` so they can be overridden by the mobile media query.

### CSS additions (`public/css/app.css`)

**Append these rules at the end of `app.css`**, after the existing `@media(max-width:600px)` block (line 132). This placement ensures the new rules override correctly without specificity issues.

```css
/* Game topbar — desktop: original 3-element single row */
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

/* Game topbar — mobile: brand+right on row 1, chips on row 2 */
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
  .topbar-center:empty {
    display: none; /* collapse row 2 on pages with no center slot content */
  }
  .topbar-right {
    grid-area: right;
    align-self: center;
    padding: 0 0.75rem;
  }
}
```

**Desktop is preserved** because `.game-topbar { display: flex; ... }` applies at all widths, and the `@media (max-width: 600px)` override switches to `display: grid` only on mobile. The original three-element flex row is fully intact above 600px.

**Empty center slot on mobile:** `.topbar-center:empty` hides the second grid row when no chips are yielded (e.g., on the lobby page), preventing the bottom padding from creating dead vertical space.

---

## 3. Join page (`play/join.blade.php`)

**Problem:** The fixed `font-size:2.8rem` heading and `font-size:2.2rem` PIN input (both inline styles in the Blade file) overflow on phones ≤ 375px. The two footer paragraphs sit on separate lines, wasting space.

**Solution:** Change the inline styles directly in the Blade file. Form structure (inputs, labels, button) unchanged. No new CSS classes needed.

### Changes to `play/join.blade.php`

1. **Heading font-size** (line 10): `font-size:2.8rem` → `font-size:clamp(1.8rem,9vw,2.6rem)`

2. **PIN input font-size** (line 25): `font-size:2.2rem` → `font-size:1.7rem`

3. **Footer links** (lines 43–52): Replace the **entire** wrapper `<div>` and its two `<p>` children (lines 43–52) with a single `<p>`. The `·` separator and the player-account link remain inside the `@if(!session('player_account_id'))` block to avoid a dangling separator when the player is logged in.

   **Note:** This intentionally shortens the second link's copy from "Track your stats? Create a player account" to "Player account" to fit on one line.

   Replace lines 43–52:
   ```blade
   <p style="color:rgba(255,255,255,.5);font-size:.82rem;text-align:center;margin-top:1.25rem">
     Want to host? <a href="{{ route('register') }}" style="color:var(--qb-yellow);font-weight:700">Create a host account</a>@if(!session('player_account_id')) · <a href="{{ route('player.register') }}" style="color:var(--qb-cyan);font-weight:700">Player account</a>@endif
   </p>
   ```

4. **No CSS changes needed** for the join page. The `clamp()` heading and smaller PIN font are set as inline styles on the elements themselves, so they apply at all viewport widths without a media query. The existing card class `.card` padding in `app.css` is already appropriate.

---

## 4. What does NOT change

- Desktop layout at any viewport width above the breakpoints.
- Answer grid / game play area (already mobile-friendly).
- Host views (dashboard, host lobby, host question).
- Lobby waiting page.
- Any backend logic, routes, or controllers.

---

## 5. Files to touch

| File | Change |
|------|--------|
| `resources/views/layouts/app.blade.php` | Add `id` to `navbar-nav`; add hamburger button inside `.navbar-inner`; add `#nav-dropdown` inside `<nav>`; add toggle JS |
| `resources/views/layouts/game.blade.php` | Replace anonymous center/right divs with named classes; update inline `<style>` block to remove layout properties from `.game-topbar` |
| `resources/views/play/join.blade.php` | `clamp()` heading, smaller PIN font, condensed footer links |
| `public/css/app.css` | Hamburger styles, nav-dropdown styles, game topbar flex+grid responsive rules |

No new files. No migrations. No backend changes.
