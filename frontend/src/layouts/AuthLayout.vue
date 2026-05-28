<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 font-sans">
    <!-- Background decoration -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute w-[800px] h-[800px] bg-green-50 rounded-full -top-96 -right-96 opacity-50"></div>
      <div class="absolute w-[600px] h-[600px] bg-green-50 rounded-full -bottom-48 -left-48 opacity-50"></div>
    </div>

    <div class="relative z-1 w-full max-w-md px-6 py-12">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 mb-4 transition-transform hover:scale-105 duration-300">
          <img src="/logo-stacked.jpg" alt="Logo" class="w-full h-full object-contain" />
        </div>
        <h1 class="text-slate-800 text-3xl font-extrabold tracking-tight">EUDR-TS</h1>
        <p class="text-gray-500 text-sm mt-1 font-medium">Enterprise Operations Data System</p>
      </div>

      <!-- Card -->
      <div class="bg-white rounded-2xl p-8 shadow-2xl shadow-slate-200/60 border border-white">
        <router-view />
      </div>

      <!-- Footer -->
      <div class="text-center mt-8">
        <p class="text-gray-400 text-xs font-semibold uppercase tracking-widest">&copy; 2026 EO Trace System</p>
      </div>
    </div>
  </div>
</template>
