<template>
  <div class="min-h-screen flex items-center justify-center" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);">
    <!-- Background decoration -->
    <div style="position:absolute;inset:0;overflow:hidden;pointer-events:none;">
      <div style="position:absolute;width:600px;height:600px;background:radial-gradient(circle,rgba(37,99,235,.15) 0%,transparent 70%);top:-100px;right:-100px;border-radius:50%;"></div>
      <div style="position:absolute;width:400px;height:400px;background:radial-gradient(circle,rgba(37,99,235,.1) 0%,transparent 70%);bottom:-50px;left:-50px;border-radius:50%;"></div>
    </div>

    <div style="position:relative;z-index:1;width:100%;max-width:440px;padding:1.5rem;">
      <!-- Logo -->
      <div style="text-align:center;margin-bottom:2rem;">
        <div style="display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;background:linear-gradient(135deg,#2563eb,#1d4ed8);border-radius:14px;margin-bottom:1rem;box-shadow:0 8px 24px rgba(37,99,235,.4);">
          <svg width="28" height="28" fill="none" viewBox="0 0 24 24"><path fill="#fff" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        </div>
        <h1 style="color:#f8fafc;font-size:1.5rem;font-weight:800;margin:0;">EODS</h1>
        <p style="color:#64748b;font-size:0.875rem;margin:.25rem 0 0;">Enterprise Operations Data System</p>
      </div>

      <!-- Card -->
      <div style="background:rgba(255,255,255,.04);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:2rem;box-shadow:0 24px 64px rgba(0,0,0,.4);">
        <router-view />
      </div>
    </div>
  </div>
</template>
