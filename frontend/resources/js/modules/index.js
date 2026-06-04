/**
 * Module Registry — central index of all frontend modules.
 *
 * Mirrors the backend nWise module structure.
 * Each entry corresponds to a directory under @/modules/<name>/
 *
 * Usage:
 *   import { MODULES } from '@/modules'
 *   console.log(MODULES.material.name)  // 'material'
 */

export const MODULES = {
  auth:             () => import('./auth/module'),
  'm-material':     () => import('./m-material/module'),
  'm-storage':     () => import('./m-storage/module'),
  'm-supplier':     () => import('./m-supplier/module'),
  'm-manufacturer': () => import('./m-manufacturer/module'),
  'm-tank':         () => import('./m-tank/module'),
  'm-plant':        () => import('./m-plant/module'),
  'ts-raw':         () => import('./ts-raw/module'),
  'ts-blending':    () => import('./ts-blending/module'),
  'ts-package':     () => import('./ts-package/module'),
  'ts-shipment':    () => import('./ts-shipment/module'),
  'ts-transfer':    () => import('./ts-transfer/module'),
  'ts-wip':         () => import('./ts-wip/module'),
  dashboard:        () => import('./dashboard/module'),
  admin:            () => import('./admin/module'),
  inquiry:          () => import('./inquiry/module'),
  shared:           () => import('./shared/module'),
  'ts-stock':       () => import('./ts-stock/module'),
  'ts-tsreport':    () => import('./ts-tsreport/module'),
  'ts-rmreport':    () => import('./ts-rmreport/module'),
  'trace-forward':  () => import('./trace-forward/module'),
  'trace-backward': () => import('./trace-backward/module'),
  'm-adjustment':   () => import('./m-adjustment/module'),
  'm-quantifier':   () => import('./m-quantifier/module'),
}

export const MODULE_NAMES = Object.keys(MODULES)
