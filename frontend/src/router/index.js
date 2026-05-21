import { createAppRouter } from '@/core/router'

const moduleGlob = import.meta.glob('@/modules/*/module.js', { eager: true })

const moduleRoutes = []

for (const [path, mod] of Object.entries(moduleGlob)) {
  const manifest = mod.default || mod
  if (manifest?.routes) {
    moduleRoutes.push(...manifest.routes)
  }
}

const router = createAppRouter(moduleRoutes)

export default router
