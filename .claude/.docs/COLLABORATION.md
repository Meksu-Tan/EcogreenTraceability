# Collaboration — EODS Refactoring Project

> **Last Updated:** 2026-06-16  
> **Purpose:** Define how Claude Code coordinates with other AI executors for optimal token usage and quality control

---

## 🎯 WHY COLLABORATION SYSTEM?

### Problem 1: Token Efficiency

**Without collaboration:**
- Claude generates all code (~50,000 tokens output)
- Same code stays in context window (paid as input tokens on every message)
- Total cost per sprint: High

**With collaboration:**
- Specialized executors generate boilerplate code (Claude handles architecture only)
- Context window stays lean (~10,000 tokens)
- Total cost per sprint: ~60% reduction

### Problem 2: Speed & Scalability

**Single executor bottleneck:**
- One AI tool doing everything = slow iteration
- Cannot parallelize work across different tasks

**Multi-executor approach:**
- Parallel development paths possible
- Claude focuses on complex decisions
- Executors handle repetitive patterns

---

## 🤖 EXECUTOR ROLES & RESPONSIBILITIES

| Executor Type | Primary Role | Best For | Avoid For |
|--------------|--------------|----------|-----------|
| **Claude Code** | Architecture, Complex Logic, Review | System design, business logic, code review | Simple CRUD, standard components |
| **OpenCode/Gemini** | Boilerplate CRUD | Database models, basic controllers | Business rules, complex validations |
| **Copilot/Cursor** | Quick edits, refactoring | Small fixes, formatting, linting | New features, system changes |
| **Subagent Tasks** | Independent research | Documentation, test generation | Direct code modification |

---

## 📋 COLLABORATION PATTERNS

### Pattern 1: Architecture → Implementation Handoff

**When to use:** Sprint kickoff, new module creation

**Workflow:**
```
1. Claude defines architecture (CLAUDE.md + SPRINT-XX.md)
2. Claude assigns executor roles for each feature
3. Executors implement their assigned modules
4. Claude reviews integration point by point
5. Final validation & cleanup by Claude
```

**Example Prompt for Executor:**
```markdown
=== LAYER 1 CONTEXT ===
[Auto-injected from CLAUDE.md]

=== LAYER 2 CONTEXT ===
Current Sprint: Sprint 1A (Master Data Migration)
Active Module: Plant Management
Feature: Create CRUD endpoints for m_plant

=== FEATURE SPECIFICATION ===
- Route: /api/v1/master/plants
- Methods: POST (create), GET (list), GET/:id (detail), PATCH (update), DELETE (soft delete)
- Validation rules: See StorePlantRequest, UpdatePlantRequest
- Response format: ApiResponse::success() wrapper
- Constraints: plant_code_3 unique, is_active boolean default true

=== YOUR ROLE ===
Generate complete implementation files:
1. Migrations (database/migrations/...)
2. Model (app/Models/Plant.php)
3. Repository interfaces & implementations
4. Service class with business logic
5. Controllers (thin, orchestration only)
6. FormRequests (validation rules)
7. Feature tests

Use Laravel best practices from CLAUDE.md. No questions needed - proceed directly.
```

**Executor Output:** Complete file set with paths listed  
**Claude's Job:** Verify architecture compliance, review integration

---

### Pattern 2: Claude Orchestration with Subagents

**When to use:** Complex multi-step tasks requiring independent analysis

**Workflow:**
```
Claude receives task → Decomposes into subtasks → Spawns specialized subagents → Aggregates results → Synthesizes final solution
```

**Example: Data Migration Strategy**

```bash
# Claude decomposes migration planning
Phase 1: Analyze source schema → Subagent: Research reference-dont-change/ database
Phase 2: Design transformation rules → Subagent: Map field transformations
Phase 3: Validate FK dependencies → Subagent: Check cross-table relationships
Phase 4: Generate migration scripts → Subagent: Write Python/Node.js transform functions

# Claude synthesizes all outputs into comprehensive plan
```

**Subagent Brief Template:**
```markdown
=== TASK ===
Research source database schema for Plant master data

=== SCOPE ===
- Table: m_plant, m_plant_detail, m_plant_user
- Columns: Identify all columns, types, constraints
- Relationships: FK relationships, soft delete indicators
- Sample data: Extract representative rows for testing

=== OUTPUT FORMAT ===
Markdown table with:
- Column name
- Type (int, varchar, timestamp, etc.)
- Nullable/Y
- Default value
- Unique constraint
- Index presence
- Notes (special handling required?)

Include SQL query to extract sample data.
```

---

### Pattern 3: Incremental Development Loop

**When to use:** Iterative feature development within a sprint

**Weekly Cadence:**

**Monday Morning:**
```
Claude sets weekly goals based on sprint roadmap
→ Assigns executor tasks for current week
→ Sets definition of done criteria
```

**Mid-week Checkpoint:**
```
Executors submit partial implementations
→ Claude reviews progress vs plan
→ Adjusts remaining tasks if needed
→ Provides quick feedback on blockers
```

**Friday Wrap-up:**
```
Executors complete all assigned features
→ Claude conducts full integration review
→ Runs test suites
→ Updates documentation with learnings
→ Prepares demo for stakeholders
```

---

## 📝 BRIEF WRITING GUIDE

### XS Brief (<5 min execution)

**Characteristics:** Tiny scope, well-defined, minimal context needed

**Example:** Add a simple API endpoint

```markdown
### ADD SIMPLE LIST ENDPOINT

Route: GET /api/v1/master/plants/{id}
Response: Single plant record via UserResource

Files to create/update:
1. Controller method (already exists, just add show() method)
2. Resource transformer (create ShowPlantResource)

Requirements:
- Inject PlantRepositoryInterface
- Find by ID or abort(404)
- Return ApiResponse::success(new ShowPlantResource($plant))

Follow existing pattern from index() method. Use same response structure.
```

---

### S Brief (~15 min execution)

**Characteristics:** Small feature, needs some coordination, multiple files

**Example:** Create Plant CRUD controller methods

```markdown
### IMPLEMENT PLANT CRUD CONTROLLER

**Context:**
We're building the Plant Master module for Sprint 1A.
Base repository already exists (EloquentPlantRepository).
Service layer ready (PlantService using PlantRepositoryInterface).

**Task:**
Complete CRUD controller methods:

1. **index()** - List all plants with pagination
   - Accept query params: search, id_plant, is_active
   - Call $this->service->paginate($filters)
   - Return paginated ApiResponse

2. **store()** - Create new plant
   - Accept StorePlantRequest validated input
   - Call $this->service->create($data)
   - Return created resource with 201 status

3. **show()** - Get single plant detail
   - Find by UUID or abort(404)
   - Return individual resource

4. **update()** - Update existing plant
   - Accept UpdatePlantRequest
   - Call service update()
   - Return updated resource

5. **destroy()** - Soft delete plant
   - Find by UUID
   - Delete (sets deleted_at, is_active=false)
   - Return success message

**Constraints:**
- All methods must use form requests for validation
- No direct DB calls - go through service layer
- Consistent response format via ApiResponse helper
- Handle exceptions gracefully (catch DomainException, return 409)

**Reference Files:**
- Existing: app/Http/Controllers/PlantController.php (stub exists)
- Existing: app/Services/PlantService.php
- Existing: Modules/Plant/app/Repositories/EloquentPlantRepository.php

Proceed with implementation. Include brief comments for complex logic.
```

---

### M Brief (~1 hour execution)

**Characteristics:** Multi-file feature, needs architectural understanding, testing required

**Example:** Full Plant Module with Testing

```markdown
### BUILD COMPLETE PLANT MODULE FROM SCRATCH

**Sprint Context:**
Sprint 1A - Master Data Migration Phase
Goal: Fully functional Plant management with CRUD operations

**Module Structure Required:**
Modules/Plant/
├── app/
│   ├── Http/
│   │   ├── Controllers/PlantController.php
│   │   ├── Requests/StorePlantRequest.php
│   │   ├── Requests/UpdatePlantRequest.php
│   │   └── Resources/PlantResource.php
│   ├── Services/
│   │   ├── Interfaces/PlantServiceInterface.php
│   │   ├── PlantService.php (implements interface)
│   │   └── Strategies/ (optional future expansion)
│   ├── Repositories/
│   │   ├── Interfaces/PlantRepositoryInterface.php
│   │   └── EloquentPlantRepository.php
│   └── Models/Plant.php
├── Providers/PlantServiceProvider.php
├── routes/api.php (register route group)
└── config/plant.php (if configuration needed)

**Implementation Requirements:**

1. **Database Layer:**
   - Migration: Create m_plant table with uuid PK, uuid unique, code_3 unique
   - Soft deletes enabled via deleted_at column
   - Foreign keys to m_plant_user junction table

2. **Service Layer:**
   - PlantServiceInterface contract
   - Implement pagination with filter support (search, plant_scope)
   - Business rules: validate code_3 uniqueness (account for soft-deletes)
   - Use Config::get() not env() for any settings

3. **Controller Layer:**
   - Thin orchestration only (no logic here)
   - Dependency injection of PlantServiceInterface
   - Proper error handling (try-catch with specific exception types)
   - Consistent ApiResponse responses

4. **Validation:**
   - FormRequest classes for store/update
   - Custom validation rules for code_3 (unique with soft-delete awareness)
   - Field-level validation messages

5. **Testing:**
   - Feature tests for all 5 CRUD operations
   - Unit tests for Service methods (mock Repository)
   - Minimum 80% code coverage
   - Use factories for test data

**Technical Constraints:**
- Declare strict_types=1 on ALL PHP files
- No hardcoded values - use env() or Config::get()
- Follow naming conventions from CLAUDE.md
- Test with SQLite :memory: database

**Documentation:**
- Update CLAUDE.md Lessons Learned section with any new patterns discovered
- Document API endpoints in swagger/openapi if configured
- Add to sprint completion checklist

**Execution Strategy:**
1. Set up folder structure and empty files
2. Write migrations first, then model
3. Build repository interface + impl
4. Create service layer with business logic
5. Add controllers and form requests
6. Register provider bindings
7. Configure routes
8. Write comprehensive tests last

Estimated time: 4-6 hours total
Start now and complete all steps before submitting for review.
```

---

### L Brief (Full day execution)

**Characteristics:** Entire feature/module, requires deep understanding, heavy coordination

**Example:** Complete Transaction Module Implementation

```markdown
=== FULL TRANSACTION MODULE IMPLEMENTATION ===

**OVERVIEW:**
Build ts-blending transaction module (Sprint 06 priority)
This is NOT just CRUD - involves complex trace number generation, balance consumption,
and chain-of-custody tracking.

**SCOPE:**
- Backend: Full Laravel module with authentication, validation, business logic
- Frontend: Vue component with real-time trace visualization
- Integration: Connects with Balance ledger for stock updates
- Documentation: API docs, user guide, migration playbook

**ARCHITECTURE DECISIONS ALREADY MADE:**
1. Use strategy pattern for trace number generation
2. Atomic state transition for balance updates
3. Companion trace records (feed + blend trace pairs)
4. Pessimistic locking during bulk operations

**MODULE STRUCTURE:**
backend/Modules/TsBlending/
├── app/Http/Controllers/
│   └── BlendingTransactionController.php (orchestrate blending logic)
├── app/Http/Requests/
│   ├── StartBlendingRequest.php (validate feed materials)
│   ├── ProcessBlendingRequest.php (confirm blending operation)
│   └── CancelBlendingRequest.php (rollback logic)
├── app/Services/
│   ├── BlendingService.php (core orchestration)
│   ├── TraceGeneratorStrategyInterface.php
│   ├── StandardTraceGenerator.php
│   └── BatchTraceGenerator.php (for special cases)
├── app/Repositories/
│   ├── BlendEntryRepositoryInterface.php
│   └── EloquentBlendEntryRepository.php
├── app/Models/
│   ├── BlendingEntry.php (trace header)
│   └── BlendingDetail.php (trace details)
├── domain/Events/
│   ├── BlendingStarted.php
│   ├── BlendingCompleted.php
│   └── BlendingCancelled.php
├── jobs/GenerateBulkBlendingRecords.php (queue job for large batches)
└── tests/Feature/Modules/TsBlending/AllTests.php

**FRONTEND REQUIREMENTS:**
frontend/resources/js/modules/ts-blending/
├── services/blendingService.js (all axios calls)
├── stores/blendingStore.js (Pinia: pending blends, active operations)
├── views/
│   ├── BlendingList.vue (searchable list of all blending ops)
│   ├── BlendingDetail.vue (full trace chain visualization)
│   └── BlendingCreateWizard.vue (multi-step form)
└── components/
    ├── TraceChainVisualization.vue (SVG timeline view)
    ├── MaterialSelector.vue (autocomplete for feed materials)
    └── QuantityCalculator.vue (real-time yield calculation)

**IMPLEMENTATION SEQUENCE:**

**DAY 1: Foundation Setup**
- Create all directories and stub files
- Implement database migrations (t_blend_entries, t_blend_details)
- Build Model classes with relationships
- Set up repository interfaces and Eloquent implementations
- Write unit tests for repositories (isolate from external deps)

**DAY 2: Service Layer Implementation**
- Build BlendingServiceInterface contract
- Implement core blending logic (start, process, complete)
- Integrate TraceGenerator strategies
- Add atomic CAS (compare-and-swap) for balance updates
- Implement pessimistic lock for concurrent operations
- Write service unit tests with Mockery mocks

**DAY 3: Controller + Forms + Routes**
- Create thin controllers (inject services)
- Implement FormRequest classes with custom rules
- Register routes under /api/v1/transactions/blendings
- Add middleware for auth:sanctum + plant.context scoping
- Write feature tests (integration level)

**DAY 4: Frontend Development**
- Create BlenderService.js (axios wrapper)
- Build Pinia store with pending/partial states
- Implement main views (wizard workflow)
- Add TraceChainVisualization component (SVG-based)
- Write Vitest unit tests for stores and components

**DAY 5: Integration & Polish**
- End-to-end testing (BE + FE together)
- Performance optimization (query caching for lists)
- Security audit (authorization checks)
- Documentation (API docs, user guide, runbook)
- Deploy to staging environment for UAT

**ACCEPTANCE CRITERIA:**
- [ ] Can create blending transaction from valid feed materials
- [ ] Trace numbers auto-generated correctly (8 prefix, date, rundown, plant, seq)
- [ ] Balance consumed atomically (CAS ensures no race conditions)
- [ ] Companion trace records created (feed entry with modified rundown)
- [ ] Rollback works correctly if blending cancelled mid-process
- [ ] UI shows real-time progress when queueing large batches
- [ ] All tests passing (≥80% coverage)
- [ ] API responds in <200ms for list queries, <500ms for transaction processing

**CONSTRAINTS:**
- Strict adherence to C→S→R pattern
- No hardcoded URLs or credentials
- All validation in FormRequest classes
- Service classes use Config::get() not env()
- Frontend uses composables (useTheme(), useLayout()) not DOM manipulation
- declare(strict_types=1) on ALL PHP files

**DELIVERABLES:**
1. Complete backend codebase with tests
2. Complete frontend codebase with tests  
3. API documentation (OpenAPI/Swagger)
4. Deployment playbook (migrations, seeders, env vars)
5. User training guide
6. Post-mortem document (what worked, what didn't)

**EXECUTION NOTE:**
This is a multi-day effort. Break it into daily milestones:
- Day 1 EOD: Back end foundation ready, repos working
- Day 2 EOD: Services tested, controllers wired up
- Day 3 EOD: All API endpoints functional with tests
- Day 4 EOD: Frontend working, can perform blending via UI
- Day 5 EOD: Full integration, docs, deployed to staging

Begin with Day 1 tasks. Submit progress summary at each daily milestone.
```

---

## 🔧 SETUP MULTI-EXECUTOR ENVIRONMENT

### Option 1: Multiple CLI Instances (Recommended)

**Setup:**
```bash
# Terminal 1 - Claude Code (Architecture + Review)
cd project-root
claude

# Terminal 2 - OpenCode/Gemini (Implementation)
cd project-root/opencode
openge --project .

# Terminal 3 - Cursor/Copilot (Quick edits)
# Just open VS Code with Copilot extension active
```

**Usage Pattern:**
- Claude runs `!` commands to delegate:
  ```
  !opencode "Generate CRUD for Plant module following CLAUDE.md specs"
  !copilot "Fix this linting error in PlantController.php"
  ```

### Option 2: Remote Agent Framework

**Setup:**
```yaml
# .claude/executors.yaml
executors:
  claude:
    role: "orchestrator"
    tools: ["Bash", "Grep", "Read", "Edit"]
    
  opencode:
    role: "implementer"
    tools: ["Bash", "Edit"]
    permissions:
      allow:
        - "Bash(npm *)"
        - "Bash(composer *)"
        
  cursor:
    role: "refactorer"
    tools: ["Read", "Edit"]
```

**Usage:**
```bash
# Send brief to executor agent
./scripts/delegate.sh opencode "Implement Plant CRUD"

# Executor completes and reports back
# Claude aggregates and validates
```

---

## 💰 TOKEN ECONOMICS CALCULATION

### Cost Comparison (Per Full CRUD Module)

| Scenario | Output Tokens | Input Context | Total Tokens/Month | Est. Cost |
|----------|---------------|---------------|-------------------|-----------|
| **Claude alone** | 50,000 | 50,000 (stays in memory) | 1,500,000/month | $30 |
| **Hybrid approach** | 10,000 (Claude only) | 10,000 | 300,000/month | $6 |

**Savings:** ~80% reduction in token costs  
**Time Savings:** ~60% faster development (parallelization)  
**Quality Improvement:** More focused reviews, less noise

---

## ✅ VERIFICATION CHECKLIST FOR EXECUTOR OUTPUT

Before accepting executor work:

### Code Quality
- [ ] All files have `declare(strict_types=1)`
- [ ] No hardcoded values (env() used appropriately)
- [ ] Naming conventions followed (PascalCase, snake_case)
- [ ] No debug methods left (dd(), console.log())
- [ ] Comments present for complex logic only

### Architecture Compliance
- [ ] C→S→R pattern respected
- [ ] Repository interface injected, not concrete class
- [ ] Business logic in Service, not Controller
- [ ] Validation in FormRequest only
- [ ] API responses wrapped in ApiResponse helper

### Testing Coverage
- [ ] Feature tests cover happy path + error cases
- [ ] Unit tests mock external dependencies
- [ ] Test names follow `test_it_[description]` pattern
- [ ] At least 80% line coverage achieved

### Documentation
- [ ] API endpoints documented (Swagger/OpenAPI)
- [ ] Code comments explain WHY not WHAT
- [ ] README updated with new features
- [ ] Lessons learned added to CLAUDE.md

If any item fails verification → Reject and request revision with specific feedback.

---

## 🔄 WORKFLOW TEMPLATE

### Daily Standup Format (with Executors)

```markdown
=== EXECUTOR STATUS REPORT ===

**Executor:** OpenCode (Task: Plant CRUD Implementation)
**Status:** 🟡 IN PROGRESS
**Progress:** 60% complete
- ✅ Migrations written
- ✅ Model implemented
- ✅ Repository layer done
- 🔄 Service layer in progress (80%)
- ⏳ Waiting on Service completion before Controller start

**Blockers:** None

**Next Step:** Finish Service methods, begin Controller

---

**Executor:** Claude (Orchestrator)
**Today's Focus:** Review PlantService, validate business logic
**Actions Needed:** 
- Confirm PlantService meets requirements
- Approve Service for Controller integration
- Plan tomorrow: Controller + Tests

---

**Executor:** Cursor (Quick Fixes)
**Status:** ✅ COMPLETED
**Work Done:** Fixed 3 linting errors in PlantModel.php
**Remaining:** None

=== END REPORT ===
```

---

*Collaboration Guide · Last updated: 2026-06-16 · Next review: After first hybrid sprint cycle*
