import RmEntryApi from '@/modules/ts-raw/api'
import * as SupplierApi from '@/modules/m-supplier/api'

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
