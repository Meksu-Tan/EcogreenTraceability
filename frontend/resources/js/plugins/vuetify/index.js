import { h } from 'vue'
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

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

const iconify = {
  component: props => h(props.tag || 'i', { class: [props.icon] }),
}

const aliases = {
  info:      'ri-information-line',
  success:   'ri-checkbox-circle-line',
  warning:   'ri-alert-line',
  error:     'ri-error-warning-line',
  close:     'ri-close-line',
  clear:     'ri-close-line',
  cancel:    'ri-close-line',
  delete:    'ri-close-circle-fill',
  complete:  'ri-check-line',
  prev:      'ri-arrow-left-s-line',
  next:      'ri-arrow-right-s-line',
  expand:    'ri-arrow-down-s-line',
  collapse:  'ri-arrow-up-s-line',
  sort:      'ri-arrow-up-line',
  sortAsc:   'ri-arrow-up-line',
  sortDesc:  'ri-arrow-down-line',
  dropdown:  'ri-arrow-down-s-line',
  menu:      'ri-menu-line',
  subgroup:  'ri-arrow-down-s-fill',
  edit:      'ri-pencil-line',
  first:     'ri-skip-back-mini-line',
  last:      'ri-skip-forward-mini-line',
  plus:      'ri-add-line',
  minus:     'ri-subtract-line',
  calendar:  'ri-calendar-2-line',
  delimiter: 'ri-circle-line',
  unfold:    'ri-split-cells-vertical',
  file:      'ri-attachment-2',
  loading:   'ri-refresh-line',
  ratingEmpty: 'ri-star-line',
  ratingFull:  'ri-star-fill',
  ratingHalf:  'ri-star-half-line',
}

export default createVuetify({
  components,
  directives,
  icons: {
    defaultSet: 'iconify',
    aliases,
    sets: { iconify },
  },
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
      color: 'primary',
      rounded: 'md',
      variant: 'outlined',
      density: 'comfortable',
    },
    VSelect: {
      color: 'primary',
      rounded: 'md',
      variant: 'outlined',
      density: 'comfortable',
    },
    VChip: {
      rounded: 'pill',
    },
  },
})