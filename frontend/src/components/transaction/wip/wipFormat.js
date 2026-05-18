export function splitBadges(value) {
  if (!value) return []
  return String(value)
    .split('|')
    .map((part) => part.trim())
    .filter(Boolean)
}

export function qtyMatchClass(materialQty, supplierQty) {
  const a = parseFloat(String(materialQty ?? '').replace(/,/g, ''))
  const b = parseFloat(String(supplierQty ?? '').replace(/,/g, ''))
  if (Number.isNaN(a) || Number.isNaN(b)) return 'text-slate-700'
  return Math.abs(a - b) < 0.006 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'
}

export function todayInputDate() {
  return new Date().toLocaleDateString('fr-CA', { timeZone: 'Asia/Jakarta' })
}

export function parseTankTails(raw) {
  if (!raw) return []
  if (Array.isArray(raw)) return raw.map(String)
  if (typeof raw === 'string') {
    try {
      const parsed = JSON.parse(raw)
      return Array.isArray(parsed) ? parsed.map(String) : []
    } catch {
      return []
    }
  }
  return []
}

/** Normalize option API result (array of rows or single row) */
export function optionFirstRow(result) {
  if (result == null) return null
  if (Array.isArray(result)) return result[0] ?? null
  if (typeof result === 'object') return result
  return null
}

export function optionScalar(result, field) {
  const row = optionFirstRow(result)
  if (!row) return ''
  const val = row[field]
  return val != null ? String(val) : ''
}

/** Feed trace no from get_feedNewBatchNumber */
export function parseFeedTraceNo(result) {
  return optionScalar(result, 'feed_number')
}

/** Rundown trace no from get_rundownNewBatchNumber */
export function parseRundownTraceNo(result) {
  return optionScalar(result, 'rundown_number')
}

/** Last feed/rundown cumulative qty from get_*LastBatch */
export function parseLastBatch(result) {
  const row = optionFirstRow(result)
  if (!row) {
    return { qty: '0', entryDate: '', status: '-NORMAL-', hideLast: false }
  }
  const status = row.status ?? '-NORMAL-'
  return {
    qty: String(row.curr_qtf ?? row.last_feed ?? row.last_rundown ?? '0'),
    entryDate: row.entry_date ?? row.last_entryDate ?? '',
    status,
    hideLast: status === '-QTF-',
  }
}
