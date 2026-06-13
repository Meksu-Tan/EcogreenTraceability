/**
 * @note Naming convention mismatch: this folder is `repositories/` but should be `services/`
 *       per project convention (View → Store → Service → Axios). Renaming is deferred
 *       due to risk; treat these files as service-layer API wrappers.
 */
export { materialRepository } from './MaterialRepository'
export { rmEntryRepository } from './RmEntryRepository'
export { tankRepository } from './TankRepository'
export { supplierRepository } from './SupplierRepository'
export { wipEntryRepository } from './WipEntryRepository'
