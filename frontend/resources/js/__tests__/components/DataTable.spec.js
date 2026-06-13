import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import DataTable from '@/modules/shared/components/DataTable.vue'

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
    expect(wrapper.text()).toContain('No data found')
  })

  it('hides search field when showSearch is false', () => {
    const wrapper = mount(DataTable, { props: { columns, data: rows, showSearch: false } })
    expect(wrapper.find('#dt-search').exists()).toBe(false)
  })

  it('hides top info text when showTopInfo is false', () => {
    const wrapper = mount(DataTable, { props: { columns, data: rows, showTopInfo: false } })
    expect(wrapper.find('.d-flex.align-center.justify-space-between.gap-4 span.text-caption').exists()).toBe(false)
  })

  it('hides bottom info text when showBottomInfo is false', () => {
    const wrapper = mount(DataTable, { props: { columns, data: rows, showBottomInfo: false } })
    expect(wrapper.find('.custom-pagination-footer span.text-caption').exists()).toBe(false)
  })
})
