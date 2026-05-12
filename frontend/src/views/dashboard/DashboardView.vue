<template>
  <div>
    <!-- Section header (Stisla style) -->
    <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;">
      <div>
        <h1 style="font-size:18px;font-weight:700;color:var(--text-main);margin:0 0 4px;">Dashboard</h1>
        <div class="section-header-breadcrumb">
          <div class="breadcrumb-item">Home</div>
          <div class="breadcrumb-item" style="color:var(--primary);">Dashboard</div>
        </div>
      </div>
    </div>

    <!-- Welcome card (Stisla black banner style) -->
    <div class="card card-primary" style="background:linear-gradient(135deg,var(--primary) 0%,#4d5ec7 100%);border:none;margin-bottom:20px;">
      <div class="card-body" style="padding:24px 28px;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
          <div>
            <h4 style="color:#fff;font-size:20px;font-weight:800;margin:0 0 6px;">
              Selamat datang, {{ authStore.user?.name || 'User' }}! 👋
            </h4>
            <p style="color:rgba(255,255,255,.75);font-size:13px;margin:0;">
              Logged in as <strong>{{ firstRole }}</strong> &mdash; {{ today }}
            </p>
          </div>
          <div style="width:60px;height:60px;background:rgba(255,255,255,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-user" style="color:#fff;font-size:24px;"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats row -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:20px;">
      <div class="card" v-for="stat in stats" :key="stat.label">
        <div class="stat-card">
          <div class="stat-icon" :style="{ background: stat.bg }">
            <i :class="stat.icon"></i>
          </div>
          <div>
            <div class="stat-value">—</div>
            <div class="stat-label">{{ stat.label }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick navigation (Stisla card style) -->
    <div class="card">
      <div class="card-header">
        <h4><i class="fas fa-th-large" style="color:var(--primary);margin-right:8px;"></i>Quick Navigation</h4>
      </div>
      <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;">
        <RouterLink
          v-for="link in quickLinks"
          :key="link.to"
          :to="link.to"
          style="display:flex;flex-direction:column;align-items:center;padding:20px 16px;border:2px solid var(--border-color);border-radius:8px;text-decoration:none;transition:all .15s;cursor:pointer;"
          @mouseover="(e) => { e.currentTarget.style.borderColor='var(--primary)'; e.currentTarget.style.background='var(--primary-faded)'; }"
          @mouseout="(e) => { e.currentTarget.style.borderColor='var(--border-color)'; e.currentTarget.style.background=''; }"
        >
          <div style="width:48px;height:48px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:12px;" :style="{ background: link.bg }">
            <i :class="link.icon" style="font-size:20px;color:#fff;"></i>
          </div>
          <span style="font-size:13px;font-weight:700;color:var(--text-main);">{{ link.label }}</span>
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const firstRole = computed(() => authStore.roles?.[0] || 'User')
const today     = computed(() => new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

const stats = [
  { label: 'Material Setup',  bg: 'var(--primary)', icon: 'fab fa-asymmetrik' },
  { label: 'Storage Setup',   bg: 'var(--success)',  icon: 'fas fa-database' },
  { label: 'Supplier Setup',  bg: 'var(--warning)',  icon: 'fas fa-diagnoses' },
  { label: 'Total Pengguna',  bg: 'var(--info)',     icon: 'fas fa-users' },
]

const quickLinks = [
  { to: '/setup/material', label: 'Setup Material', bg: 'var(--primary)', icon: 'fab fa-asymmetrik' },
  { to: '/setup/storage',  label: 'Setup Storage',  bg: 'var(--success)',  icon: 'fas fa-database' },
  { to: '/setup/supplier', label: 'Setup Supplier', bg: 'var(--warning)',  icon: 'fas fa-diagnoses' },
]
</script>
