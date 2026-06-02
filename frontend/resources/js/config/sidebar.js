/**
 * Sidebar Navigation Configuration
 * Centralized menu structure for decoupled sidebar component
 */

export const sidebarMenu = [
  // Main Dashboard
  {
    id: 'main',
    label: 'Main',
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
    icon: 'ri:exchange-funds-line',
    children: [
      {
        path: '/ts-raw/rm-entry',
        label: 'RM Entry'
      },
      {
        path: '/ts-wip/wip-entry',
        label: 'WIP Entry'
      },
      {
        path: '/ts-blending/blending',
        label: 'Blending'
      },
      {
        path: '/ts-package/package-entry',
        label: 'Package Entry'
      },
      {
        path: '/ts-shipment/shipment-entry',
        label: 'Shipment Entry'
      },
      {
        path: '/ts-transfer/transfer',
        label: 'Transfer'
      }
    ]
  },

  // TS Inquiry
  {
    id: 'ts-inquiry',
    label: 'TS Inquiry',
    icon: 'ri:search-line',
    children: [
      {
        path: '/inquiry/stock',
        label: 'Stock On-Hand'
      },
      {
        path: '/inquiry/ts-report',
        label: 'TS Report'
      },
      {
        path: '/inquiry/rm-report',
        label: 'RM Report'
      }
    ]
  },

  // TS Setup
  {
    id: 'ts-setup',
    label: 'TS Setup',
    icon: 'ri:settings-line',
    children: [
      {
        path: '/setup/material',
        label: 'Material'
      },
      {
        path: '/setup/storage',
        label: 'Storage'
      },
      {
        path: '/setup/tank',
        label: 'Tank'
      },
      {
        path: '/setup/supplier',
        label: 'Supplier'
      },
      {
        path: '/setup/manufacturer',
        label: 'Manufacturer'
      },
      {
        path: '/setup/adjustment',
        label: 'Adjustment'
      },
      {
        path: '/setup/quantifier',
        label: 'Quantifier'
      },
      {
        path: '/setup/plant',
        label: 'Plant'
      }
    ]
  },

  // Admin Setup
  {
    id: 'admin-setup',
    label: 'Admin Setup',
    icon: 'ri:shield-user-line',
    children: [
      {
        path: '/admin/user-management',
        label: 'User Management'
      }
    ]
  }
]