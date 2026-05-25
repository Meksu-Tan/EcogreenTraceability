import * as MaterialApi from '@/modules/m-material/api'

class MaterialRepository {
  async all() {
    const res = await MaterialApi.getMaterials()
    return res.data?.data ?? res.data ?? res
  }

  async getPackagings() {
    const res = await MaterialApi.getPackagings()
    return res.data?.data ?? res.data ?? res
  }

  async getSourceProducts() {
    const res = await MaterialApi.getSourceProducts()
    return res.data?.data ?? res.data ?? res
  }

  async create(data) {
    const res = await MaterialApi.storeMaterial(data)
    return res.data?.data ?? res.data ?? res
  }

  async update(id, data) {
    const res = await MaterialApi.updateMaterial(id, data)
    return res.data?.data ?? res.data ?? res
  }

  async deactivate(id) {
    const res = await MaterialApi.deactivateMaterial(id)
    return res.data?.data ?? res.data ?? res
  }

  async activate(id) {
    const res = await MaterialApi.activateMaterial(id)
    return res.data?.data ?? res.data ?? res
  }

  async createPackaging(data) {
    const res = await MaterialApi.storePackaging(data)
    return res.data?.data ?? res.data ?? res
  }

  async updatePackaging(id, data) {
    const res = await MaterialApi.updatePackaging(id, data)
    return res.data?.data ?? res.data ?? res
  }

  async deactivatePackaging(id) {
    const res = await MaterialApi.deactivatePackaging(id)
    return res.data?.data ?? res.data ?? res
  }

  async activatePackaging(id) {
    const res = await MaterialApi.activatePackaging(id)
    return res.data?.data ?? res.data ?? res
  }

  async search(query, limit = 10) {
    const res = await MaterialApi.getMaterials()
    const list = res.data?.data ?? res.data ?? res ?? []
    return list.filter(item =>
      Object.values(item).some(val =>
        String(val).toLowerCase().includes(query.toLowerCase())
      )
    ).slice(0, limit)
  }

  async getByType(type) {
    const res = await MaterialApi.getMaterials()
    const list = res.data?.data ?? res.data ?? res ?? []
    return list.filter(item =>
      item.type?.toUpperCase() === type?.toUpperCase() ||
      item.material_type?.toUpperCase() === type?.toUpperCase()
    )
  }
}

export const materialRepository = new MaterialRepository()
