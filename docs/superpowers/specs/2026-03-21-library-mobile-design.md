# Library Mobile — Design Spec

**Goal:** Fix the library page search header so it works correctly on phones down to 320px without breaking desktop.

**Scope:** `library/index.blade.php` header only. The quiz grid (`.grid-3`) already collapses to one column at ≤768px and needs no changes. No backend changes.

---

## 1. Problem

The search form at the top of the library page uses a single `display:flex` row with fixed-width inputs (`width:220px` on the text input, `width:150px` on the select). At 375px these inputs are squished and overflow at 320px. The `flex-wrap` is not set, so items never wrap to a second line.

## 2. Solution — Option A: stacked search

On mobile (≤767px), stack the form into two rows:
- **Row 1:** search text input, full width
- **Row 2:** category dropdown (fills remaining space) + SEARCH button side by side

On desktop (≥768px): layout is unchanged — single horizontal flex row, fixed-width inputs.

### HTML changes (`resources/views/library/index.blade.php`)

Replace the inline-styled form (lines 12–21) with:

```blade
<form method="GET" action="{{ route('library') }}" class="library-search-form">
  <input type="text" name="search" class="form-control library-search-input"
         value="{{ request('search') }}" placeholder="Search quizzes..." />
  <div class="library-filter-row">
    <select name="category" class="form-control library-search-select">
      <option value="">All Categories</option>
      @foreach($categories as $cat)
        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
      @endforeach
    </select>
    <button class="btn btn-white btn-sm">SEARCH</button>
  </div>
</form>
```

Changes from current:
- `style="display:flex;gap:0.5rem"` removed from `<form>` — replaced by `.library-search-form` class
- `style="width:220px"` removed from `<input>` — replaced by `.library-search-input` class
- `style="width:150px"` removed from `<select>` — replaced by `.library-search-select` class
- New `<div class="library-filter-row">` wraps select + button together so they form a flex row on mobile

### CSS additions (`public/css/app.css`)

Append after the existing `@media (max-width: 600px)` block:

```css
/* ── Library search form ── */
.library-search-form {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  flex-wrap: nowrap; /* prevent premature wrap on mid-size tablets */
}
.library-search-input  { width: 220px; flex-shrink: 0; }
.library-search-select { width: 150px; flex-shrink: 0; }
.library-filter-row {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  flex-wrap: nowrap; /* keep select+button on one row always */
}

@media (max-width: 767px) {
  .library-search-form   { flex-direction: column; align-items: stretch; width: 100%; }
  .library-search-input  { width: 100%; flex-shrink: 1; }
  .library-filter-row    { flex: none; }
  .library-filter-row .library-search-select { flex: 1; width: auto; }
}
```

**Why no JS:** The two-row layout is achieved purely with CSS flexbox direction change at the breakpoint. The `<div class="library-filter-row">` wrapper keeps the select and button together on the second row.

**CSS insertion point:** Append these rules at the very end of `public/css/app.css`, after the last `@media (max-width: 600px)` block (the game-topbar mobile grid rule).

---

## 3. What does NOT change

- Desktop layout (≥768px) — identical to current
- Quiz card grid (`.grid-3`) — already responsive
- Search/filter logic — no PHP or controller changes
- Pagination — unchanged

---

## 4. Files to touch

| File | Change |
|------|--------|
| `resources/views/library/index.blade.php` | Wrap select+button in `.library-filter-row`; replace inline styles with classes |
| `public/css/app.css` | Add `.library-search-form` responsive rules |
