---
paths: frontend/**
---

# Frontend Vue Rules

## Component rules
- Composition API + `<script setup>` ONLY — no Options API, no class components
- ALWAYS handle loading, error, empty states in UI components
- No `console.log()` in committed code

## Routing & Auth
- `useRouter` / `useRoute`: WAJIB explicit import dari `vue-router` — no auto-import
- Axios 401: clear `localStorage('auth_token')` → `return Promise.reject(error)` — NO `window.location.href`
- NEVER call `fetchMe()` on public/guest routes

## State & Data flow
```
View → Store → Service → Axios
```
- NEVER call API directly from Vue component/template — wajib via service layer
- Axios instance: `@/api/axios.js` — uses `VITE_API_URL` (no fallback string)
- `VITE_API_URL`: `import.meta.env.VITE_API_URL` — NO `|| 'http://...'` fallback

## Vuetify state
- Theme/layout state WAJIB via `useTheme()` / `useLayout()` composables
- NEVER `document.documentElement.*` or any DOM API for Vuetify state
- Breadcrumbs: use `#item` slot — NOT `to` prop (causes full-page reload)

## Imports in tests (Vitest)
- Pinia stores: explicit `import { ref, computed } from 'vue'` — no auto-import in Vitest
- `setActivePinia(createPinia())` before each store test

## Path aliases
- `@` → `resources/js/`
- `@core` → `resources/js/@core/`
- `@images` → `resources/images/`
- Source root: `frontend/resources/js/` NOT `src/`

## Module structure
Each module exports `module.js` with `{ name, routes, stores }` — auto-loaded via `import.meta.glob`

## Assets
- Logo assets FROM `.claude/.design/assets/` → copy ke `frontend/public/`
- Access via `/logo-*` path — NEVER import via JS bundler
- `logo-stacked.jpg` → sidebar/login, `logo-horizontal.jpg` → app bar, `logo-symbol.jpg` → favicon

## ESLint enforced
- No semicolons (`semi: never`)
- 2-space indent
- Trailing commas on multiline
- Vuetify logical classes: `ps-`, `pe-`, `ms-`, `me-` — NOT `pl-`, `pr-`
- Icons: `ri-*` (Remix Icon) — NOT MDI
