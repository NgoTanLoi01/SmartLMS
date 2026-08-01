import { expect, Page } from '@playwright/test';

export type TestRole = 'admin' | 'teacher' | 'student';

export type TestAccount = {
    username: string;
    password: string;
};

export function accountFor(role: TestRole): TestAccount | null {
    const prefix = `E2E_${role.toUpperCase()}`;
    const username = process.env[`${prefix}_USERNAME`];
    const password = process.env[`${prefix}_PASSWORD`];

    return username && password ? { username, password } : null;
}

export async function signIn(page: Page, account: TestAccount): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Tên đăng nhập').fill(account.username);
    await page.getByLabel('Mật khẩu', { exact: true }).fill(account.password);
    await page.getByRole('button', { name: /Đăng nhập ngay/i }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
}
