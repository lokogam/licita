/**
 * Page object for the create / edit tender form.
 */
export class TenderFormPage {
  /** @param {import('@playwright/test').Page} page */
  constructor(page) {
    this.page = page
    this.subject = page.getByLabel('Objeto')
    this.description = page.getByLabel('Descripción / Alcance')
    this.currency = page.getByLabel('Moneda')
    this.budget = page.getByLabel('Presupuesto')
    this.activity = page.getByRole('combobox', { name: 'Actividad' })
    this.startDate = page.getByLabel('Fecha de inicio')
    this.startTime = page.getByLabel('Hora de inicio')
    this.closingDate = page.getByLabel('Fecha de cierre')
    this.closingTime = page.getByLabel('Hora de cierre')
  }

  async gotoCreate() {
    await this.page.goto('/licitaciones/nueva')
    await this.page.getByRole('heading', { name: 'Nueva licitación' }).waitFor()
  }

  /**
   * @param {{subject: string, description: string, currency: string, budget: string, activitySearch: string,
   *   startDate: string, startTime: string, closingDate: string, closingTime: string}} data
   */
  async fill(data) {
    await this.subject.fill(data.subject)
    await this.description.fill(data.description)
    await this.currency.selectOption(data.currency)
    await this.budget.fill(data.budget)
    await this.chooseActivity(data.activitySearch)
    await this.startDate.fill(data.startDate)
    await this.startTime.fill(data.startTime)
    await this.closingDate.fill(data.closingDate)
    await this.closingTime.fill(data.closingTime)
    await this.closingTime.blur()
  }

  /** @param {string} term */
  async chooseActivity(term) {
    await this.activity.click()
    await this.activity.fill(term)
    const option = this.page
      .getByRole('option')
      .filter({ hasText: new RegExp(term, 'i') })
      .first()
    await option.waitFor()
    await option.dispatchEvent('mousedown')
  }

  /**
   * @param {{title: string, description: string, file: {name: string, mimeType: string, buffer: Buffer}}} document
   */
  async addDocument(document) {
    await this.page.getByRole('button', { name: 'Agregar documento' }).click()
    const dialog = this.page.getByRole('dialog', { name: 'Agregar documento' })
    await dialog.getByLabel('Título').fill(document.title)
    await dialog.getByLabel('Descripción').fill(document.description)
    await dialog.getByLabel('Archivo').setInputFiles(document.file)
    await dialog.getByRole('button', { name: 'Agregar', exact: true }).click()
    await dialog.waitFor({ state: 'detached' })
  }

  /** @param {string} name */
  submitButton(name) {
    return this.page.getByRole('button', { name })
  }
}
