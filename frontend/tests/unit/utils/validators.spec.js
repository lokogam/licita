import { describe, expect, it } from 'vitest'
import {
  CURRENCIES,
  LIMITS,
  isValidDate,
  isValidTime,
  sanitizeBudget,
  validateDocument,
  validateTender,
} from '@/utils/validators'

const validTender = () => ({
  subject: 'Compra de portátiles',
  description: 'Adquisición de 50 equipos',
  currency: 'COP',
  budget: '1500000.50',
  activity_id: 120,
  start_date: '2026-11-01',
  start_time: '08:00',
  closing_date: '2026-11-15',
  closing_time: '17:30',
})

describe('validateTender', () => {
  it('returns no errors for a valid tender', () => {
    expect(validateTender(validTender())).toEqual({})
  })

  it('requires every field', () => {
    const errors = validateTender({})

    expect(Object.keys(errors).sort()).toEqual(
      [
        'activity_id',
        'budget',
        'closing_date',
        'closing_time',
        'currency',
        'description',
        'start_date',
        'start_time',
        'subject',
      ].sort(),
    )
    expect(errors.subject).toBe('El campo objeto es obligatorio.')
    expect(errors.activity_id).toBe('El campo actividad es obligatorio.')
  })

  it('rejects text made only of spaces', () => {
    expect(validateTender({ ...validTender(), subject: '   ' }).subject).toBe(
      'El campo objeto no puede contener solo espacios.',
    )
  })

  it('enforces character limits', () => {
    const errors = validateTender({
      ...validTender(),
      subject: 'a'.repeat(LIMITS.subject + 1),
      description: 'a'.repeat(LIMITS.description + 1),
    })

    expect(errors.subject).toBe('El campo objeto no debe superar 150 caracteres.')
    expect(errors.description).toBe('El campo descripción no debe superar 400 caracteres.')
  })

  it('only accepts the fixed currencies', () => {
    expect(CURRENCIES).toEqual(['COP', 'USD', 'EUR'])
    expect(validateTender({ ...validTender(), currency: 'MXN' }).currency).toBe(
      'El campo moneda debe ser uno de: COP, USD, EUR.',
    )
  })

  it.each([
    ['0', 'El campo presupuesto debe ser mayor a 0.'],
    ['0.00', 'El campo presupuesto debe ser mayor a 0.'],
    ['10.123', 'El campo presupuesto debe ser un número con máximo 2 decimales (solo números y punto).'],
    ['1,5', 'El campo presupuesto debe ser un número con máximo 2 decimales (solo números y punto).'],
    ['abc', 'El campo presupuesto debe ser un número con máximo 2 decimales (solo números y punto).'],
    ['99999999999999', 'El campo presupuesto no debe ser mayor a 9999999999999.99.'],
  ])('rejects budget %s', (budget, message) => {
    expect(validateTender({ ...validTender(), budget }).budget).toBe(message)
  })

  it('validates date and time formats', () => {
    const errors = validateTender({ ...validTender(), start_date: '2026-02-30', start_time: '25:00' })

    expect(errors.start_date).toBe('El campo fecha de inicio debe ser una fecha válida con formato AAAA-MM-DD.')
    expect(errors.start_time).toBe('El campo hora de inicio debe ser una hora válida con formato HH:mm (24 horas).')
  })

  it('does not allow closing date before start date', () => {
    expect(validateTender({ ...validTender(), closing_date: '2026-10-31' }).closing_date).toBe(
      'La fecha de cierre no puede ser menor a la fecha de inicio.',
    )
  })

  it('requires a later closing time when both dates are the same', () => {
    const sameDay = { ...validTender(), closing_date: '2026-11-01' }

    expect(validateTender({ ...sameDay, closing_time: '08:00' }).closing_time).toBe(
      'La hora de cierre debe ser mayor a la hora de inicio cuando ambas fechas son iguales.',
    )
    expect(validateTender({ ...sameDay, closing_time: '08:01' })).toEqual({})
  })
})

describe('sanitizeBudget', () => {
  it.each([
    ['1500', '1500'],
    ['15a0b0', '1500'],
    ['1,500.75', '1500.75'],
    ['1.2.3', '1.23'],
    ['10.999', '10.99'],
    ['-5', '5'],
    ['.5', '0.5'],
    ['', ''],
  ])('turns %s into %s', (raw, expected) => {
    expect(sanitizeBudget(raw)).toBe(expected)
  })
})

describe('date and time helpers', () => {
  it('checks real calendar dates', () => {
    expect(isValidDate('2028-02-29')).toBe(true)
    expect(isValidDate('2026-02-29')).toBe(false)
    expect(isValidDate('2026-13-01')).toBe(false)
    expect(isValidDate('01/02/2026')).toBe(false)
  })

  it('checks 24h times', () => {
    expect(isValidTime('00:00')).toBe(true)
    expect(isValidTime('23:59')).toBe(true)
    expect(isValidTime('24:00')).toBe(false)
    expect(isValidTime('7:00')).toBe(false)
  })
})

describe('validateDocument', () => {
  const file = (name, type, size = 1024) => ({ name, type, size })

  it('accepts a complete PDF or ZIP document', () => {
    expect(validateDocument({ title: 'Pliego', description: 'Principal', file: file('a.pdf', 'application/pdf') })).toEqual({})
    expect(validateDocument({ title: 'Anexos', description: 'Zip', file: file('a.zip', 'application/x-zip-compressed') })).toEqual({})
  })

  it('requires title, description and file within limits', () => {
    const errors = validateDocument({ title: ' ', description: 'a'.repeat(201), file: null })

    expect(errors.title).toBe('El campo título no puede contener solo espacios.')
    expect(errors.description).toBe('El campo descripción no debe superar 200 caracteres.')
    expect(errors.file).toBe('Debe seleccionar un archivo.')
  })

  it('rejects other file types and oversized files', () => {
    expect(validateDocument({ title: 'x', description: 'x', file: file('a.exe', 'application/pdf') }).file).toBe(
      'Solo se permiten archivos PDF o ZIP.',
    )
    expect(validateDocument({ title: 'x', description: 'x', file: file('a.pdf', 'image/png') }).file).toBe(
      'Solo se permiten archivos PDF o ZIP.',
    )
    expect(
      validateDocument({ title: 'x', description: 'x', file: file('a.pdf', 'application/pdf', 11 * 1024 * 1024) }).file,
    ).toBe('El archivo no debe superar 10 MB.')
  })
})
