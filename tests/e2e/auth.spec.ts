import { expect, test } from '@playwright/test';

test.describe('Xác thực và ngôn ngữ mặc định', () => {
    test('trang đăng nhập hiển thị hoàn toàn bằng tiếng Việt', async ({ page }) => {
        await page.goto('/login');

        await expect(page.locator('html')).toHaveAttribute('lang', 'vi');
        await expect(page.getByRole('heading', { name: 'Chào mừng!' })).toBeVisible();
        await expect(page.getByLabel('Tên đăng nhập')).toBeVisible();
        await expect(page.getByLabel('Mật khẩu', { exact: true })).toBeVisible();
        await expect(page.getByRole('button', { name: /Đăng nhập ngay/i })).toBeVisible();
    });

    test('khách chưa đăng nhập không truy cập được trang tổng quan', async ({ page }) => {
        await page.goto('/dashboard');

        await expect(page).toHaveURL(/\/login$/);
    });
});
