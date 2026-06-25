# pages/charts — ApexCharts Patterns
READ-ONLY reference.

## Theme-Aware Colors
```javascript
// WAJIB — jangan hardcode
const chartColors = computed(() => ({
  primary: vuetifyTheme.current.value.colors.primary,    // #42B240
  secondary: vuetifyTheme.current.value.colors.secondary,
}))
```

## Chart Types
- `RadarChart.vue` — competency radar
- `BarChart.vue` — comparison bar
- `DonutChart.vue` — composition donut

Package: `vue3-apexcharts` — JANGAN Chart.js
