import RmEntryApi from '@/api/transactionRmEntry'
import * as SupplierApi from '@/api/setupSupplier'

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
