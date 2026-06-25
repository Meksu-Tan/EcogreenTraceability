# WIP Process Tree Refactor — Analysis

## Current State

### Problem

- 5 hardcoded sections + 4 complex computed step builders + 6 mode refs + watchers
- 3 mapping methods (`mapFrontendSectionToDb*`, `mapRundownToFeedSectionId`, `mapSectionToMaterialId`)
- Config-based mapping still needed by feed/rundown queries
- Returns flat array, not actually consumed by frontend — Vue builds its own tree
- Zero new tables/migrations/models exist yet. Plan doc is comprehensive but implementation hasn't started.

---

## Your Plan — What's Solid

1. **2 tables only** (`m_wip_section` + `m_wip_process_step`) — correct. No over-splitting.
2. **`step_type` ENUM** (`feed`, `rundown`, `label`, `mode_switch`) — good, covers all cases.
3. **`mode_group` + `mode_value` columns** — handles nested modes (104, 105, 106, 112).
4. **`parent_step_id`** — needed for section 105 chain mode (me28 vs me80 conditional children).
5. **Phase ordering** (DB → Backend → Frontend → Cleanup) — correct sequence.
6. **Ponytail skips** (no drag-drop, no WebSocket, no soft-delete cascade) — all right calls.

---

## Gaps / Issues to Address

| # | Area | Detail |
|---|------|--------|
| 1 | **Frontend wiring** | Vue builds tree entirely from hardcoded arrays. Backend `getWipTree()` returns flat `m_material` data. You need to wire the new backend endpoint → new frontend fetch. |
| 2 | **Section 106 & 112 mode complexity** | Section 106 has major mode (Mode 1 vs Mode 2 = completely different step sets) + sub mode (ECOROL 24 vs 12/14 within Mode 1). Section 112 has 5 modes. Your plan's `mode_group` + `mode_value` handles this, but the frontend template needs a recursive/v-for approach for conditional step visibility. |
| 3 | **Caller verification** | `getBalance()`, `getFeed()`, `getRundown()` call `mapFrontendSectionToDbFeedId()` / `mapFrontendSectionToDbRundownId()`. After refactor, the step's `feed_id`/`rundown_id` from DB replaces this — but you need to verify every caller. |
| 4 | **`feed_material_map` config** | `009/006/008` prefix routing is used by `getMaterialIdBySection()` for balance queries. This config may need to stay or be moved to a DB column. |
| 5 | **No admin UI** | Plan mentions seed but no admin UI for managing the tree. Acceptable for Phase 1, but worth noting. |
| 6 | **Cache implementation** | `getWipTree()` hits DB on every page load. Plan mentions cache but no implementation detail. Simple fix: `Cache::remember('wip_tree_plant:{id}', 3600, ...)`. |
| 7 | **Frontend mode rendering** | Current `VBtnToggle` for Mode 1/2 + `VSelect` for sub-modes need to render dynamically from `modeConfigs` in API response. Template logic changes significantly. |

---

## Recommended Implementation Order

### Phase 1 — DB (migrations + seed)

- **1a.** `create_m_wip_section_table` migration
- **1b.** `create_m_wip_process_step_table` migration
- **1c.** `WipSection` + `WipProcessStep` models
- **1d.** Seed all 9 sections + ~60–80 steps from hardcoded data
- **1e.** Feature test for seed data integrity

### Phase 2 — Backend

- **2a.** `WipTreeService::getTree()` — nested query, cache
- **2b.** `WipEntryController::getWipTree()` — wire to new service
- **2c.** Update `WipEntryQueryTrait` callers to use `step.feed_id` / `rundown_id`
- **2d.** Unit test for `WipTreeService`

### Phase 3 — Frontend

- **3a.** Add `fetchWipTree()` to service + store
- **3b.** Refactor `WipEntryView.vue` template to render from API response
- **3c.** Replace hardcoded mode refs with reactive `modes` object
- **3d.** Remove `wipSectionsBase`, all `section*Steps` computed, all mode watchers

### Phase 4 — Cleanup

- **4a.** Remove `mapFrontendSectionToDb*` from trait
- **4b.** Remove `mapSectionToMaterialId`
- **4c.** Evaluate `wip_material_mapping.php` (keep `feed_material_map`, remove `rundown_to_feed_map`)

---

## Critical: Section 112 Mode Data (5 modes)

The most complex section. Each mode has completely different feed + rundown steps:

| Mode | Feed ID | Rundown IDs |
|------|---------|-------------|
| ECOROL WAX 106/114 | `009-04` | `018` |
| FA24 106/114 | `009-01` | `038` |
| FA18lrr 106/114 | `009-03` | `058` |
| FA14lrr 112/114 | `009-02` | `069`, `059` |
| FA18lrr 112/114 | `009-03` | `069`, `029`, `019` |

> Seed data must capture all 5 variants with correct `mode_value` conditions.
