import { expect, test } from '@playwright/test';
import { accountFor, signIn } from './support/auth';

test('lesson editor saves and reloads content without Tiny Cloud', async ({ page }) => {
    const account = accountFor('teacher');
    const courseId = process.env.E2E_EDITOR_COURSE_ID;
    const lessonId = process.env.E2E_EDITOR_LESSON_ID;
    const mutationsAllowed = process.env.E2E_EDITOR_ALLOW_MUTATION === 'true';

    test.skip(!account, 'Chưa cấu hình tài khoản E2E giáo viên.');
    test.skip(!courseId || !lessonId, 'Chưa cấu hình khóa học và bài học fixture cho editor E2E.');
    test.skip(!mutationsAllowed, 'E2E editor chỉ được chạy với fixture cho phép thay đổi rõ ràng.');

    const tinyMceConsoleErrors: string[] = [];
    const externalTinyMceRequests: string[] = [];

    page.on('console', (message) => {
        const text = message.text();
        if (/TinyMCE|no-api-key|read-only/i.test(text) && ['warning', 'error'].includes(message.type())) {
            tinyMceConsoleErrors.push(text);
        }
    });
    page.on('request', (request) => {
        if (/cdn\.tiny\.cloud|cdnjs\.cloudflare\.com\/ajax\/libs\/tinymce/i.test(request.url())) {
            externalTinyMceRequests.push(request.url());
        }
    });

    await signIn(page, account!);
    await page.goto(`/courses/${courseId}`);

    const editButton = page.locator(`.edit-lesson-btn[data-id="${lessonId}"]`);
    await expect(editButton).toHaveCount(1);
    await editButton.click();

    const modal = page.locator('#editLessonModal');
    await expect(modal).toBeVisible();
    const editorBody = page.frameLocator('#editLessonContent_ifr').locator('body');
    await expect(editorBody).toBeVisible();
    const originalHtml = await editorBody.evaluate((element) => element.innerHTML);
    const marker = `TinyMCE E2E ${Date.now()}`;

    try {
        await editorBody.fill(marker);
        await modal.getByRole('button', { name: 'Cập nhật bài học' }).click();
        await expect(page).toHaveURL(new RegExp(`/courses/${courseId}`));

        await page.reload();
        await editButton.click();
        await expect(page.frameLocator('#editLessonContent_ifr').locator('body')).toContainText(marker);
    } finally {
        if (!page.url().includes(`/courses/${courseId}`)) {
            await page.goto(`/courses/${courseId}`);
        }

        const restoreModal = page.locator('#editLessonModal');
        if (!(await restoreModal.isVisible())) {
            await page.locator(`.edit-lesson-btn[data-id="${lessonId}"]`).click();
        }
        const restoreBody = page.frameLocator('#editLessonContent_ifr').locator('body');
        await restoreBody.evaluate((element, html) => {
            element.innerHTML = html;
        }, originalHtml);
        await restoreModal.getByRole('button', { name: 'Cập nhật bài học' }).click();
    }

    expect(externalTinyMceRequests).toEqual([]);
    expect(tinyMceConsoleErrors).toEqual([]);
});
