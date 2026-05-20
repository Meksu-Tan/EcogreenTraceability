/**
 * Sidebar Navigation Configuration
 * Centralized menu structure for decoupled sidebar component
 */

export interface MenuItem {
  path: string
  label: string
  icon?: string
}

export interface MenuGroup {
  id: string
  label: string
  icon?: string
  items?: MenuItem[]
  children?: MenuItem[]
}

export const sidebarMenu: MenuGroup[] = [
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
        path: '/transaction/rm-entry',
        label: 'RM Entry'
      },
      {
        path: '/transaction/wip-entry',
        label: 'WIP Entry'
      },
      {
        path: '/transaction/blending',
        label: 'Blending'
      },
      {
        path: '/transaction/package-entry',
        label: 'Package Entry'
      },
      {
        path: '/transaction/shipment-entry',
        label: 'Shipment Entry'
      },
      {
        path: '/transaction/transfer',
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
