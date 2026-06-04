import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import '@mdi/font/css/materialdesignicons.css'

const ecoTheme = {
  dark: false,
  colors: {
    primary: '#42B240',
    'primary-darken-1': '#2A8030',
    'primary-darken-2': '#1E5C20',
    'primary-lighten-1': '#65C463',
    'primary-lighten-2': '#E2F6E2',
    secondary: '#2A8030',
    accent: '#C8873A',
    error: '#C62828',
    warning: '#E65100',
    info: '#01579B',
    success: '#2E7D32',
    background: '#F7F4EF',
    surface: '#FFFFFF',
    'on-primary': '#FFFFFF',
    'on-secondary': '#FFFFFF',
    'on-background': '#1C2420',
    'on-surface': '#1C2420',
    'green-900': '#1C3220',
    'green-800': '#1E5C20',
    'green-700': '#2A8030',
    'green-500': '#42B240',
    'green-100': '#E2F6E2',
    'green-50': '#F3FBF3',
    'neutral-50': '#F7F4EF',
    'neutral-200': '#D4DDD8',
    'neutral-400': '#97A49C',
    'neutral-600': '#5A6860',
    'neutral-900': '#1C2420',
    'amber-500': '#C8873A',
  },
}

const ecoDarkTheme = {
  dark: true,
  colors: {
    primary:          '#65C463',  // lighter green legible on dark
    'primary-darken-1': '#42B240',
    secondary:        '#90D58E',
    accent:           '#E8B878',
    error:            '#EF9A9A',
    warning:          '#FFCC80',
    info:             '#81D4FA',
    success:          '#A5D6A7',
    background:       '#0F1410',
    surface:          '#1C2420',
    'on-primary':     '#0D3B22',
    'on-background':  '#E2F6E2',
    'on-surface':     '#D4DDD8',
  },
}

export default createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'eco',
    themes: {
      eco: ecoTheme,
      ecoDark: ecoDarkTheme,
    },
  },
  defaults: {
    VBtn: {
      rounded: 'md',
      elevation: 0,
    },
    VCard: {
      rounded: 'lg',
      elevation: 1,
    },
    VTextField: {
      rounded: 'md',
      variant: 'outlined',
      density: 'comfortable',
    },
    VSelect: {
      rounded: 'md',
      variant: 'outlined',
      density: 'comfortable',
    },
    VChip: {
      rounded: 'pill',
    },
  },
})