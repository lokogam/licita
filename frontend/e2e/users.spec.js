import { request as playwrightRequest } from '@playwright/test'
import { ADMIN, apiLogin, expect, test } from './fixtures'

/** Unique per run so repeated runs (and other environments) do not collide. */
const email = `e2e-${Date.now()}@licita.test`

/**
 * Removes the test user through the API if a failed run left it behind.
 *
 * @param {string} baseURL
 */
async function cleanUp(baseURL) {
  const api = await playwrightRequest.newContext({ baseURL })
  try {
    const token = await apiLogin(api, ADMIN)
    const headers = { Authorization: `Bearer ${token}` }
    const users = (await (await api.get('/api/users', { headers })).json()).data
    const leftover = users.find((user) => user.email === email)
    if (leftover) await api.delete(`/api/users/${leftover.id}`, { headers })
  } finally {
    await api.dispose()
  }
}

test.describe('Administración de usuarios', () => {
  test.afterEach(async ({ baseURL }) => {
    await cleanUp(baseURL)
  })

  test('crea y elimina un usuario', async ({ page }) => {
    await page.goto('/licitaciones')
    await page.getByRole('link', { name: 'Usuarios' }).click()
    await expect(page.getByRole('heading', { name: 'Usuarios' })).toBeVisible()

    // Own account cannot be deleted
    await expect(page.getByRole('button', { name: `Eliminar ${ADMIN.email}` })).toBeDisabled()

    // Create
    await page.getByRole('button', { name: 'Nuevo usuario' }).click()
    const dialog = page.getByRole('dialog', { name: 'Nuevo usuario' })
    await dialog.locator('#user-name').fill('Usuario E2E')
    await dialog.locator('#user-email').fill(email)
    await dialog.locator('#user-role').selectOption('viewer')
    await dialog.locator('#user-password').fill('corta')
    await dialog.locator('#user-password').blur()
    await expect(dialog.getByText('El campo contraseña debe tener al menos 10 caracteres.')).toBeVisible()
    await dialog.locator('#user-password').fill('Clave-E2E-2026')
    await dialog.getByRole('button', { name: 'Crear usuario' }).click()

    await expect(dialog).toHaveCount(0)
    const row = page.getByRole('row').filter({ hasText: email })
    await expect(row).toContainText('Usuario E2E')
    await expect(row).toContainText('Lector')

    // Duplicate email is rejected by the API
    await page.getByRole('button', { name: 'Nuevo usuario' }).click()
    await dialog.locator('#user-name').fill('Duplicado')
    await dialog.locator('#user-email').fill(email)
    await dialog.locator('#user-role').selectOption('editor')
    await dialog.locator('#user-password').fill('Clave-E2E-2026')
    await dialog.getByRole('button', { name: 'Crear usuario' }).click()
    await expect(dialog.getByText('Ya existe un usuario con ese correo.')).toBeVisible()
    await dialog.getByRole('button', { name: 'Cancelar' }).click()

    // Delete
    await page.getByRole('button', { name: `Eliminar ${email}` }).click()
    const confirm = page.getByRole('dialog', { name: 'Eliminar usuario' })
    await confirm.getByRole('button', { name: 'Eliminar' }).click()

    await expect(confirm).toHaveCount(0)
    await expect(page.getByRole('row').filter({ hasText: email })).toHaveCount(0)
  })
})
