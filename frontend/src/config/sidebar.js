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
        icon: 'fa-th-large'
      },
      {
        path: '/forward-trace',
        label: 'Forward Trace',
        icon: 'fa-angle-double-right'
      },
      {
        path: '/backward-trace',
        label: 'Backward Trace',
        icon: 'fa-angle-double-left'
      }
    ]
  },

  // TS Transaction
  {
    id: 'ts-transaction',
    label: 'TS Transaction',
    icon: 'fa-exchange-alt',
    children: [
      {
        path: '/ts-raw/rm-entry',
        label: 'RM Entry'
      },
      {
        path: '/ts-raw/wip-entry',
        label: 'WIP Entry'
      },
      {
        path: '/ts-raw/blending',
        label: 'Blending'
      },
      {
        path: '/ts-raw/package-entry',
        label: 'Package Entry'
      },
      {
        path: '/ts-raw/shipment-entry',
        label: 'Shipment Entry'
      },
      {
        path: '/ts-raw/transfer',
        label: 'Transfer'
      }
    ]
  },

  // TS Inquiry
  {
    id: 'ts-inquiry',
    label: 'TS Inquiry',
    icon: 'fa-search',
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
    icon: 'fa-cog',
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
    icon: 'fa-user-shield',
    children: [
      {
        path: '/admin/user-management',
        label: 'User Management'
      }
    ]
  }
]
