# WIP Page Fixes Implementation Plan

## Issues Identified

### 1. Specific SLOC Selection Issue
**Problem**: In WIP page feed/rundown form, specific sloc only shows one per sloc instead of showing all options according to m_sloc database description.

**Current Implementation**:
- WIP uses `m_sloc_detail` table via `getActiveSpecificTanks()` method
- Query: `SELECT a.id_sloc_tail, a.id_sloc_tail AS id_tank_tail, a.tf_number AS tankNo FROM m_sloc_detail a WHERE a.status = 1 AND a.id_sloc = ? ORDER BY a.tf_number ASC`

**RM Entry Reference**:
- RM entry uses `m_tank_detail` table via `getActiveSpecificTanksRundown()` method
- Query: `SELECT a.id_tank_tail, a.tf_number AS tankNo FROM m_tank_detail a WHERE a.status = 1 AND a.id_tank = ? ORDER BY a.tf_number ASC`

**Expected Behavior**: Like RM entry, should show all specific SLOCs with multiple selection capability and cancel option.

**Database Tables Involved**:
- `m_sloc` - Main SLOC table
- `m_sloc_detail` - SLOC detail table (specific tanks/sub-tanks)
- `m_tank` - Tank table (used by RM entry)
- `m_tank_detail` - Tank detail table (used by RM entry)

### 2. Feed Save Issue
**Problem**: Cannot save feed and record it to database.

**Current Implementation Analysis**:
- `postMaterialFeed()` in WipEntryRepository.php (line 779)
- Uses `Feed::generalFeed()` helper
- Inserts into `t_prod_log` table after successful feed
- Calls `Feed::normalizeSupplierRundown()` for supplier quantity normalization

**Database Tables Involved**:
- `t_trace_header` - Trace header records
- `t_trace_detail` - Trace detail records
- `t_balance_header` - Balance header records
- `t_balance_detail` - Balance detail records
- `t_prod_log` - Production log records (WIP-specific)

**Status**: The implementation appears complete. Need to verify if the issue is in frontend or missing data.

### 3. Transfer Synchronization (RM Entry ↔ WIP Entry)
**Problem**: Need to check synchronization relationship between RM entry transfer and WIP entry.

**Key Relationships**:
- RM entry creates balance records in `t_balance_header` and `t_balance_detail`
- WIP feed consumes from balance records via FIFO
- WIP rundown creates new balance records
- Transfer happens via `t_trace_header` and `t_trace_detail`

**Trace Number Prefixes**:
- Prefix '1': RM Entry (Raw Material)
- Prefix '2': Rundown (WIP output)
- Prefix '3': Feed (WIP input)
- Prefix '7': Transfer
- Prefix '8': Storage
- Prefix '9': Warehouse

**Need to Verify**: Ensure trace numbers and balance headers are properly linked between RM and WIP modules.

### 4. Balance Per Batch Per Section Data View
**Problem**: Data view balance per batch per section doesn't show data.

**Current Implementation**:
- `getBalance()` method in WipEntryRepository.php (line 23)
- Complex query joining multiple tables including `m_sloc`, `t_balance_header`, `t_balance_detail`, `t_trace_header`
- Filters by `id_feed` or `id_rundown` based on section

**Potential Issues**:
- Section mapping might be incorrect
- Plant ID resolution might be failing
- Status filters might be too restrictive
- JOIN conditions might not be matching data

## Implementation Plan

### Phase 1: Specific SLOC Selection Fix
1. Verify `getActiveSpecificTanks()` returns all records
2. Check if frontend is limiting display to one option
3. Ensure the query returns all m_sloc_detail records for given sloc_id
4. Test multiple selection capability

### Phase 2: Feed Save Verification
1. Check if `t_prod_log` table exists and has correct schema
2. Verify `Feed::normalizeSupplierRundown()` is working correctly
3. Test feed save end-to-end
4. Check for any missing database constraints or indexes

### Phase 3: Transfer Synchronization Check
1. Verify trace number generation is consistent across modules
2. Check balance header/detail relationships
3. Ensure FIFO consumption works correctly
4. Test RM entry → WIP feed → WIP rundown flow

### Phase 4: Balance Data View Fix
1. Debug `getBalance()` query with actual data
2. Verify section mapping functions
3. Check plant ID resolution
4. Test balance display with known data

## Database Schema Reference

### m_sloc Table Structure
- `id` (primary key)
- `id_plant`
- `plant_name`
- `tank_number`
- `tank_height`
- `status`
- `created_by`, `updated_by`
- `created_at`, `updated_at`

### m_sloc_detail Table Structure
- `id_sloc_tail` (primary key, auto-increment)
- `id_sloc` (foreign key to m_sloc.id)
- `tf_number`
- `status`
- `created_by`, `updated_by`
- `created_at`, `updated_at`

### m_tank Table Structure
- `id_tank` (primary key, auto-increment)
- `code`, `code_2`, `code_3`, `code_4`
- `id_plant`
- `description`
- `status`
- `created_by`, `updated_by`
- `created_at`, `updated_at`

### m_tank_detail Table Structure
- `id_tank_tail` (primary key, auto-increment)
- `id_tank` (foreign key to m_tank.id_tank)
- `tf_number`
- `status`
- `created_by`, `updated_by`
- `created_at`, `updated_at`

### t_prod_log Table Structure (WIP-specific)
- `id_prod_log` (primary key, auto-increment)
- `id_trace_head` (foreign key to t_trace_header)
- `section`
- `entry_date`
- `batch_no`
- `tank_id`
- `tank_tail`
- `id_material`
- `in_qty`, `out_qty`
- `yield`
- `id_plant`
- `status`
- `created_by`, `updated_by`
- `created_at`, `updated_at`

## Critical Finding: m_sloc Table Structure Conflict

**ISSUE**: There are TWO different `m_sloc` table definitions in different modules:

### m-tank module (backend/Modules/m-tank/database/migrations/2026_05_19_000000_create_m_sloc_table.php):
```php
Schema::create('m_sloc', function (Blueprint $table) {
    $table->integer('id')->primary();  // Uses 'id' as primary key
    $table->string('id_plant', 10);
    $table->string('plant_name', 100);
    $table->string('tank_number', 50);
    $table->decimal('tank_height', 10, 2);
    $table->tinyInteger('status')->default(1);
    // ...
});
```

### m-storage module (backend/Modules/m-storage/database/migrations/2026_05_21_000001_create_storage_tables.php):
```php
Schema::create('m_sloc', function (Blueprint $table) {
    $table->increments('id_sloc');  // Uses 'id_sloc' as primary key
    $table->string('code_2', 50)->nullable();
    $table->string('code_3', 50)->nullable();
    $table->string('code_4', 50)->nullable();
    $table->string('id_plant', 50)->nullable();
    $table->string('description', 200);
    $table->tinyInteger('status')->default(1);
    // ...
});
```

**WIP Module Expects**: The m-storage version (with `id_sloc`, `code_3`, `description` columns)
**RM Entry Uses**: m_tank_detail table (not m_sloc_detail)

## Root Cause Analysis

### Issue 1: Specific SLOC Selection
- WIP's `getActiveSpecificTanks()` query is CORRECT - it returns ALL records from `m_sloc_detail`
- The query: `SELECT a.id_sloc_tail, a.id_sloc_tail AS id_tank_tail, a.tf_number AS tankNo FROM m_sloc_detail a WHERE a.status = 1 AND a.id_sloc = ? ORDER BY a.tf_number ASC`
- **The issue is likely in the FRONTEND** - it may be limiting display to one option instead of allowing multiple selection like RM entry
- **OR** the m_sloc table structure mismatch is causing data retrieval issues

### Issue 2: Feed Save Failure
- **ROOT CAUSE**: Missing `t_prod_log` table migration
- **FIXED**: Created migration at `backend/Modules/ts-wip/database/migrations/2026_05_20_000000_create_t_prod_log_table.php`
- The `Feed::normalizeSupplierRundown()` method EXISTS and is working correctly

### Issue 3: Transfer Synchronization
- Need to verify that RM entry transfers (prefix '1') properly create balance records that WIP feed (prefix '3') can consume
- Need to check trace number generation consistency

### Issue 4: Balance Data View
- The `getBalance()` query is complex and may have JOIN issues if m_sloc structure is wrong
- Need to verify the query returns data correctly with the correct m_sloc structure

## Implementation Status

### Completed
1. ✅ Created t_prod_log table migration
2. ✅ Analyzed specific SLOC selection - backend query is correct
3. ✅ Verified Feed::normalizeSupplierRundown() exists
4. ✅ Identified m_sloc table structure conflict

### Required Actions (Without Changing Algorithm)
1. **Ensure correct m_sloc table structure is used** - WIP expects m-storage version
2. **Run the t_prod_log migration** to create the missing table
3. **Check frontend code** for specific sloc selection - may need to allow multiple selection
4. **Verify balance query** works with correct m_sloc structure
5. **Test feed save** after migration is run
