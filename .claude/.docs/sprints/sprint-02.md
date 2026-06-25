# SPRINT-02.md — Transaction Module Migration (Review + Improvement)

> **Status:** 🟡 IN PROGRESS  
> **Azure Sprint ID:** Sprint 2  
> **Start Date:** 2026-06-16  
> **Target End:** TBD (~3 weeks)  
> **Last Updated:** 2026-06-17  
> **Module 21 Alignment:** Active (seeding required)  
> **PostgreSQL Go-Live Target:** September 2026 (Phase 6)

---

## Sprint Info

| Field | Value |
|-------|-------|
| **Sprint Number** | 2 |
| **Focus** | Migrate all transaction module, review + improvement |
| **Azure Epic** | AB#XXX (To be assigned after seed) |
| **Current Status** | 🟡 DOING (Week 1 of 3) |
| **Team** | 2 Backend, 1 Frontend, 1 QA |
| **Dependency** | Sprint 1 completed (master data ready) |

---

## Sprint Goal

Migrate ALL transaction modules dari reference-dont-change/ ke new stack dengan refactoring improvements:
- ✅ Refactor WIP process mapping tree → dynamic from DB (no more hardcoded)
- ✅ Add SO & allocated qty per batch from SAP integration
- ✅ Refactor RM entry include manufacturer data
- ✅ Full Module 21 compliance from now on
- ✅ **PostgreSQL migration readiness** — semua modul harus dual-DB compatible sebelum go-live

**Setelah sprint ini:** User dapat track semua transactions (WIP, Transfer, Blending, Balance, Trace), lihat allocated qty dari SAP, dan view manufacturer details di RM entries dengan accurate real-time data. Semua modul siap switch ke PostgreSQL (target September 2026).

---

## Features List

### Feature 1: Refactor WIP Process Mapping Tree `[Priority: CRITICAL]` **(TO DO)**

**Azure Work Items:**
- Issue (Brief): AB#XXX (To be assigned after seed)
- Tasks: AB#XXX, AB#XXX, AB#XXX (To be assigned after seed)

**User Story:**
> Sebagai production planner, saya perlu WIP process tree yang dynamic dari database (tidak hardcoded) sehingga bisa auto-update UI saat ada perubahan proses tanpa deploy ulang.

**Acceptance Criteria:**
- [ ] Current state: `trans_wip/index.blade.php` has hardcoded process tree
- [ ] New implementation: Process tree stored in DB (`m_process_tree`, `m_process_steps`)
- [ ] Dynamic loading: API endpoint `/api/process-tree` returns JSON structure
- [ ] Frontend refactor: Vue component consume API vs hardcoded array
- [ ] Auto-update UI: WebSocket or polling untuk real-time changes
- [ ] Configuration UI: Admin interface untuk add/edit process steps
- [ ] Performance: Tree render <500ms
- [ ] Validation: Ensure no orphaned references

**Technical Notes:**
- **Current Problem:** Hardcoded in Blade template → requires code change untuk setiap process modification
- **Solution:** Store process hierarchy in DB, serve via REST API
- **Data Structure:** 
  ```sql
  CREATE TABLE m_process_tree (
    id UUID PRIMARY KEY,
    parent_id UUID NULL, -- recursive self-reference
    name VARCHAR(100),
    sequence INT,
    is_active BOOLEAN DEFAULT true,
    config JSON -- additional settings
  );
  
  CREATE TABLE m_process_steps (
    id UUID PRIMARY KEY,
    process_id UUID FK,
    step_name VARCHAR(100),
    operation_type ENUM('transform','wait','measure'),
    duration_minutes INT,
    quality_check_required BOOLEAN
  );
  ```
- **Frontend Component:** `<ProcessTreeViewer />` with Vuex/Pinia store
- **Real-time Update:** Laravel Echo + Pusher/SockJS

**Out of Scope:**
- Backwards compatibility for old process IDs → Deprecation plan in future sprint

---

### Feature 2: Add SO & Allocated Qty per Batch from SAP `[Priority: HIGH]` **(TO DO)**

**Azure Work Items:**
- Issue (Brief): AB#XXX (To be assigned after seed)
- Tasks: AB#XXX, AB#XXX, AB#XXX (To be assigned after seed)

**User Story:**
> Sebagai sales coordinator, saya perlu SO (Sales Order) dan allocated quantity per batch dari SAP ERP agar visible di EO-TRACE untuk coordination antara sales dan production teams.

**Acceptance Criteria:**
- [ ] SAP API endpoint identified (S4HANA or ECC?)
- [ ] Authentication method: OAuth2/SAPI credentials
- [ ] Data sync: Scheduled worker fetch SO data every N minutes
- [ ] Allocation tracking: Link SO quantities to specific batches
- [ ] Conflict resolution: What happens if SAP says "allocated" but EODS says "free"?
- [ ] UI updates: Show allocated qty per batch in balance/view screens
- [ ] Manual override option: Warehouse staff can manually adjust allocations
- [ ] Audit trail: Log all allocation changes

**Technical Notes:**
- **SAP Integration Pattern:**
  - Polling interval: Every 5 minutes
  - Batch size: Max 1000 records per fetch
  - Delta sync vs full refresh
  - Error handling: Retry logic (exponential backoff)
- **Data Model:**
  ```sql
  CREATE TABLE t_so_allocation (
    id UUID PRIMARY KEY,
    sap_order_no VARCHAR(50) UNIQUE,
    batch_id UUID FK,
    allocated_qty DECIMAL(18,6),
    available_qty DECIMAL(18,6),
    reserved_until DATETIME,
    status ENUM('pending','confirmed','released')
  );
  ```
- **Integration Point:** SAP → EODS (one-way sync)
- **Validation:** Cross-check against existing inventory levels

**Dependencies:**
- SAP team availability for API spec
- Network configuration (EODS ↔ SAP gateway)

---

### Feature 3: Refactor RM Entry Include Manufacturer Data `[Priority: CRITICAL]` **(DOING)** ⚡ ACTIVE

**Azure Work Items:**
- Issue (Brief): AB#XXX (Currently "Doing")
- Tasks: AB#XXX, AB#XXX, AB#XXX (Currently "Doing")

**User Story:**
> Sebagai warehouse operator, saya perlu view manufacturer details langsung di RM entry form/table sehingga traceability lengkap dan bisa filter by supplier/manufacturer quickly.

**Acceptance Criteria:**
- [ ] Current state: RM entry hanya punya supplier_id, tidak ada manufacturer linkage
- [ ] New schema: Add `manufacturer_id` field to `t_rm_entry_header`
- [ ] Join logic: Supplier → Manufacturer relationship established
- [ ] UI enhancement: Display manufacturer name alongside supplier
- [ ] Filter capability: Filter RM entries by manufacturer
- [ ] Search capability: Search by manufacturer name
- [ ] Reporting impact: Reports updated to show manufacturer breakdown
- [ ] Import validation: Legacy data migration script (supplier-only records get default/null manufacturer)

**Technical Notes:**
- **Data Source:** `m_manufacturer` table already exists from Sprint 1 ✅
- **Relationship:** `m_supplier` has optional `manufacturer_id` FK → one supplier can belong to one manufacturer
- **Migration Strategy:**
  - Existing records without manufacturer → keep `null` temporarily
  - Bulk update script to match supplier→manufacturer mappings
  - Manual review for unmatched suppliers
- **UI Changes:**
  - Form: Add dropdown for manual manufacturer selection
  - Table: Add "Manufacturer" column
  - Detail view: Show manufacturer logo + contact info
- **Performance Impact:** One additional LEFT JOIN per query

**Current Progress:**
- ✅ Schema analysis complete
- 🔄 Implementing migration script
- ⏳ Waiting for stakeholder confirmation on data matching rules

**Related Issues:**
- Sprint 1: `m_manufacturer` table added as traceability source
- This feature: Leverage manufacturer table for RM entry traceability

---

---

## Feature 4: PostgreSQL Migration Readiness `[Priority: HIGH]` **(DONE ✅)**

**Azure Work Items:**
- Issue (Brief): AB#XXX (PostgreSQL dual-DB compatibility)

**User Story:**
> Sebagai DevOps engineer, saya perlu semua modul backend kompatibel dengan PostgreSQL agar bisa go-live production di September 2026 tanpa downtime extended.

**Acceptance Criteria:**
- [x] Semua MySQL-specific SQL patterns diidentifikasi di 22+ repository/trait files
- [x] `DbCompatTrait` dibuat sebagai single helper untuk dual-DB (`GROUP_CONCAT`/`STRING_AGG`, `DATE_FORMAT`/`TO_CHAR`, `CURDATE`/`CURRENT_DATE`, dll)
- [x] Semua `IFNULL()` → `COALESCE()` (universal)
- [x] Semua `GROUP_CONCAT()` → `$this->dbGroupConcat()` (branched)
- [x] Semua `DATE_FORMAT()` → `$this->dbDateFormat()` (branched)
- [x] Semua `COLLATE utf8mb4_unicode_ci` → dihapus (TankQueryRepository, BlendingService)
- [x] Semua `CAST(... AS UNSIGNED)` → branched `INTEGER` untuk PG (RmEntryQueryTrait, RmEntryTransferTrait, TransferRepository)
- [x] Semua backtick identifiers → dihapus
- [x] PHPUnit 522 tests passed setelah semua perubahan
- [x] Dual-DB: MySQL (`eudr_ts`) + PostgreSQL (`eudr_ts_pg`) aktif bersamaan
- [ ] Frontend integration test vs PostgreSQL: pending (Go-Live Gate)
- [ ] Staging deployment + 24h monitoring: **Phase 6 — September 2026**
- [ ] Production cutover: **Phase 6 — September 2026**

**Technical Notes:**
- `DbCompatTrait` ada di `Modules/Shared/Traits/DbCompatTrait.php`
- Connection PG: `eudr_ts_pg` (env: `DB_PG_*` vars)
- Migrasi PG ada di `backend/database/migrations/pgsql/` (22 files)
- Detail lengkap: `.claude/POSTGRESQL-MIGRATION.md`

**Go-Live Gate (sebelum Phase 6 boleh jalan):**
- [ ] Semua module feature-complete
- [ ] Full test suite green (PHPUnit + Vitest)
- [ ] Business logic validation manual (EUDR ledger)
- [ ] Performance benchmark (< 500ms per query)
- [ ] Staging deployment sukses + 24h monitoring clean
- [ ] Stakeholder approval

---

## 📅 Sprint Timeline (Week-by-Week)

### Week 1 (2026-06-16 to 2026-06-22)
**Focus:** RM entry refactor (current priority) + Planning for other features

| Day | Task | Priority | Status | Azure ID |
|-----|------|----------|--------|----------|
| Mon | Seed Sprint 2 to Azure | CRITICAL | ❌ Pending | AB#XXX |
| Mon-Wed | RM entry refactoring | CRITICAL | 🔄 Doing | AB#XXX |
| Wed | Complete RM entry unit tests | HIGH | ⏳ Pending | AB#XXX |
| Thu-Fri | Start WIP process tree design | MEDIUM | ⏳ Pending | AB#XXX |

### Week 2 (2026-06-23 to 2026-06-29)
**Focus:** WIP process tree implementation

| Day | Task | Priority | Status | Azure ID |
|-----|------|----------|--------|----------|
| Mon-Tue | Database schema for process tree | HIGH | ⏳ Not Started | AB#XXX |
| Tue-Thu | API endpoints development | HIGH | ⏳ Not Started | AB#XXX |
| Thu-Fri | Frontend component development | HIGH | ⏳ Not Started | AB#XXX |

### Week 3 (2026-06-30 to 2026-07-06)
**Focus:** SO allocation + Testing + Sign-off

| Day | Task | Priority | Status | Azure ID |
|-----|------|----------|--------|----------|
| Mon-Tue | SAP integration setup | HIGH | ⏳ Not Started | AB#XXX |
| Wed-Thu | SO data sync implementation | HIGH | ⏳ Not Started | AB#XXX |
| Fri | Final testing + UAT preparation | MEDIUM | ⏳ Not Started | AB#XXX |

---

## ✅ Checklist Selesai (Module 21 Compliance)

Track progress via Azure Board:

### Phase 1: RM Entry Refactor **(CURRENTLY IN PROGRESS)**
- [x] Schema analysis complete
- [ ] Migration script for legacy data
- [ ] Backend: Add `manufacturer_id` to RM entry tables
- [ ] Backend: Update relationships & validation
- [ ] Frontend: Add manufacturer fields to forms
- [ ] Frontend: Add manufacturer column to tables
- [ ] Frontend: Filter/search by manufacturer
- [ ] Tests: Unit + Feature tests green
- [ ] UAT: Stakeholder approval

### Phase 2: WIP Process Tree
- [ ] DB schema design
- [ ] API endpoints (GET, POST, PUT, DELETE)
- [ ] Real-time update mechanism
- [ ] Admin UI for process configuration
- [ ] Performance optimization
- [ ] Testing coverage ≥80%

### Phase 3: SAP Integration (SO Allocation)
- [ ] SAP API spec finalization
- [ ] Authentication setup
- [ ] Sync worker implementation
- [ ] Conflict resolution logic
- [ ] UI updates for allocated qty display
- [ ] Manual override mechanism
- [ ] Performance testing

### Phase 4: PostgreSQL Migration Readiness **(DONE ✅ — 2026-06-17)**
- [x] `DbCompatTrait` created with dual-DB helpers
- [x] 22+ repository/trait files audited and fixed
- [x] COLLATE removed: TankQueryRepository, BlendingService
- [x] CAST UNSIGNED branched: RmEntryQueryTrait, RmEntryTransferTrait, TransferRepository
- [x] PHPUnit: 522 tests passed, 0 failures
- [x] Dual-DB connections: MySQL `eudr_ts` + PostgreSQL `eudr_ts_pg`
- [x] All 22 tables in PostgreSQL `eudr_dev`
- [x] Data migrated: 192,916 rows (100% match)

### PostgreSQL Go-Live Gate (September 2026)
- [ ] Semua module coding selesai
- [ ] Full test suite green (PHPUnit + Vitest)
- [ ] Manual QA EUDR ledger workflows
- [ ] Staging deployment sukses
- [ ] 24h monitoring on staging — zero errors
- [ ] Performance: < 500ms semua major queries
- [ ] Stakeholder UAT sign-off
- [ ] Rollback plan tested
- [ ] Phase 6 execution: production cutover

### Final Verification
- [ ] php artisan test green
- [ ] php artisan migrate fresh (clean environment test)
- [ ] Performance benchmarks (<2s queries)
- [ ] Security audit passed
- [ ] Documentation complete
- [ ] Azure Work Items → Done
- [ ] Commit messages include AB#ID format
- [ ] Stakeholder UAT approved
- [ ] Handoff to operations team

---

## 🧪 Testing Requirements

### Unit Tests
```php
// RM Entry Manufacturer Test
test('rm_entry_has_manufacturer_data()') {
    $entry = RmEntry::create([
        'supplier_id' => $supplier->id,
        'manufacturer_id' => $manufacturer->id,
        // ...
    ]);
    
    $this->assertEquals($manufacturer->id, $entry->manufacturer_id);
}
```

### Integration Tests
```javascript
// WIP Process Tree Dynamic Loading
test('process tree updates when API response changes', async () => {
  const mockResponse = { /* updated tree structure */ };
  
  // Mock API call
  axios.get.mockResolvedValue(mockResponse);
  
  await wrapper.findAll('li');
  
  // Verify UI reflects new structure
  expect(wrapper.html()).toContain(expectedStructure);
});
```

### Acceptance Criteria Checklist
- [ ] RM entry shows manufacturer alongside supplier
- [ ] Filter by manufacturer works correctly
- [ ] Search by manufacturer returns accurate results
- [ ] WIP tree reloads dynamically without page refresh
- [ ] SAP SO data appears in balance view
- [ ] Allocated qty reduces available stock automatically
- [ ] All 100+ test cases pass
- [ ] Load time <2s for all major pages

---

## 🔗 Related Documentation

- **WIP Process Design:** `.context/wip-process-refactoring.md`
- **SAP Integration Spec:** To be obtained from IT team
- **Manufacturer Table Schema:** `.docs/data-models/m_manufacturer.md`
- **RM Entry Architecture:** `.docs/architecture/transactions/rm-entry.md`
- **Module 21 Guide:** `.docs/azure-devops-setup-guide.md`

---

## 🚨 Risks & Dependencies

### Risk 1: SAP Integration Complexity
**Issue:** SAP API specifications may be incomplete or change mid-sprint  
**Mitigation:**
- Engage SAP team early for detailed specs
- Build flexible adapter pattern
- Have manual import fallback option

### Risk 2: Data Quality in Legacy Records
**Issue:** Existing RM entries without manufacturer info  
**Mitigation:**
- Create bulk update utility
- Allow null values initially
- Flag for manual review

### Risk 3: Performance Degradation with Real-Time Updates
**Issue:** WIP tree WebSocket connections could slow down browser  
**Mitigation:**
- Lazy loading for large trees
- Debounced updates (max 1 update/sec per user)
- Fallback to polling if WebSocket unavailable

---

## 💬 Team Communication

### Standup Template (Daily):
```
Yesterday:
- Completed RM entry schema migration draft
- Started WIP tree API endpoint design

Today:
- Finish migration script
- Test with staging data
- Review PR for manufacturer dropdown component

Blockers:
- Need SAP API docs to proceed with SO allocation
```

---

## 📊 Progress Metrics

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| Feature Completion | 100% | 33% (RM entry in progress) | ⏳ On Track |
| Test Coverage | ≥80% | 65% (estimated) | 🔄 Building |
| Code Review | 2 approvals each PR | In progress | ✅ Compliant |
| Azure State Sync | Daily | Needs first seed | ⚠️ Action Required |

---

---

## 🗓️ PostgreSQL Go-Live Roadmap

| Milestone | Target | Status |
|-----------|--------|--------|
| Phase 1-2: Schema migration | 2026-06-17 | ✅ Done |
| Phase 3: Code updates (DbCompatTrait) | 2026-06-17 | ✅ Done |
| Phase 4: Data migration (192k rows) | 2026-06-17 | ✅ Done |
| Phase 5: Testing & validation | 2026-06-17 (ongoing) | 🟡 PHPUnit ✅, UI pending |
| Feature coding completion | ~July-August 2026 | 🔄 In Progress |
| Phase 6 Staging deployment | August 2026 | ⏳ Pending |
| Phase 6 Production go-live | **September 2026** | 🎯 Target |

---

*SPRINT-02 • Transaction Module Migration • Module 21 Compliant • PostgreSQL Go-Live: Sep 2026 • Last Sync: 2026-06-17*
