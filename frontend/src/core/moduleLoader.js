/**
 * Module Loader — auto-discovers and registers Vue frontend modules.
 *
 * Each module under `@/modules/<name>/` should export a `module.js`
 * manifest that may contain:
 *   - routes:   Array of route configs (merged into the app router)
 *   - stores:   Array of Pinia store definitions
 *   - api:      API service object (optional)
 *
 * Usage:
 *   import { registerModules } from '@/core/moduleLoader'
 *   const modules = registerModules(import.meta.glob(...))
 */

let _registeredRoutes = []
let _registeredStores = []

/**
 * Scans glob-matched module manifests, collects routes and stores.
 * @param {Record<string, () => Promise<{ default: ModuleDef }>>} moduleGlob
 * @returns {{ routes: object[], stores: object[] }}
 */
export async function registerModules(moduleGlob) {
  const routes = []
  const stores = []

  for (const [path, resolver] of Object.entries(moduleGlob)) {
    try {
      const { default: mod } = await resolver()
      if (!mod) continue

      if (Array.isArray(mod.routes)) {
        routes.push(...mod.routes)
      }
      if (Array.isArray(mod.stores)) {
        stores.push(...mod.stores)
      }
    } catch (err) {
      /* module load error bypassed */
    }
  }

  _registeredRoutes = routes
  _registeredStores = stores

  return { routes, stores }
}

export function getRegisteredRoutes() {
  return _registeredRoutes
}

export function getRegisteredStores() {
  return _registeredStores
}

/**
 * Synchronously register stores from eager-loaded module manifests.
 * Call from main.js before creating the Pinia instance.
 */
export function registerModuleStores(pinia) {
  const moduleGlob = import.meta.glob('@/modules/*/module.js', { eager: true })

  for (const [, mod] of Object.entries(moduleGlob)) {
    const manifest = mod.default || mod
    if (!Array.isArray(manifest?.stores)) continue

    for (const storeDef of manifest.stores) {
      // storeDef is a Pinia store setup function (result of defineStore)
      // It auto-registers via Pinia's global store map.
      // We call it here just to be explicit — Pinia already tracks it.
      if (typeof storeDef === 'function') {
        storeDef(pinia)
      }
    }
  }
}
