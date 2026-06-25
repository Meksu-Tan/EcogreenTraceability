# SPRINT-03.md — Inquiry Module Migration

> **Status:** ⏳ PLANNED  
> **Azure Sprint ID:** Sprint 3  
> **Target Start:** TBD (after Sprint 2 completion)  
> **Target End:** TBD (~2 weeks)  
> **Module 21 Alignment:** Ready (template prepped)

---

## Sprint Info

| Field | Value |
|-------|-------|
| **Sprint Number** | 3 |
| **Focus** | Migrate all inquiry module, review + improvement |
| **Azure Epic** | AB#XXX (To be assigned after seed) |
| **Current Status** | ⏳ PLANNED |
| **Team** | Backend, Frontend, QA |
| **Dependency** | Sprint 2 completed (transaction data ready) |

---

## Sprint Goal

Bangun semua inquiry/reports modules untuk read-only access ke transaction data dengan:
- ✅ Performance optimized (caching, pagination)
- ✅ Real-time sync capabilities (where needed)
- ✅ Advanced filtering & search
- ✅ Scheduled workers for periodic data refresh
- ✅ Full Module 21 compliance

**Setelah sprint ini:** User dapat melihat laporan lengkap (PSPA, balance, trace), query data real-time dari EO-DLS, dan automate WIP/Transfer/Blending entry dengan scheduled worker.

---

## Features List

### Feature 1: Merge Table WHx → Balance `[Priority: CRITICAL]`

**Azure Work Items:**
- Issue (Brief): AB#XXX (To be assigned after seed)
- Tasks: AB#XXX, AB#XXX, AB#XXX (To be assigned after seed)

**User Story:**
> Sebagai analyst, saya perlu unified view dari warehouse data (WHx tables) ke balance structure agar reporting konsisten dan accurate.

**Acceptance Criteria:**
- [ ] Mapping logic: `t_wh*` tables → `t_balance_header`, `t_balance_detail`
- [ ] Data transformation script developed
- [ ] FK relationships preserved
- [ ] Validation: No data loss during merge
- [ ] Rollback mechanism ready
- [ ] Performance test: Merge <30 minutes for full dataset
- [ ] Audit trail: Log every merge operation
- [ ] Documentation: Field mapping reference

**Technical Notes:**
- Source tables: `t_wh01`, `t_wh02`, etc. (warehouse-specific)
- Target structure: Standardized `t_balance_*` format
- Transformation rules: Location mapping, unit conversion
- Conflict resolution: Last-write-wins atau manual review?

**Out of Scope:**
- Historical WHx data preservation → Keep as-is in source tables

---

### Feature 2: Sync Master Tank Data from EO-DLS `[Priority: HIGH]` **(DOING)**

**Azure Work Items:**
- Issue (Brief): AB#XXX (To be assigned after seed)
- Tasks: AB#XXX, AB#XXX, AB#XXX (Currently "Doing")

**User Story:**
> Sebagai operator, saya perlu master tank data (capacity, level, location) ter-sync otomatis dari EO-DLS ke EO-TRACE untuk accuracy dan consistency antar sistem.

**Acceptance Criteria:**
- [ ] EO-DLS API endpoint identified & authenticated
- [ ] Periodic sync worker implemented (cron job)
- [ ] Incremental update vs full sync strategy
- [ ] Error handling: Retry failed syncs, alert on persistent failures
- [ ] Data validation: Capacity ranges, required fields
- [ ] UI indicator: Show last sync timestamp
- [ ] Performance: Sync cycle <5 minutes
- [ ] Manual trigger option for ad-hoc updates

**Technical Notes:**
- EO-DLS URL: `https://eodls.ecogreenoleo.com/api/v1/tanks` (example)
- Auth method: OAuth2 or API key (TBD with IT team)
- Sync frequency: Every 15 minutes default
- Data source: `m_tank`, `m_tank_detail` → `m_sloc`, `m_sloc_detail`
- Conflict handling: Override local changes OR mark as stale?

**Dependencies:**
- EO-DLS team availability for API spec
- Network firewall configuration (EODS ↔ EO-DLS)

**Related Issues:**
- Sprint 1: Tank → SLOC mapping completed
- Sprint 4: Chemical tracking requires accurate tank data

---

### Feature 3: Automate WIP, Transfer, Blending Entry `[Priority: HIGH]` **(DOING)**

**Azure Work Items:**
- Issue (Brief): AB#XXX (To be assigned after seed)
- Tasks: AB#XXX, AB#XXX, AB#XXX (Currently "Doing")

**User Story:**
> Sebagai production scheduler, saya perlu automatic fetch dari EO-DLS untuk WIP, Transfer, dan Blending entries sehingga manual input minim dan data real-time akurat.

**Acceptance Criteria:**
- [ ] Fetch logic from EO-DLS implemented
- [ ] Automated creation of transaction headers
- [ ] Scheduled worker runs every N minutes
- [ ] Manual override available for exceptions
- [ ] Notification: Alert jika ada pending entries to import
- [ ] Duplicate detection: Prevent double-entry
- [ ] Error logging: Track failed imports
- [ ] Configuration: Adjust frequency per transaction type

**Technical Notes:**
- EO-DLS endpoints: `/api/wip`, `/api/transfers`, `/api/blending`
- Worker implementation: Laravel Task Scheduler + Queue
- Fetch pattern: Polling every 5 minutes vs Webhook push
- Data mapping: Transform EO-DLS format → EODS internal format
- Exception handling: Quarantine table untuk invalid records

**Integration Points:**
- EO-DLS live data ↔ EODS transaction tables
- Background processing via Laravel Queues
- Real-time dashboard updates (WebSocket)

---

### Feature 4: PSPA Report `[Priority: MEDIUM]`

**Azure Work Items:**
- Issue (Brief): AB#XXX
- Tasks: AB#XXX, AB#XXX

**Acceptance Criteria:**
- [ ] Report generation from `t_report_pspa_head/tail`
- [ ] Date range filter
- [ ] Plant/product filter
- [ ] Export to PDF/Excel
- [ ] Performance: <5s for 6-month range

---

### Feature 5: Balance Reports `[Priority: MEDIUM]`

**Azure Work Items:**
- Issue (Brief): AB#XXX
- Tasks: AB#XXX, AB#XXX

**Acceptance Criteria:**
- [ ] Query-based reports over `t_balance_*`
- [ ] Stock by location/view
- [ ] Movement history
- [ ] Comparison vs previous period

---

## ✅ Checklist Selesai (Module 21 Compliance)

Track via Azure Board:

### Phase 1: Data Merge (WHx → Balance)
- [ ] Develop mapping script
- [ ] Test with staging data
- [ ] Execute merge in production
- [ ] Validate post-merge accuracy

### Phase 2: EO-DLS Integration (Tank Sync)
- [ ] Setup API credentials
- [ ] Implement sync worker
- [ ] Test incremental vs full sync
- [ ] Configure scheduled job

### Phase 3: Transaction Automation
- [ ] Build fetch logic for WIP
- [ ] Build fetch logic for Transfers
- [ ] Build fetch logic for Blending
- [ ] Set up background workers
- [ ] Test duplicate prevention

### Final Verification
- [ ] php artisan test green
- [ ] Performance benchmarks passed
- [ ] Stakeholder UAT approved
- [ ] Azure Work Items → Done
- [ ] Commit messages include AB#ID

---

## 📅 Sprint Timeline

### Week 1 (Start Date TBD)
**Focus:** EO-DLS integration setup + data merge planning

| Day | Task | Priority |
|-----|------|----------|
| Mon | Seed Sprint 3 to Azure | CRITICAL |
| Mon-Tue | EO-DLS API spec review & credential setup | CRITICAL |
| Tue-Wed | WHx → Balance mapping analysis | HIGH |
| Wed-Fri | Sync worker skeleton implementation | HIGH |

### Week 2
**Focus:** Implementation + testing

| Day | Task | Priority |
|-----|------|----------|
| Mon-Tue | Complete tank data sync worker | HIGH |
| Tue-Thu | Automate WIP, Transfer, Blending fetches | HIGH |
| Thu-Fri | PSPA & Balance report development | MEDIUM |
| Fri | Integration testing phase | MEDIUM |

---

## 🔗 Related Documentation

- **EO-DLS API Docs:** To be obtained from EO-DLS team
- **WHx Table Schema:** `reference-dont-change/schema_whx.sql`
- **Balance Structure:** `.docs/ARCHITECTURE.md` §Transaction Tables
- **Module 21 Guide:** `.docs/azure-devops-setup-guide.md`

---

## 🚨 Risks & Dependencies

### Risk 1: EO-DLS API Availability
**Issue:** External dependency on EO-DLS system uptime  
**Mitigation:** 
- Mock data for development
- Fallback to manual entry mode
- Monitor EO-DLS SLA

### Risk 2: Data Quality Issues
**Issue:** Inconsistent data in WHx tables  
**Mitigation:**
- Extensive validation before merge
- Staging environment testing first
- Clear rollback procedure documented

---

*SPRINT-03 • Inquiry Module Migration • Module 21 Compliant Template • Ready for seeding*
