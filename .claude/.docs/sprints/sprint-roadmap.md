# Sprint Roadmap — EODS Refactoring (Module 21 Compliant)

> **Last Updated:** 2026-06-16  
> **Alignment:** Fully aligned with Azure DevOps structure  
> **Status:** Sprint 2 IN PROGRESS

---

## 📋 AZURE DEVOPS SPRINT STRUCTURE

> **Organization:** https://dev.azure.com/eoads05  
> **Project:** pteo-traceability  
> **Total Sprints:** 4

| Sprint # | Name | Status | Start Date | End Date | Azure Epic ID |
|----------|------|--------|------------|----------|---------------|
| **1** | Migrate all master module, review if possible to get data from other App | ✅ DONE | TBD | TBD | AB#XXX |
| **2** | Migrate all transaction module, review + improvement | 🟡 DOING | 2026-06-16 | TBD | AB#XXX |
| **3** | Migrate all inquiry module, review + improvement | ⏳ TO DO | TBD | TBD | AB#XXX |
| **4** | Add chemical, packaging, etc transaction | ⏳ PLANNED | TBD | TBD | AB#XXX |

---

## 🎯 DETAILED SPRINT SCOPE

### **SPRINT 1 — Master Data Migration** ✅ COMPLETED

**Focus:** Migrate ALL master modules from reference-dont-change/ system

#### **Deliverables:**
- [x] SLOC mapping up to tank number (currently only until tank category WIP, Storage, etc.)
- [x] Add 'm_manufacturer' as traceability source (QA requirement)
- [x] Docker wrapper for containerized server deployment
- [x] Plant master data migration
- [x] Material master data migration
- [x] Supplier & Manufacturer CRUD
- [ ] Review: Integration with external app data sources

**Azure Epic:** AB#XXX (To be filled after `/start-session`)

**Current State:** 
- All deliverables marked Done in Azure Board
- Git commits tidak menggunakan format `[progress] ... AB#ID` (pre-Module 21 era)
- Documentation retroactively aligned

---

### **SPRINT 2 — Transaction Module Migration** 🟡 IN PROGRESS

**Focus:** Migrate ALL transaction modules + refactoring improvements

#### **Pending Deliverables:**
- [ ] Refactor WIP process mapping tree — dynamic from DB instead of hardcoded in `trans_wip/index.blade.php`, auto-update UI on change
- [ ] Add SO & allocated qty per batch from SAP
- [ ] Refactor RM entry include manufacturer data **(DOING)**

#### **In Progress:**
- [🔄] Refactor RM entry include manufacturer data
  - Issue: AB#XXX
  - Tasks: AB#XXX, AB#XXX, AB#XXX (fill after seed)

**Azure Epic:** AB#YYY (Sprint 2 — Transaction Module)

**Timeline:** Week 1 of 3

---

### **SPRINT 3 — Inquiry Module Migration** ⏳ PLANNED

**Focus:** Migrate all inquiry/reports modules + improvements

#### **Planned Deliverables:**
- [ ] Merge table whx -> balance
- [ ] Sync master tank data from EO-DLS to EO-TRACE **+ scheduled worker for periodic update** (DOING)
- [ ] Automate WIP, Transfer, Blending entry with scheduled worker (auto/manual fetch from EO-DLS) (DOING)
- [ ] PSPA Report implementation
- [ ] Balance Reports
- [ ] Trace Reports (backward/forward)
- [ ] Dashboard KPIs

**Azure Epic:** AB#ZZZ (Sprint 3 — Inquiry Module)

**Notes:** 
- Sync from EO-DLS: Requires API integration setup
- Scheduled workers: Laravel Task Scheduler configuration
- Merge table whx -> balance: Foreign key relationship adjustment

---

### **SPRINT 4 — Enhancements** ⏳ FUTURE

**Focus:** New transaction types and integrations

#### **Planned Features:**
- [ ] Link to SIT document & certification in RM entry by Warehouse
- [ ] LDAP login integration as alternative auth method
- [ ] Chemical tracking transactions
- [ ] Packaging transactions
- [ ] Advanced certification management
- [ ] Real-time dashboard embed

**Azure Epic:** AB#WWW (Sprint 4 — Enhancements)

---

## 📅 SPRINT TIMELINE OVERVIEW

```
Sprint 1 (Master Data)
├─ Duration: Completed
├─ Status: ✅ DONE (pre-Module 21 commits)
└─ Next: Archive

Sprint 2 (Transaction Module)
├─ Duration: 3 weeks
├─ Current Week: Week 1 of 3
├─ Status: 🟡 IN PROGRESS
└─ Next Step: Seed to Azure + Begin Module 21 workflow

Sprint 3 (Inquiry Module)
├─ Duration: TBD
├─ Status: ⏳ PLANNED
└─ Prerequisite: Sprint 2 completion

Sprint 4 (Enhancements)
├─ Duration: TBD
├─ Status: ⏳ PLANNED
└─ Prerequisite: Sprint 3 completion
```

---

## 🔗 DEPENDENCY GRAPH

```
Sprint 1 (DONE) ✅
    └── Sprint 2 (IN PROGRESS) 🟡
            ├── Sprint 3 (PLANNED) ⏳
                    └── Sprint 4 (FUTURE) ⏳
```

**Critical Dependencies:**
- Sprint 2 → Sprint 3: Transaction data required for inquiry reports
- Sprint 3 → Sprint 4: Master tank sync needed for chemical tracking
- External integrations (EO-DLS, SAP): Parallel tracks possible

---

## 📝 MODULE 21 COMPLIANCE STATUS

| Sprint | Azure Work Items Seeded | Checklists Aligned | Commit Convention | Audit Trail |
|--------|------------------------|--------------------|-------------------|-------------|
| **Sprint 1** | ⚠️ Partial | ⚠️ Retroactive | ❌ Not compliant | Manual |
| **Sprint 2** | ⏳ Pending (needs seed) | ✅ Template ready | ⏳ Will apply from now | Auto |
| **Sprint 3** | ⏳ Not seeded | ✅ Template ready | ⏳ Future sprint | Auto |
| **Sprint 4** | ⏳ Not seeded | ✅ Template ready | ⏳ Future sprint | Auto |

---

## 🎯 NEXT ACTIONS

### **Immediate (This Week):**
- [ ] Run `claude /start-session` to seed Sprint 2 work items to Azure
- [ ] Set current task (RM entry refactor) to "Doing"
- [ ] Begin Module 21 workflow for Sprint 2
- [ ] Update sprint-02.md with actual AB#IDs after seed

### **Short-term:**
- [ ] Complete Sprint 2 features (WIP refactor, SO & allocated qty, RM entry)
- [ ] Seed Sprint 3 work items when ready
- [ ] Prepare Sprint 3 documentation

### **Long-term:**
- [ ] Complete all 4 sprints
- [ ] Ensure full Module 21 compliance throughout
- [ ] Maintain audit trail via Azure ↔ Git integration

---

## 📄 RELATED DOCUMENTATION

| Document | Purpose | Location |
|----------|---------|----------|
| Sprint 1 Details | Historical reference (completed) | `.docs/sprints/sprint-01.md` |
| Sprint 2 Details | Current active sprint | `.docs/sprints/sprint-02.md` |
| Sprint 3 Planning | Upcoming sprint planning | `.docs/sprints/sprint-03.md` |
| Sprint 4 Planning | Future enhancements | `.docs/sprints/sprint-04.md` |
| Azure Setup Guide | Module 21 integration guide | `.docs/azure-devops-setup-guide.md` |
| CLAUDE.md Conventions | Project conventions | `.claude/CLAUDE.md` |

---

*EODS Sprint Roadmap • Module 21 Compliant • Last Sync: 2026-06-16*
