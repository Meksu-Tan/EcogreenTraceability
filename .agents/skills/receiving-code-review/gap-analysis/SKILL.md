---
name: gap-analysis
description: >
  Compare reference-dont-change/ implementation against new Laravel 12 + Vue 3
  implementation feature-by-feature. Produces a structured gap report: missing features,
  behavioral differences, data mismatches, UI discrepancies. Use when user says
  "gap analysis", "compare reference vs new", "cek perbedaan", "analisa gap",
  or invokes /gap-analysis.
---

# Gap Analysis Skill

Compare reference implementation against new implementation. Output structured gap report with prioritized findings.

## Trigger

User says: "gap analysis", "compare reference vs new", "cek perbedaan X", "analisa gap", "GAP-ANALYSIS", or invokes `/gap-analysis <module-name>`.

## Pre-flight

1. Identify module/feature to compare
2. Reference: `reference-dont-change/` (read-only, source of truth for business logic)
3. New: `backend/Modules/<module>/` + `frontend/src/modules/<module>/`

## Workflow

### Step 1 — Map Reference Features

For the target module, catalog every feature in reference:

1. **API endpoints** — Read `reference-dont-change/routes/web.php` or `api.php`
   - List all routes with HTTP method, URL, controller method
   - Note middleware, parameters, response format

2. **Controller logic** — Read each controller method
   - Input validation rules
   - Business logic flow
   - Error handling
   - Response structure

3. **Database queries** — Read models and raw queries
   - Table relationships (joins, foreign keys)
   - Column usage (which columns are read/written)
   - Query patterns (grouping, filtering, sorting)

4. **UI/UX** — Read blade templates
   - Form fields and validation
   - Table columns and formatting
   - Buttons and actions
   - Modal/dialog content
   - Error states and loading states

5. **Business rules** — Extract from code
   - Calculations (formulas, aggregations)
   - Conditional logic (when does X happen)
   - Status transitions
   - Role-based access

### Step 2 — Map New Implementation

For the same module, catalog what exists in new stack:

1. **API endpoints** — Read `Modules/<module>/routes/api.php`
2. **Controller/Service/Repository** — Read implementation chain
3. **Database** — Read migrations, check table/column names
4. **Frontend** — Read Vue components, stores, API services
5. **Tests** — Read feature and unit tests

### Step 3 — Compare Feature-by-Feature

For each feature in reference, check:

| Check | What to look for |
|-------|-----------------|
| **Endpoint exists** | Same URL pattern, HTTP method, parameters |
| **Business logic matches** | Same calculations, same conditions, same flow |
| **Data correct** | Same tables, same columns, same joins |
| **UI matches** | Same fields, same layout, same interactions |
| **Error handling** | Same error messages, same validation rules |
| **Edge cases** | Same handling for null, zero, empty, boundary values |

### Step 4 — Produce Gap Report

Format as markdown table:

```markdown
## Gap Analysis: <Module Name>

### Summary
- Total features in reference: N
- Fully implemented: N
- Partially implemented: N
- Missing: N
- Behavioral differences: N

### Gaps

| # | Feature | Status | Reference Behavior | New Behavior | Priority | Notes |
|---|---------|--------|-------------------|--------------|----------|-------|
| 1 | Endpoint X | ❌ Missing | Does Y | N/A | HIGH | — |
| 2 | Endpoint Z | ⚠️ Partial | Returns A,B,C | Returns A only | MEDIUM | Missing field B |
| 3 | Calc W | 🔴 Wrong | formula X | formula Y | HIGH | Diff result |
| 4 | UI field V | ⚠️ Different | Dropdown | Text input | LOW | UX mismatch |

### Priority Guide
- **HIGH**: Data incorrect, feature missing, business logic wrong
- **MEDIUM**: Partial implementation, UI mismatch, missing edge case
- **LOW**: Cosmetic difference, naming inconsistency, extra feature
```

### Step 5 — Recommend Fixes

For each HIGH/MEDIUM gap, provide:
1. What needs to change (specific file/line)
2. How to fix (code snippet or approach)
3. Estimated effort (small/medium/large)

## Scope Control

- Focus on ONE module per analysis (ask if user wants multiple)
- Don't analyze shared infrastructure (auth, plant context) unless specifically asked
- Reference is source of truth — if new implementation differs, new is wrong
- Don't suggest changing reference

## Stopping Condition

Analysis is complete when:
- Every reference feature has been checked against new implementation
- All gaps are categorized by priority
- HIGH/MEDIUM gaps have fix recommendations
- Report is delivered to user

## Output

Deliver as:
1. Markdown gap report (saved to `gap-analysis-<module>.md` in project root)
2. Summary in chat with top 3-5 most critical gaps
