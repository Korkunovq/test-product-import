import { test, expect } from '@playwright/test';

test.describe('Product Import app', () => {
  test('redirects unauthenticated user to login', async ({ page }) => {
    await page.goto('/products');
    await expect(page).toHaveURL(/\/login/);
    await expect(page.getByRole('heading', { name: /Вход/i })).toBeVisible();
  });

  test('login and open products list', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill('admin@example.com');
    await page.getByLabel('Пароль').fill('admin123');
    await page.getByRole('button', { name: 'Войти' }).click();

    await expect(page).toHaveURL(/\/products/);
    await expect(page.getByRole('heading', { name: 'Список товаров' })).toBeVisible();
    await expect(page.getByLabel('Поиск по названию')).toBeVisible();
  });

  test('navigate to import page after login', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill('admin@example.com');
    await page.getByLabel('Пароль').fill('admin123');
    await page.getByRole('button', { name: 'Войти' }).click();
    await expect(page).toHaveURL(/\/products/);

    await page.getByRole('link', { name: 'Импорт' }).click();
    await expect(page).toHaveURL(/\/import/);
    await expect(page.getByRole('heading', { name: 'Импорт товаров' })).toBeVisible();
    await expect(page.getByRole('button', { name: /Загрузить/i })).toBeVisible();
  });

  test('open product card from list when products exist', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill('admin@example.com');
    await page.getByLabel('Пароль').fill('admin123');
    await page.getByRole('button', { name: 'Войти' }).click();
    await expect(page).toHaveURL(/\/products/);

    const openLink = page.getByRole('link', { name: 'Открыть →' }).first();
    const count = await openLink.count();
    test.skip(count === 0, 'No products imported yet');

    await openLink.click();
    await expect(page).toHaveURL(/\/products\/\d+/);
    await expect(page.getByRole('link', { name: /К списку товаров/i })).toBeVisible();
  });

  test('logout returns to login', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill('admin@example.com');
    await page.getByLabel('Пароль').fill('admin123');
    await page.getByRole('button', { name: 'Войти' }).click();
    await expect(page).toHaveURL(/\/products/);

    await page.getByRole('button', { name: 'Выход' }).click();
    await expect(page).toHaveURL(/\/login/);
  });
});
