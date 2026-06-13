/**
 * Dev Panel — entry point
 *
 * Only loaded in local development (VITE_APP_ENV=local).
 * Mounts DevPanel as an isolated Vue app so it never interferes with the main app.
 */
import { createApp } from 'vue'
import DevPanel from './DevPanel.vue'

export default {
  mount() {
    // Create a container div outside #app so it doesn't conflict
    const container = document.createElement('div')
    container.id = 'dev-panel-root'
    document.body.appendChild(container)

    // We need the same router instance so navigation hooks work
    import(/* @vite-ignore */ '@/router').then(({ default: router }) => {
      const panelApp = createApp(DevPanel)
      panelApp.use(router)
      panelApp.mount('#dev-panel-root')
    })
  },
}
