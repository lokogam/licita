import { expect, test } from './fixtures'

test('el listado es usable en pantallas pequeñas', async ({ page }) => {
  await page.goto('/licitaciones')

  await expect(page.getByRole('heading', { level: 1, name: 'Licitaciones' })).toBeVisible()
  await expect(page.getByRole('link', { name: 'Nueva licitación' }).first()).toBeVisible()

  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)
  expect(overflow).toBeLessThanOrEqual(0)
})
