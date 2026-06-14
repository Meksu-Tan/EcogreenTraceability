/**
 * @note Naming convention mismatch: this file lives in `repositories/` but should be in `services/`
 *       per project convention (View → Store → Service → Axios). Rename deferred due to risk.
 */
import RmEntryApi from '@/modules/ts-raw/services/index.js'

class SupplierRepository {
  async search(query) {
    if (query) {
      const res = await RmEntryApi.searchSuppliers(query)
      return res.data?.data ?? res.data ?? res
    }
    const res = await SupplierApi.getActiveSuppliers()
    return res.data?.data ?? res.data ?? res
  }
}

export const supplierRepository = new SupplierRepository()
