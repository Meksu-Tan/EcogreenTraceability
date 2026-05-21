import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import DataTable from '@/components/shared/DataTable.vue'

describe('DataTable', () => {
  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'name', label: 'Name' },
  ]

  const rows = [
    { id: 1, name: 'Item A' },
    { id: 2, name: 'Item B' },
  ]

  it('renders columns', () => {
    const wrapper = mount(DataTable, { props: { columns, data: rows } })
    columns.forEach(col => {
      expect(wrapper.text()).toContain(col.label)
    })
  })

  it('renders rows', () => {
    const wrapper = mount(DataTable, { props: { columns, data: rows } })
    expect(wrapper.text()).toContain('Item A')
    expect(wrapper.text()).toContain('Item B')
  })

  it('shows empty message when no data', () => {
    const wrapper = mount(DataTable, { props: { columns, data: [] } })
    expect(wrapper.text()).toContain('Tidak ada data')
  })
})
