# Frontend Modules — Convention Reference

## Module structure
```
modules/<name>/
  views/        ← Vue page components (PascalCase.vue)
  components/   ← module-specific components
  services/     ← ALL axios calls here (never in view/store directly)
  stores/       ← Pinia store (defineStore, composition API)
  module.js     ← exports { name, routes, stores }
```

## Data flow (WAJIB)
```
View → Store → Service → Axios (@/api/axios.js)
```

## module.js format
```js
export default {
  name: 'module-name',
  routes: [...],
  stores: [...]
}
```
Auto-loaded via `import.meta.glob` in `core/router/index.js`.

## Anti-patterns
- API call directly in Vue component/template
- `window.location.href` in axios interceptor
- `fetchMe()` on public routes
- `document.documentElement.*` for Vuetify state

## Imports in Vitest tests
```js
import { ref, computed } from 'vue'  // explicit, no auto-import
import { setActivePinia, createPinia } from 'pinia'
```

## See also
`.claude/rules/frontend-vue.md` — full Vue rule set
