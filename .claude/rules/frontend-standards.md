# rules/frontend-standards.md — Vue.js Frontend Standards

> Applies to both `client/` (public) and `admin/` (dashboard).
> Target: Vue 3 official style guide + Pinia official docs.
> Back to manifesto: [../CLAUDE.md](../CLAUDE.md)

---

## Golden Rule: Always `<script setup>`

**Do:**
```vue
<script setup>
import { ref, computed } from 'vue'
import { useEventStore } from '@/stores/events'

const props = defineProps({
  eventId: { type: Number, required: true },
})
const emit = defineEmits(['added-to-cart'])

const store = useEventStore()
const quantity = ref(1)
const event = computed(() => store.getById(props.eventId))

function addToCart() {
  emit('added-to-cart', { eventId: props.eventId, quantity: quantity.value })
}
</script>
```

**Never:**
```vue
<script>
export default {
  data() { return { quantity: 1 } }  // Options API — banned in new code
}
</script>
```

---

## Naming Conventions

| Item | Convention | Example |
|------|-----------|---------|
| Component files | PascalCase | `EventCard.vue`, `CheckoutForm.vue` |
| Page components | PascalCase + `View` suffix | `EventsView.vue`, `LoginView.vue` |
| Composables | `use` prefix, camelCase | `useEventFilters.js`, `useAuth.js` |
| Pinia stores | `use` prefix + noun | `useAuthStore`, `useCartStore` |
| Store files | camelCase | `auth.js`, `cart.js` |
| API modules | camelCase | `events.js`, `orders.js` |
| Props | camelCase in JS, kebab-case in template | `eventId` / `:event-id` |
| Emits | kebab-case | `'added-to-cart'`, `'modal-closed'` |

---

## Target Folder Structure

```
src/
├── api/
│   ├── axios.js           # Single Axios instance + interceptors
│   ├── auth.js
│   ├── events.js
│   ├── orders.js
│   ├── tickets.js
│   └── organizers.js      # admin only
├── components/
│   ├── base/              # Domain-agnostic reusable components
│   │   ├── BaseButton.vue
│   │   ├── BaseInput.vue
│   │   ├── BaseModal.vue
│   │   └── BaseTable.vue
│   ├── events/
│   │   ├── EventCard.vue
│   │   └── EventFilters.vue
│   ├── checkout/
│   │   └── ParticipantForm.vue
│   └── tickets/
│       └── TicketQrCard.vue
├── composables/
│   ├── useAuth.js
│   ├── useEventFilters.js
│   ├── usePagination.js
│   └── useToast.js
├── layouts/
│   ├── DefaultLayout.vue
│   └── DashboardLayout.vue
├── router/index.js
├── stores/
│   ├── auth.js
│   ├── cart.js
│   └── events.js
└── views/
    ├── HomeView.vue
    └── ...
```

---

## Pinia Store — Target Pattern (Setup Store)

```js
// stores/auth.js

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user    = ref(null)
  const token   = ref(localStorage.getItem('auth_token') ?? null)
  const isLoading = ref(false)
  const error   = ref(null)

  // Getters
  const isAuthenticated = computed(() => !!token.value)
  const isSuperAdmin    = computed(() =>
    user.value?.roles?.some(r => r.slug === 'super_admin') ?? false
  )

  // Actions
  async function login(email, password) {
    isLoading.value = true
    error.value = null
    try {
      const data = await authApi.login(email, password)
      token.value = data.token
      user.value  = data.user
      localStorage.setItem('auth_token', data.token)
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Login failed.'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    await authApi.logout()
    user.value  = null
    token.value = null
    localStorage.removeItem('auth_token')
  }

  async function fetchUser() {
    if (!token.value) return
    user.value = await authApi.me()
  }

  return { user, token, isLoading, error, isAuthenticated, isSuperAdmin, login, logout, fetchUser }
})
```

**Rules:**
- Setup Store syntax only (not Options Store)
- State = `ref()` · Getters = `computed()` · Actions = `async function`
- Never mutate state outside actions
- Persist token in `localStorage` only (not full user object)
- Always: reset `error` at action start, set `isLoading` around async calls

---

## API Module — Target Standard

All API calls go through `src/api/`. **Components and stores never import axios directly.**

```js
// api/axios.js — Single Axios instance

import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use(config => {
  const auth = useAuthStore()
  if (auth.token) config.headers.Authorization = `Bearer ${auth.token}`
  return config
})

api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      useAuthStore().logout()
      router.push('/entrar')
    }
    return Promise.reject(error)
  }
)

export default api
```

```js
// api/events.js — One file per domain

import api from './axios'

export const eventsApi = {
  list:   (params) => api.get('/events', { params }).then(r => r.data),
  show:   (slug)   => api.get(`/events/${slug}`).then(r => r.data),
  cities: ()       => api.get('/events/cities').then(r => r.data),
  states: ()       => api.get('/events/states').then(r => r.data),
}
```

**Rules:**
- One `api/*.js` file per domain
- Each function unwraps `.data` before returning
- Import as: `import { eventsApi } from '@/api/events'`

**Never:**
```js
import axios from 'axios'
const resp = await axios.get('/api/events')  // never — bypasses token injection
```

---

## Composables — Target Standard

Extract reusable stateful logic. A composable uses Vue reactivity.

```js
// composables/useEventFilters.js

import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { eventsApi } from '@/api/events'

export function useEventFilters() {
  const route  = useRoute()
  const router = useRouter()

  const filters   = ref({ search: route.query.search ?? '', city: '', state: '', page: 1 })
  const events    = ref([])
  const isLoading = ref(false)
  const meta      = ref(null)

  async function fetch() {
    isLoading.value = true
    try {
      const data  = await eventsApi.list(filters.value)
      events.value = data.data
      meta.value   = data.meta
    } finally {
      isLoading.value = false
    }
  }

  watch(filters, val => { router.replace({ query: val }); fetch() }, { deep: true })

  return { filters, events, isLoading, meta, fetch }
}
```

**Create a composable when:**
- Logic is reused in 2+ components
- A component exceeds ~150 lines due to logic
- Logic uses Vue reactivity (refs, watchers, lifecycle hooks)

**Don't create a composable for:**
- Simple one-off utility → plain function in `utils/`
- App-wide shared state → Pinia store

---

## Route Guards — Target Standard

```js
// router/index.js

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Re-hydrate auth on first navigation
  if (!auth.user && auth.token) await auth.fetchUser()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { path: '/entrar', query: { redirect: to.fullPath } }
  }

  if (to.meta.requiresGuest && auth.isAuthenticated) {
    return { path: '/' }
  }

  // admin app only:
  if (to.meta.requiresSuperAdmin && !auth.isSuperAdmin) {
    return { path: '/organizer/dashboard' }
  }
})
```

**Route meta flags:**
- `requiresAuth: true` — must be authenticated
- `requiresGuest: true` — redirect if already logged in
- `requiresSuperAdmin: true` — admin app only, super admin check

**Rules:**
- Use `return { path: ... }` for redirects (not `next()`)
- Auth hydration in `beforeEach` — always lazy-check before route logic

---

## Component Structure Rules

### Single Responsibility

Views are thin orchestrators. Extract to sub-components when a view exceeds ~150 lines.

```
EventsView.vue (thin orchestrator)
  ├── EventFilters.vue    ← filter form + state
  ├── EventList.vue       ← list rendering
  │   └── EventCard.vue   ← single card
  └── BasePagination.vue  ← reusable
```

### Props Validation (always declare)

```js
const props = defineProps({
  event:     { type: Object,  required: true },
  showPrice: { type: Boolean, default: true },
})
```

### Template Rules

- `v-for` always with `:key` — use entity IDs, never array index
- Never `v-if` + `v-for` on same element — use `<template v-if>` wrapper
- `v-show` for frequent toggles, `v-if` for conditional rendering

---

## Async Error Handling Pattern (Standard)

Use this pattern consistently in all components and composables:

```vue
<script setup>
const events    = ref([])
const isLoading = ref(false)
const error     = ref(null)

async function loadEvents() {
  isLoading.value = true
  error.value     = null
  try {
    events.value = await eventsApi.list()
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Failed to load events.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div v-if="isLoading">Loading...</div>
  <div v-else-if="error" class="text-red-600">{{ error }}</div>
  <div v-else>
    <EventCard v-for="event in events" :key="event.id" :event="event" />
  </div>
</template>
```

---

## Tailwind CSS Rules

- Follow Tailwind recommended class order: layout → box model → typography → visual
- Reuse via **components**, not `@apply` custom classes

**Do:**
```vue
<!-- BaseButton.vue — extract repeated class combinations -->
<button :class="['px-4 py-2 rounded-lg font-medium transition',
  variant === 'primary' ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-100 text-gray-700']">
  <slot />
</button>
```

**Don't:**
```css
.btn-primary { @apply bg-blue-600 text-white; }  /* Don't create utility classes */
```

---

## Admin App — Additional Rules

- `vue3-apexcharts` charts used **only in view-level components**, never shared components
- Charts receive data via props — never fetch their own data
- Role-based guards via `meta.requiresSuperAdmin` — see route guard pattern above
- `html5-qrcode` scanner initialized in `onMounted`, destroyed in `onUnmounted`

---

## Current State → Migration Path

| Area | Current | Migration |
|------|---------|-----------|
| Script blocks | Mix Options API / setup | Migrate to `<script setup>` on next touch — see [TD-008](../memory/tech-debt.md) |
| API calls | Some direct in components | Extract to `api/*.js` + composable — see [TD-004](../memory/tech-debt.md) |
| Pinia stores | Partially implemented | Expand with full setup-store pattern |
| Error handling | Inconsistent | Add `isLoading`/`error` pattern on next touch |
| Component size | Some large views | Extract sub-components when > 150 lines |
