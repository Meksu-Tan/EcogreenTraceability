<template>
  <div>
    <div class="mb-6">
      <h2 class="text-h6 font-weight-bold">Welcome Back</h2>
      <p class="text-body-2 text-medium-emphasis mt-1">Please enter your details to sign in.</p>
    </div>

    <VForm @submit.prevent="handleLogin">
      <VTextField
        v-model="form.email"
        label="Email Address"
        type="email"
        prepend-inner-icon="ri-mail-line"
        autocomplete="email"
        autofocus
        :error-messages="errors.email"
        class="mb-4"
      />

      <VTextField
        v-model="form.password"
        label="Password"
        :type="showPassword ? 'text' : 'password'"
        prepend-inner-icon="ri-lock-line"
        :append-inner-icon="showPassword ? 'ri-eye-off-line' : 'ri-eye-line'"
        autocomplete="current-password"
        :error-messages="errors.password"
        class="mb-4"
        @click:append-inner="showPassword = !showPassword"
      />

      <VAlert
        v-if="loginError"
        type="error"
        variant="tonal"
        density="compact"
        :prepend-icon="'ri-error-warning-line'"
        class="mb-4"
      >
        {{ loginError }}
      </VAlert>

      <VBtn
        id="btn-login"
        type="submit"
        color="primary"
        block
        size="large"
        :loading="authStore.loading"
        :prepend-icon="'ri-login-box-line'"
      >
        Sign In
      </VBtn>
    </VForm>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore    = useAuthStore()
const router       = useRouter()
const loginError   = ref('')
const showPassword = ref(false)
const form         = reactive({ email: '', password: '' })
const errors       = reactive({ email: '', password: '' })

function validate() {
  errors.email = errors.password = ''
  let ok = true
  if (!form.email)    { errors.email    = 'Email is required.'; ok = false }
  if (!form.password) { errors.password = 'Password is required.'; ok = false }
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