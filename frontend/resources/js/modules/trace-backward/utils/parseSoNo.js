/**
 * parseSoNo — extract Sales Order number & item from a combined so_no string.
 *
 * The legacy so_no column may contain formats like:
 *   - "100001"               (plain SO number, no item)
 *   - "100001-10"            (SO-SOItem combined)
 *   - "100001-10,11,12"      (SOItem list comma-separated)
 *
 * Returns:
 *   { soNo: string, soItem: string, batchNo: string }
 *   - soNo:   the base SO number (digits only, no zero-padding mutation)
 *   - soItem: 5-digit zero-padded SO item, suffixed with '0' if not already
 *             ends-with-0. Falls back to 'No Doc' when no item is encoded.
 *   - batchNo: passthrough of the row.batch_no for convenience
 */
export function parseSoNo(soNoRaw = '', batchNo = '') {
  let soNo = soNoRaw || ''
  let soItem = 'No Doc'

  if (soNo.includes('-')) {
    const parts = soNo.split(/[-,]/)
    soNo = parts[0]
    soItem = parts[1].toString()
    soItem = !soItem.endsWith('0')
      ? soItem.padStart(5, '0') + '0'
      : soItem.padStart(5, '0')
  }

  return { soNo, soItem, batchNo }
}
