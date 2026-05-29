<template>
  <div>
    <div class="mb-6">
      <h4 class="text-xl font-bold text-slate-800">Welcome Back</h4>
      <p class="text-gray-500 text-sm mt-1">Please enter your details to sign in.</p>
    </div>

    <form @submit.prevent="handleLogin" class="space-y-5">
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide" for="email">Email Address</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="fas fa-envelope text-gray-400 text-xs"></i>
          </div>
          <input
            id="email"
            v-model="form.email"
            type="email"
            class="block w-full pl-10 pr-3 py-2.5 border rounded-lg text-sm transition-all focus:ring-2 focus:ring-green-500/20 focus:border-green-500 outline-none"
            :class="errors.email ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-white'"
            placeholder="name@company.com"
            autocomplete="email"
            autofocus
          />
        </div>
        <span v-if="errors.email" class="text-[10px] font-bold text-red-500 uppercase tracking-tight mt-1">{{ errors.email }}</span>
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide" for="password">Password</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="fas fa-lock text-gray-400 text-xs"></i>
          </div>
          <input
            id="password"
            v-model="form.password"
            type="password"
            class="block w-full pl-10 pr-3 py-2.5 border rounded-lg text-sm transition-all focus:ring-2 focus:ring-green-500/20 focus:border-green-500 outline-none"
            :class="errors.password ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-white'"
            placeholder="••••••••"
            autocomplete="current-password"
          />
        </div>
        <span v-if="errors.password" class="text-[10px] font-bold text-red-500 uppercase tracking-tight mt-1">{{ errors.password }}</span>
      </div>

      <!-- Error alert -->
      <div v-if="loginError" class="bg-red-50 border border-red-100 rounded-lg p-3 flex items-center gap-3 text-red-600 text-xs font-medium">
        <i class="fas fa-exclamation-circle text-sm"></i>
        <span>{{ loginError }}</span>
      </div>

      <button
        id="btn-login"
        type="submit"
        class="w-full bg-green-600 hover:bg-green-700 text-white py-2.5 rounded-lg font-bold text-sm shadow-lg shadow-green-200 transition-all active:scale-[0.98] flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
        :disabled="authStore.loading"
      >
        <i v-if="authStore.loading" class="fas fa-circle-notch animate-spin"></i>
        <i v-else class="fas fa-sign-in-alt"></i>
        <span>{{ authStore.loading ? 'Authenticating...' : 'Sign In' }}</span>
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore  = useAuthStore()
const router     = useRouter()
const loginError = ref('')
const form       = reactive({ email: '', password: '' })
const errors     = reactive({ email: '', password: '' })

function validate() {
  errors.email = errors.password = ''
  let ok = true
  if (!form.email)    { errors.email    = 'Email wajib diisi.'; ok = false }
  if (!form.password) { errors.password = 'Password wajib diisi.'; ok = false }
  return ok
}

async function handleLogin() {
  loginError.value = ''
  if (!validate()) return
  const result = await authStore.login({ email: form.email, password: form.password })
  if (result.success) {
    router.push({ name: 'dashboard' })
  } else {
    loginError.value = result.message
  }
}
</script>