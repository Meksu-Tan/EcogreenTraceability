import api from '@/api/axios'

export const fetchQty = ({ idMaterial, idPlant, idSloc }) =>
  api.get('/api/v1/qty/fetch', { params: { id_material: idMaterial, id_plant: idPlant, id_sloc: idSloc } })
