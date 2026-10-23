import { expect, test } from './fixtures'
import { TenderFormPage } from './pages/TenderFormPage'

const PDF = {
  name: 'Pliego de condiciones.pdf',
  mimeType: 'application/pdf',
  buffer: Buffer.from('%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n'),
}

// Minimal valid ZIP archive (empty end-of-central-directory record).
const ZIP = Buffer.from([0x50, 0x4b, 0x05, 0x06, ...new Array(18).fill(0)])

const uniqueSubject = () => `Compra de portátiles E2E ${Date.now()}`

const tenderData = (subject) => ({
  subject,
  description: 'Adquisición de 50 equipos portátiles con garantía extendida.',
  currency: 'COP',
  budget: '150000000.50',
  activitySearch: 'Computadores',
  startDate: '2026-11-01',
  startTime: '08:00',
  closingDate: '2026-11-15',
  closingTime: '17:30',
})

test.describe('Módulo de licitaciones', () => {
  test('crea, edita con documento, lista, filtra, consulta y exporta una licitación', async ({ page }) => {
    const subject = uniqueSubject()
    const form = new TenderFormPage(page)

    // Create
    await form.gotoCreate()
    await expect(form.submitButton('Crear licitación')).toBeDisabled()
    await form.fill(tenderData(subject))
    await expect(form.submitButton('Crear licitación')).toBeEnabled()
    await form.submitButton('Crear licitación').click()

    await expect(page).toHaveURL(/\/licitaciones\/\d+$/)
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(subject)
    const consecutive = (await page.locator('.consecutive-chip').textContent()).trim()
    expect(consecutive).toMatch(/^O-\d{4,}-\d{2}$/)

    // Edit: requires at least one document
    await page.getByRole('link', { name: 'Editar' }).click()
    await expect(page.getByText('Debe registrar al menos 1 documento.')).toBeVisible()
    await expect(form.submitButton('Guardar cambios')).toBeDisabled()

    await form.addDocument({ title: 'Pliego', description: 'Pliego de condiciones técnicas', file: PDF })
    await expect(page.getByText('Pendiente', { exact: true })).toBeVisible()
    await form.submitButton('Guardar cambios').click()

    // Detail tabs
    await expect(page).toHaveURL(/\/licitaciones\/\d+$/)
    await page.getByRole('tab', { name: /Documentos/ }).click()
    const download = page.getByRole('link', { name: /Pliego de condiciones\.pdf/ })
    await expect(download).toBeVisible()
    await page.getByRole('tab', { name: 'Cronograma' }).click()
    await expect(page.getByText('01/11/2026 08:00')).toBeVisible()

    // List + filter
    await page.getByRole('link', { name: 'Volver al listado' }).click()
    await page.getByLabel('Objeto').fill(subject)
    await expect(page.getByRole('row')).toHaveCount(2)
    await expect(page.getByRole('cell', { name: consecutive, exact: true })).toBeVisible()

    // Export to Excel (server generated)
    const downloadPromise = page.waitForEvent('download')
    await page.getByRole('button', { name: 'Exportar Excel' }).click()
    const file = await downloadPromise
    expect(file.suggestedFilename()).toMatch(/^licitaciones-\d{8}-\d{6}\.xlsx$/)
  })

  test('valida los campos y el cronograma antes de permitir guardar', async ({ page }) => {
    const form = new TenderFormPage(page)
    await form.gotoCreate()

    await form.subject.fill('   ')
    await form.subject.blur()
    await expect(page.getByText('El campo objeto no puede contener solo espacios.')).toBeVisible()

    await form.budget.pressSequentially('12a3,4.567')
    await expect(form.budget).toHaveValue('1234.56')

    await form.fill({ ...tenderData(uniqueSubject()), closingDate: '2026-11-01', closingTime: '07:00' })
    await expect(
      page.getByText('La hora de cierre debe ser mayor a la hora de inicio cuando ambas fechas son iguales.'),
    ).toBeVisible()
    await expect(form.submitButton('Crear licitación')).toBeDisabled()
  })

  test('rechaza documentos que no son PDF ni ZIP', async ({ page }) => {
    const form = new TenderFormPage(page)
    await form.gotoCreate()
    await form.fill(tenderData(uniqueSubject()))
    await form.submitButton('Crear licitación').click()
    await page.getByRole('link', { name: 'Editar' }).click()

    await page.getByRole('button', { name: 'Agregar documento' }).click()
    const dialog = page.getByRole('dialog', { name: 'Agregar documento' })
    await dialog
      .getByLabel('Archivo')
      .setInputFiles({ name: 'foto.png', mimeType: 'image/png', buffer: Buffer.from('png') })

    await expect(dialog.getByText('Solo se permiten archivos PDF o ZIP.')).toBeVisible()
    await expect(dialog.getByRole('button', { name: 'Agregar', exact: true })).toBeDisabled()
  })

  test('edita y elimina documentos guardados conservando al menos uno', async ({ page }) => {
    const form = new TenderFormPage(page)
    await form.gotoCreate()
    await form.fill(tenderData(uniqueSubject()))
    await form.submitButton('Crear licitación').click()
    await page.getByRole('link', { name: 'Editar' }).click()

    await form.addDocument({ title: 'Pliego', description: 'Pliego inicial', file: PDF })
    await form.addDocument({
      title: 'Anexos',
      description: 'Anexos técnicos',
      file: { name: 'anexos.zip', mimeType: 'application/zip', buffer: ZIP },
    })
    await form.submitButton('Guardar cambios').click()
    await expect(page).toHaveURL(/\/licitaciones\/\d+$/)
    await page.getByRole('link', { name: 'Editar' }).click()

    await page.getByRole('button', { name: 'Editar Pliego de condiciones.pdf' }).click()
    const dialog = page.getByRole('dialog', { name: 'Editar documento' })
    await dialog.getByLabel('Título').fill('Pliego definitivo')
    await dialog.getByRole('button', { name: 'Guardar' }).click()
    await expect(page.getByRole('cell', { name: 'Pliego definitivo' })).toBeVisible()

    await page.getByRole('button', { name: 'Eliminar anexos.zip' }).click()
    await page.getByRole('dialog', { name: 'Eliminar documento' }).getByRole('button', { name: 'Eliminar' }).click()
    await expect(page.getByText('anexos.zip')).toHaveCount(0)
    await expect(page.getByRole('button', { name: 'Eliminar Pliego de condiciones.pdf' })).toBeDisabled()
  })
})
