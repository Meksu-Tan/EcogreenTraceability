export function formatSupplierList(val, { withStatus = false } = {}) {
  if (!val) return []
  return val.split(' | ').map(item => {
    const parts = item.split(' / ')
    const result = {
      supplier: parts[0] || '',
      batch: parts[1] || '',
      qty: (parts[2] || '').replace('Qty : ', '').replace('Qty: ', ''),
    }
    if (withStatus) result.status = parts[3] || ''
    return result
  })
}

export function formatDetailSupplier(val) {
  if (!val) return []
  return val.split(' || ').map(item => {
    const parts = item.split(' / ')
    return {
      supplier: parts[0] || '',
      batch: parts[1] || '',
      qty: (parts[2] || '').replace(' MT', '').trim(),
    }
  })
}
