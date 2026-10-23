import { describe, expect, it } from 'vitest'
import { buildDocumentFormData, buildUpdateFormData, filenameFromDisposition } from '@/api/tenders'
import { toApiError } from '@/api/http'

describe('tenders api helpers', () => {
  it('builds the multipart payload for an update with documents', () => {
    const file = new File(['%PDF-1.4'], 'pliego.pdf', { type: 'application/pdf' })
    const data = buildUpdateFormData({ subject: 'Compra', budget: '10.50', activity_id: 7 }, [
      { title: 'Pliego', description: 'Principal', file },
    ])

    expect(data.get('_method')).toBe('PUT')
    expect(data.get('subject')).toBe('Compra')
    expect(data.get('activity_id')).toBe('7')
    expect(data.get('documents[0][title]')).toBe('Pliego')
    expect(data.get('documents[0][description]')).toBe('Principal')
    expect(data.get('documents[0][file]')).toBeInstanceOf(File)
  })

  it('builds the multipart payload to edit a document, omitting an unchanged file', () => {
    const withoutFile = buildDocumentFormData({ title: 'Pliego', description: 'Final', file: null })
    expect(withoutFile.get('_method')).toBe('PATCH')
    expect(withoutFile.get('title')).toBe('Pliego')
    expect(withoutFile.has('file')).toBe(false)

    const file = new File(['%PDF'], 'nuevo.pdf', { type: 'application/pdf' })
    expect(buildDocumentFormData({ title: 'Pliego', description: 'Final', file }).get('file')).toBeInstanceOf(File)
  })

  it('reads the file name from Content-Disposition', () => {
    expect(filenameFromDisposition('attachment; filename="licitaciones-20261022-101500.xlsx"')).toBe(
      'licitaciones-20261022-101500.xlsx',
    )
    expect(filenameFromDisposition('attachment; filename="a.pdf"; filename*=UTF-8\'\'Pliego%20%C3%BAnico.pdf')).toBe(
      'Pliego único.pdf',
    )
    expect(filenameFromDisposition(undefined, 'fallback.xlsx')).toBe('fallback.xlsx')
  })
})

describe('toApiError', () => {
  it('keeps the api message and field errors', () => {
    const error = toApiError({
      response: {
        status: 422,
        data: { message: 'Los datos enviados no son válidos.', errors: { subject: ['Obligatorio'] } },
      },
    })

    expect(error).toEqual({
      status: 422,
      message: 'Los datos enviados no son válidos.',
      errors: { subject: 'Obligatorio' },
    })
  })

  it('maps network failures to a friendly message', () => {
    expect(toApiError({ request: {} })).toEqual({
      status: 0,
      message: 'No fue posible conectar con el servidor. Verifique su conexión.',
      errors: {},
    })
  })
})
