import api from '@/api/axios'

export const fetchQty = ({ idMaterial, idPlant }) =>
  api.get('/api/v1/qty/fetch', { params: { id_material: idMaterial, id_plant: idPlant } })
