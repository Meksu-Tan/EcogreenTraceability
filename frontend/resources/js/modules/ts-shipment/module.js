import { useShipmentEntryStore } from './stores/useShipmentEntryStore'

export default {
  name: 'ts-shipment',
  routes: [
    {
      path: 'ts-shipment/shipment-entry',
      name: 'ts-shipment-shipment-entry',
      component: () => import('./views/ShipmentEntryView.vue'),
      meta: { title: 'Shipment Entry', requiresAuth: true },
    },
  ],
  stores: [useShipmentEntryStore],
}
