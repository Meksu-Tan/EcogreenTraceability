import './styles/design-tokens.css'
import './assets/main.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(vuetify)

app.mount('#app')

// ── Dev Panel (local only) ──────────────────────────────────
if (import.meta.env.VITE_APP_ENV === 'local') {
  import('./dev-panel/index.js').then(({ default: DevPanel }) => {
    DevPanel.mount()
  })
}