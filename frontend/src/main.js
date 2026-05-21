import './assets/main.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

import { registerModuleStores } from '@/core/moduleLoader'

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)

// Register module stores
registerModuleStores(pinia)

app.use(router)
app.mount('#app')