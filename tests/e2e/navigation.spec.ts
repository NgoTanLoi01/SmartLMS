import { expect, test } from '@playwright/test';
import { accountFor, signIn, TestRole } from './support/auth';

const expectations: Record<TestRole, { visible: string[]; hidden: string[] }> = {
    admin: {
        visible: ['nav-group-training', 'nav-group-content', 'nav-group-operations', 'nav-group-system'],
        hidden: ['nav-student-schedule'],
    },
    teacher: {
        visible: ['nav-group-training', 'nav-group-content', 'nav-group-operations'],
        hidden: ['nav-group-system', 'nav-student-schedule'],
    },
    student: {
        visible: ['nav-student-primary', 'nav-courses', 'nav-student-schedule', 'nav-student-grades'],
        hidden: ['nav-group-learning', 'nav-group-training', 'nav-group-system'],
    },
};

for (const role of Object.keys(expectations) as TestRole[]) {
    test(`menu ${role} chỉ hiển thị đúng nhóm chức năng`, async ({ page }) => {
        const account = accountFor(role);
        test.skip(!account, `Chưa cấu hình tài khoản E2E cho vai trò ${role}.`);

        await signIn(page, account!);
        await expect(page.getByTestId('main-sidebar')).toBeVisible();
        await expect(page.getByTestId('nav-dashboard')).toHaveAttribute('aria-current', 'page');

        for (const testId of expectations[role].visible) {
            await expect(page.getByTestId(testId)).toBeVisible();
        }
        for (const testId of expectations[role].hidden) {
            await expect(page.getByTestId(testId)).toHaveCount(0);
        }
    });
}

test('menu điện thoại mở, đóng bằng Escape và không che nội dung sau khi đóng', async ({ page }) => {
    const account = accountFor('admin');
    test.skip(!account, 'Chưa cấu hình tài khoản E2E quản trị viên.');

    await page.setViewportSize({ width: 390, height: 844 });
    await signIn(page, account!);

    const sidebar = page.getByTestId('main-sidebar');
    await expect(sidebar).not.toHaveClass(/show/);
    await page.locator('#sidebarToggle').click();
    await expect(sidebar).toHaveClass(/show/);
    await page.keyboard.press('Escape');
    await expect(sidebar).not.toHaveClass(/show/);
});
