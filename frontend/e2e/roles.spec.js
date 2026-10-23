import { VIEWER, expect, test } from './fixtures'

test.describe('Rol lector', () => {
  test.use({ credentials: VIEWER })

  test('solo consulta: no ve acciones de creación, edición ni usuarios', async ({ page }) => {
    await page.goto('/licitaciones')

    await expect(page.getByRole('heading', { level: 1, name: 'Licitaciones' })).toBeVisible()
    await expect(page.getByRole('button', { name: /^Cuenta de / })).toContainText('Lector')
    await expect(page.getByRole('button', { name: 'Exportar Excel' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Nueva licitación' })).toHaveCount(0)
    await expect(page.getByRole('link', { name: 'Usuarios' })).toHaveCount(0)

    const detail = page.getByRole('link', { name: /^Ver detalle de / }).first()
    if ((await detail.count()) > 0) {
      await detail.click()
      await expect(page).toHaveURL(/\/licitaciones\/\d+$/)
      await expect(page.locator('.consecutive-chip')).toBeVisible()
      await expect(page.getByRole('link', { name: 'Editar' })).toHaveCount(0)
    }
  })

  test('es redirigido al listado si abre una sección sin permiso', async ({ page }) => {
    await page.goto('/licitaciones/nueva')

    await expect(page).toHaveURL(/\/licitaciones$/)
    await expect(page.getByText('No tiene permisos para acceder a esa sección.')).toBeVisible()

    await page.goto('/usuarios')
    await expect(page).toHaveURL(/\/licitaciones$/)
  })
})
