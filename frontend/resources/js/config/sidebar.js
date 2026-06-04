/**
 * Sidebar Navigation Configuration
 * Centralized menu structure for decoupled sidebar component
 */

export const sidebarMenu = [
  // Main Dashboard
  {
    id: 'main',
    label: 'Dashboard',
    items: [
      {
        path: '/dashboard',
        label: 'Dashboard',
        icon: 'ri:dashboard-line'
      },
      {
        path: '/forward-trace',
        label: 'Forward Trace',
        icon: 'ri:arrow-right-double-line'
      },
      {
        path: '/backward-trace',
        label: 'Backward Trace',
        icon: 'ri:arrow-left-double-line'
      }
    ]
  },

  // TS Transaction
  {
    id: 'ts-transaction',
    label: 'TS Transaction',
    items: [
      {
        path: '/ts-raw/rm-entry',
        label: 'Raw Material',
        icon: 'ri:brush-line'
      },
      {
        path: '/ts-wip/wip-entry',
        label: 'WIP',
        icon: 'ri:fire-line'
      },
      {
        path: '/ts-blending/blending',
        label: 'Blending',
        icon: 'ri:git-merge-line'
      },
      {
        path: '/ts-transfer/transfer',
        label: 'Transfer',
        icon: 'ri:node-tree'
      },
      {
        path: '/ts-package/package-entry',
        label: 'Packaging',
        icon: 'ri:box-3-line'
      },
      {
        path: '/ts-shipment/shipment-entry',
        label: 'Shipment',
        icon: 'ri:ship-2-line'
      }
    ]
  },

  // TS Inquiry
  {
    id: 'ts-inquiry',
    label: 'TS Inquiry',
    items: [
      {
        path: '/ts-tsreport/ts-report',
        label: 'TS Report',
        icon: 'ri:file-list-3-line'
      },
      {
        path: '/ts-stock/stock',
        label: 'Stock On-Hand',
        icon: 'ri:database-2-line'
      },
      {
        path: '/ts-rmreport/rm-report',
        label: 'RM to PRD',
        icon: 'ri:calculator-line'
      }
    ]
  },

  // TS Setup
  {
    id: 'ts-setup',
    label: 'TS Setup',
    items: [
      {
        path: '/adjustment',
        label: 'Adjustment',
        icon: 'ri:equalizer-line'
      },
      {
        path: '/setup/material',
        label: 'Material',
        icon: 'ri:mind-map'
      },
      {
        path: '/setup/supplier',
        label: 'Supplier',
        icon: 'ri:user-shared-line'
      },
      {
        path: '/setup/storage',
        label: 'Storage',
        icon: 'ri:database-line'
      },
      {
        path: '/qtfsetup',
        label: 'Quantifier',
        icon: 'ri:scales-3-line'
      },
      {
        path: '/setup/plant',
        label: 'Plant',
        icon: 'ri:building-4-line'
      }
    ]
  },

  // Admin Setup
  {
    id: 'admin-setup',
    label: 'Admin Setup',
    items: [
      {
        path: '/admin/user-management',
        label: 'User Management',
        icon: 'ri:user-settings-line'
      }
    ]
  }
]