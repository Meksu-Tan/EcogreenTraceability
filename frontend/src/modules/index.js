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
  auth:         () => import('./auth/module'),
  material:     () => import('./material/module'),
  storage:      () => import('./storage/module'),
  supplier:     () => import('./supplier/module'),
  manufacturer: () => import('./manufacturer/module'),
  tank:         () => import('./tank/module'),
  plant:        () => import('./plant/module'),
  transaction:  () => import('./transaction/module'),
  dashboard:    () => import('./dashboard/module'),
  admin:        () => import('./admin/module'),
  inquiry:      () => import('./inquiry/module'),
  shared:       () => import('./shared/module'),
}

export const MODULE_NAMES = Object.keys(MODULES)
