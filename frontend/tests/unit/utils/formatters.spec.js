import { describe, expect, it } from 'vitest'
import { formatBytes, formatDate, formatDateTime, formatMoney } from '@/utils/formatters'

describe('formatters', () => {
  it('formats ISO dates as DD/MM/YYYY', () => {
    expect(formatDate('2026-11-01')).toBe('01/11/2026')
    expect(formatDate('')).toBe('—')
    expect(formatDate(null)).toBe('—')
  })

  it('formats a date with its time', () => {
    expect(formatDateTime('2026-11-01', '08:00')).toBe('01/11/2026 08:00')
    expect(formatDateTime('2026-10-22 10:15:00')).toBe('22/10/2026 10:15')
  })

  it('formats money with the tender currency and thousands separators', () => {
    expect(formatMoney('1500000.5', 'COP')).toContain('1.500.000,50')
    expect(formatMoney('1500000.5', 'USD')).toContain('US$')
    expect(formatMoney('10', 'EUR')).toContain('€')
  })

  it('formats file sizes', () => {
    expect(formatBytes(900)).toBe('900 B')
    expect(formatBytes(524288)).toBe('512 KB')
    expect(formatBytes(10 * 1024 * 1024)).toBe('10 MB')
    expect(formatBytes(1572864)).toBe('1.5 MB')
  })
})
