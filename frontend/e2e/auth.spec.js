import { ADMIN, expect, test } from './fixtures'

test.describe('Inicio de sesión', () => {
  test.use({ credentials: null })

  test('muestra el error de la API con credenciales incorrectas', async ({ page }) => {
    await page.goto('/login')

    // Unknown account: does not count failed attempts against a real user.
    await page.getByLabel('Correo electrónico').fill(`nadie-${Date.now()}@suplos.test`)
    await page.getByLabel('Contraseña', { exact: true }).fill('Clave-Incorrecta-1')
    await page.getByRole('button', { name: 'Ingresar' }).click()

    await expect(page.getByRole('alert')).toContainText('Correo o contraseña incorrectos.')
    await expect(page).toHaveURL(/\/login/)
  })

  test('valida el formulario antes de enviarlo', async ({ page }) => {
    await page.goto('/login')

    await page.getByLabel('Correo electrónico').fill('correo-invalido')
    await page.getByRole('button', { name: 'Ingresar' }).click()

    await expect(page.getByText('El campo correo debe ser un correo electrónico válido.')).toBeVisible()
    await expect(page.getByText('El campo contraseña es obligatorio.')).toBeVisible()
  })

  test('protege las páginas y vuelve a la solicitada después de ingresar', async ({ page }) => {
    await page.goto('/licitaciones/nueva')
    await expect(page).toHaveURL(/\/login\?redirect=/)

    await page.getByLabel('Correo electrónico').fill(ADMIN.email)
    await page.getByLabel('Contraseña', { exact: true }).fill(ADMIN.password)
    await page.getByRole('button', { name: 'Ingresar' }).click()

    await expect(page).toHaveURL(/\/licitaciones\/nueva$/)
    await expect(page.getByRole('heading', { name: 'Nueva licitación' })).toBeVisible()
  })

  test('conserva la sesión al recargar la página (cookie de renovación)', async ({ page }) => {
    await page.goto('/login')
    await page.getByLabel('Correo electrónico').fill(ADMIN.email)
    await page.getByLabel('Contraseña', { exact: true }).fill(ADMIN.password)
    await page.getByRole('button', { name: 'Ingresar' }).click()
    await expect(page).toHaveURL(/\/licitaciones$/)

    await page.reload()

    await expect(page).toHaveURL(/\/licitaciones$/)
    await expect(page.getByRole('button', { name: /^Cuenta de / })).toBeVisible()
  })
})

test.describe('Cierre de sesión', () => {
  test('cierra la sesión y vuelve a pedir credenciales', async ({ page }) => {
    await page.goto('/licitaciones')
    await expect(page.getByRole('heading', { name: 'Licitaciones' })).toBeVisible()

    await page.getByRole('button', { name: /^Cuenta de / }).click()
    await page.getByRole('menuitem', { name: 'Cerrar sesión' }).click()

    await expect(page).toHaveURL(/\/login$/)
    await page.goto('/licitaciones')
    await expect(page).toHaveURL(/\/login\?redirect=/)
  })
})
