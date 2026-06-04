import { describe, it, expect } from 'vitest'
import { parseSoNo } from '@/modules/trace-backward/utils/parseSoNo'

describe('parseSoNo', () => {
  it('returns No Doc soItem when no dash in soNo', () => {
    const result = parseSoNo('100001', 'B-001')
    expect(result).toEqual({ soNo: '100001', soItem: 'No Doc', batchNo: 'B-001' })
  })

  it('pads soItem to 5 digits and appends 0 when not ending with 0', () => {
    const result = parseSoNo('100001-1', 'B-001')
    expect(result.soNo).toBe('100001')
    expect(result.soItem).toBe('000010')
    expect(result.batchNo).toBe('B-001')
  })

  it('pads soItem to 5 digits when ending with 0 (no extra suffix)', () => {
    const result = parseSoNo('100001-10', 'B-001')
    expect(result.soNo).toBe('100001')
    expect(result.soItem).toBe('00010')
  })

  it('handles empty soNo gracefully', () => {
    const result = parseSoNo('', 'B-001')
    expect(result).toEqual({ soNo: '', soItem: 'No Doc', batchNo: 'B-001' })
  })

  it('handles missing batchNo', () => {
    const result = parseSoNo('100001-10', '')
    expect(result.batchNo).toBe('')
  })
})
