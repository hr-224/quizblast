# Host Answer Distribution Chart

**Date:** 2026-03-21
**Status:** Approved by user

## What

After the host reveals answers on the question screen, display a bar chart showing how many players selected each answer option, positioned above the answer grid.

## Layout (approved mockup: answer-chart-v2.html)

```
┌─────────────────────────────────────┐
│  Topbar                             │
├─────────────────────────────────────┤
│  ✅ Answers revealed!               │
│                                     │
│  [Question card]                    │
│                                     │
│  [Bar chart — response breakdown]   │  ← NEW (top)
│                                     │
│  [Answer grid 2×2 with ✓/✗]        │  ← existing (bottom)
└─────────────────────────────────────┘
```

## Bar Chart Spec

- Visible only when `$game->status === 'reviewing'`
- Four vertical bars, one per answer option
- Bar colour matches answer colour (red/blue/yellow/green)
- Bar height proportional to vote share (tallest = 100% of chart area)
- Count shown above each bar
- Shape symbol (▲◆●■) shown below each bar as label
- Correct answer bar: gold count label, shape label appended with ` ✓`, subtle glow
- Header row: "Response breakdown" label left, "N players answered" right
- Minimum bar height (4px stub) so zero-vote answers are visible

## Data

`$answerCounts` is a `pluck('total', 'answer_id')` collection — it only contains answer IDs that received at least one vote. Answers with zero votes are absent.

**Iteration rule:** Always iterate `$question->answers` as the authoritative answer list (4 answers in order). Look up each answer's count with `$answerCounts->get($ans->id, 0)`. This ensures zero-vote answers render as a 4px stub bar rather than being silently skipped.

**Header label:** Show `"{{ $totalAnswered }} responses"` on the right side of the chart header. Using the word "responses" (not "players answered") is accurate for both single-correct and multi-correct questions — it counts answer selections, not unique people, so it never misleads.

No backend changes needed. The `answer-count-updated` WebSocket event also carries `answer_counts` (same shape) but the chart is only shown during reviewing state so live updates during the question phase are not required.

## Files to change

- `resources/views/host/question.blade.php` — add chart HTML+CSS between the question card and answer grid, visible only during reviewing state. No other files need changing.
