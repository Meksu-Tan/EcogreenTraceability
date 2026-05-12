<template>
  <div class="login-page">
    <div class="login-box">
      <!-- Logo -->
      <div class="login-logo">
        <div style="display:inline-flex;align-items:center;gap:12px;margin-bottom:8px;">
          <div style="width:48px;height:48px;background:var(--primary);border-radius:12px;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-layer-group" style="color:#fff;font-size:20px;"></i>
          </div>
          <div style="text-align:left;">
            <div class="login-logo-title" style="margin:0;">EUDR-TS</div>
            <div class="login-logo-sub">PTEO Enterprise Operations</div>
          </div>
        </div>
      </div>

      <!-- Card -->
      <div class="login-card">
        <h4>Login</h4>
        <p>Masuk ke akun Anda untuk melanjutkan.</p>

        <form @submit.prevent="handleLogin">
          <div class="form-group">
            <label class="control-label" for="email">Email</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              class="form-control"
              :class="{ 'is-invalid': errors.email }"
              placeholder="Masukkan email"
              autocomplete="email"
              autofocus
            />
            <span v-if="errors.email" class="invalid-feedback">{{ errors.email }}</span>
          </div>

          <div class="form-group">
            <label class="control-label" for="password">Password</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              class="form-control"
              :class="{ 'is-invalid': errors.password }"
              placeholder="Masukkan password"
              autocomplete="current-password"
            />
            <span v-if="errors.password" class="invalid-feedback">{{ errors.password }}</span>
          </div>

          <!-- Error alert -->
          <div v-if="loginError" style="background:#fdf0ef;border:1px solid #f5c6c4;border-radius:6px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:var(--danger);">
            <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>{{ loginError }}
          </div>

          <div class="form-group" style="margin-bottom:0;">
            <button
              id="btn-login"
              type="submit"
              class="btn btn-primary btn-block btn-lg"
              :disabled="authStore.loading"
            >
              <i v-if="authStore.loading" class="fas fa-circle-notch spinner"></i>
              <i v-else class="fas fa-sign-in-alt"></i>
              {{ authStore.loading ? 'Memproses...' : 'Login' }}
            </button>
          </div>
        </form>
      </div>

      <div class="login-footer">
        Copyright &copy; {{ new Date().getFullYear() }} PT. Ecogreen Oleochemicals
      </div>
    </div>
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
