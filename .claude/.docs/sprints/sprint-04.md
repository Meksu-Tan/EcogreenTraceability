# SPRINT-04.md — Chemical & Packaging Enhancements

> **Status:** ⏳ PLANNED  
> **Azure Sprint ID:** Sprint 4  
> **Target Start:** TBD (after Sprint 3 completion)  
> **Target End:** TBD (~3 weeks)  
> **Module 21 Alignment:** Ready (template prepped)

---

## Sprint Info

| Field | Value |
|-------|-------|
| **Sprint Number** | 4 |
| **Focus** | Add chemical, packaging, etc transaction + enhancements |
| **Azure Epic** | AB#XXX (To be assigned after seed) |
| **Current Status** | ⏳ PLANNED |
| **Team** | Backend, Frontend, QA |
| **Dependency** | Sprint 3 completed (inquiry data ready) |

---

## Sprint Goal

Implement ENHANCED features beyond reference system:
- ✅ Chemical tracking with composition batches
- ✅ Advanced packaging management with QC integration
- ✅ SAP & EO-DLS deep integrations
- ✅ LDAP authentication support
- ✅ Document certification linkage
- ✅ Full Module 21 compliance

**Setelah sprint ini:** User dapat track material kimia dengan SDS compliance, manage packaging materials dengan auto-QC, link certifications ke RM entries, dan login via LDAP alternative.

---

## Features List

### Feature 1: Chemical Transactions `[Priority: CRITICAL]`

**Azure Work Items:**
- Issue (Brief): AB#XXX (To be assigned after seed)
- Tasks: AB#XXX, AB#XXX, AB#XXX (To be assigned after seed)

**User Story:**
> Sebagai quality manager, saya perlu chemical transactions ter-track dengan composition batches, SDS documentation, dan compliance requirements agar memenuhi regulatory standards.

**Acceptance Criteria:**
- [ ] Chemical master data structure added (`m_chemical`, `m_chemical_composition`)
- [ ] Transaction types: Chemical input, output, blend, waste
- [ ] Composition tracking: Recipe-based batching
- [ ] SDS document linkage per chemical batch
- [ ] Compliance validation: Regulatory checks before approval
- [ ] Traceability: Full chemical flow history
- [ ] Reporting: Chemical usage by batch/product
- [ ] Alerts: Low stock or expired SDS notifications

**Technical Notes:**
- New tables: `t_chemical_header`, `t_chemical_detail`, `m_chemical`, `m_sds`
- Composition format: JSON-based recipe schema
- SDS storage: File uploads + metadata indexing
- Compliance rules: Configurable business rules engine
- Integrations: EO-DLS chemical catalog sync (future)

**Out of Scope:**
- Chemical formulation R&D tools → Future Phase 5
- Hazardous material handling workflows → Regulatory requirement review

---

### Feature 2: Packaging Transactions `[Priority: HIGH]`

**Azure Work Items:**
- Issue (Brief): AB#XXX (To be assigned after seed)
- Tasks: AB#XXX, AB#XXX, AB#XXX (To be assigned after seed)

**User Story:**
> Sebagai warehouse supervisor, saya perlu packaging materials tracked per production batch dengan QC checks untuk memastikan correct packaging selection and quality compliance.

**Acceptance Criteria:**
- [ ] Packaging master data (`m_packaging_materials`, `m_packaging_specs`)
- [ ] Transaction types: Packaging issue, return, spoilage
- [ ] Per-batch assignment: Link packaging to production batches
- [ ] QC integration: Automatic pass/fail based on specs
- [ ] Reorder alerts: Low stock warnings
- [ ] Barcode scanning support (future enhancement)
- [ ] Usage analytics: Packaged units vs material used
- [ ] Cost tracking: Packaging cost per unit

**Technical Notes:**
- Integration point: Production schedule from ERP
- QC rules: Configurable thresholds (dimensions, weight, material grade)
- Barcode support: TUI library for label generation
- Real-time inventory: WebSocket updates for packaging stock levels

**Related Features:**
- Chemical tracking (same phase)
- WIP automation (linked in Sprint 3)

---

### Feature 3: Link SIT & Certification in RM Entry `[Priority: HIGH]` **(TO DO)**

**Azure Work Items:**
- Issue (Brief): AB#XXX (To be assigned after seed)
- Tasks: AB#XXX, AB#XXX (Currently "Pending")

**User Story:**
> Sebagai warehouse staff, saya perlu upload dan link certificates/SDS documents ke Raw Material entries sehingga traceability lengkap dan audit-ready.

**Acceptance Criteria:**
- [ ] Document upload per RM entry (`rm_entry.documents` pivot table)
- [ ] Document types: SIT, Certificate of Analysis, SDS, COC
- [ ] Expiry tracking: Auto-alert sebelum expiration
- [ ] Document validation: Format checks (PDF, JPG), size limits
- [ ] Version control: Multiple versions per document
- [ ] Access control: Role-based viewing permissions
- [ ] Search functionality: Filter by document type/date
- [ ] Download/export: Bulk download for audits

**Technical Notes:**
- Storage: Azure Blob Storage or AWS S3
- Indexing: Elasticsearch for fast document search
- Integration: EO-DLS document repository sync (optional)
- Security: Signed URLs for secure access
- Workflow: Approval flow for uploaded documents

**Dependencies:**
- File storage service setup
- Document viewer component (frontend)

---

### Feature 4: LDAP Login Integration `[Priority: MEDIUM]` **(TO DO)**

**Azure Work Items:**
- Issue (Brief): AB#XXX (To be assigned after seed)
- Tasks: AB#XXX, AB#XXX (Currently "Pending")

**User Story:**
> Sebagai IT admin, saya perlu LDAP sebagai alternative auth method sehingga users bisa login dengan corporate credentials tanpa password reset overhead.

**Acceptance Criteria:**
- [ ] LDAP configuration module (`ldaprecord-laravel` integration)
- [ ] Dual auth option: SQL + LDAP during login
- [ ] LDAP user sync: Auto-create/update user records
- [ ] Group-based permission mapping (AD groups → EODS roles)
- [ ] Password fallback: SQL for LDAP failures
- [ ] Session management: LDAP token refresh
- [ ] Audit logging: All LDAP auth attempts
- [ ] Admin UI: LDAP config page + test connection

**Technical Notes:**
- Auth flow: Email → SQL, Username → LDAP (if enabled)
- Directory: Active Directory or OpenLDAP
- Sync interval: On-demand + scheduled every 24 hours
- Fallback strategy: SQL only if LDAP unavailable
- Security: LDAPS (SSL/TLS) required

**Integration Points:**
- Corporate AD forest
- Existing user database (merge by username/email)
- RBAC system (map AD groups to EODS roles)

---

### Feature 5: Advanced Dashboard KPIs `[Priority: LOW]`

**Azure Work Items:**
- Issue (Brief): AB#XXX
- Tasks: AB#XXX

**Acceptance Criteria:**
- [ ] Real-time KPI cards: Stock levels, pending orders, alerts
- [ ] Charts: Trend analysis, comparisons
- [ ] Widget customization: User-configurable layout
- [ ] Export capability: Snapshot PDF/Excel
- [ ] Mobile-responsive design

---

## ✅ Checklist Selesai (Module 21 Compliance)

Track via Azure Board:

### Phase 1: Chemical Tracking
- [ ] Chemical master data structure
- [ ] Transaction types implementation
- [ ] Composition batch tracking
- [ ] SDS document linkage
- [ ] Compliance validation rules
- [ ] Alerting system setup

### Phase 2: Packaging Enhancement
- [ ] Packaging master data
- [ ] Per-batch assignment logic
- [ ] QC integration framework
- [ ] Reorder alerting
- [ ] Cost tracking implementation

### Phase 3: Document Management
- [ ] Upload interface per RM entry
- [ ] Document types taxonomy
- [ ] Expiry tracking logic
- [ ] Access control implementation
- [ ] Search functionality

### Phase 4: LDAP Integration
- [ ] LDAP configuration module
- [ ] User sync mechanism
- [ ] Group-to-role mapping
- [ ] Fallback auth strategy
- [ ] Test connection UI

### Final Verification
- [ ] php artisan test green
- [ ] Security audit complete
- [ ] Performance benchmarks passed
- [ ] Stakeholder UAT approved
- [ ] Azure Work Items → Done
- [ ] Commit messages include AB#ID

---

## 📅 Sprint Timeline

### Week 1 (Start Date TBD)
**Focus:** Chemical & Packaging foundation

| Day | Task | Priority |
|-----|------|----------|
| Mon | Seed Sprint 4 to Azure | CRITICAL |
| Mon-Tue | Chemical data structure design | CRITICAL |
| Tue-Wed | Packaging data structure design | HIGH |
| Wed-Fri | Document upload interface (Phase 1) | HIGH |

### Week 2
**Focus:** Implementation core features

| Day | Task | Priority |
|-----|------|----------|
| Mon-Tue | Chemical transactions CRUD | HIGH |
| Tue-Thu | Packaging transactions CRUD | HIGH |
| Thu-Fri | Document management backend | HIGH |

### Week 3
**Focus:** LDAP + Advanced features

| Day | Task | Priority |
|-----|------|----------|
| Mon-Tue | LDAP integration setup | MEDIUM |
| Tue-Thu | LDAP UI + group mapping | MEDIUM |
| Thu-Fri | Dashboard KPIs + final testing | LOW |

---

## 🔗 Related Documentation

- **Chemical Data Model:** `.docs/data-models/chemical-tracking.md` (to be created)
- **Packaging Specs:** `.docs/data-models/packaging-materials.md` (to be created)
- **Document Storage:** `.docs/infrastructure/document-management.md` (to be created)
- **LDAP Integration:** `.docs/integrations/ldap-authentication.md` (to be created)
- **Module 21 Guide:** `.docs/azure-devops-setup-guide.md`

---

## 🚨 Risks & Dependencies

### Risk 1: Chemical Regulation Compliance
**Issue:** Changes in chemical safety regulations mid-sprint  
**Mitigation:**
- Modular rule engine (easy update)
- Regular compliance review meetings
- External consultant availability

### Risk 2: LDAP Integration Complexity
**Issue:** Corporate AD structure complexity  
**Mitigation:**
- Engage IT security team early
- Staging environment for testing
- Phased rollout plan

### Risk 3: Document Storage Costs
**Issue:** High storage costs for uploaded files  
**Mitigation:**
- Tiered storage strategy (hot/warm/cold)
- Compression before upload
- Retention policies automated

---

## 🎯 Post-Sprint 4 Considerations

### Future Enhancements (Not in Sprint 4):
- AI-powered chemical formulation recommendations
- Blockchain-based certification verification
- IoT sensor integration for real-time tank levels
- Mobile app for field operations
- Predictive analytics for stock optimization

### Technical Debt Cleanup:
- Legacy report exports → New reporting engine
- Manual entry fields → Automated imports
- Hardcoded rules → Configuration-driven system

---

*SPRINT-04 • Chemical & Packaging Enhancements • Module 21 Compliant Template • Ready for seeding*
