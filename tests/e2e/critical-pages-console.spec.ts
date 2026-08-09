import { expect, test } from '@playwright/test';
import { accountFor, signIn } from './support/auth';

test('critical teacher pages have no uncaught JavaScript errors', async ({ page }) => {
    const account = accountFor('teacher');
    const courseId = process.env.E2E_AUDIT_COURSE_ID;
    const classId = process.env.E2E_AUDIT_CLASS_ID;

    test.skip(!account, 'Chưa cấu hình tài khoản E2E giáo viên.');

    const targets = [
        ['Dashboard', '/dashboard'],
        ['Course', '/courses'],
        ['Assignment', '/assignments'],
        ['Calendar', '/schedules'],
        ['Question Bank', '/question-bank'],
        ['AI', '/quizzes/ai-generate'],
        ['Chatbot', '/documents'],
        ...(courseId ? [
            ['Lesson', `/courses/${courseId}`],
            ['Attendance', `/courses/${courseId}/attendance`],
        ] : []),
        ...(classId ? [['Grade', `/classes/${classId}/progress`]] : []),
    ];

    const uncaughtErrors: string[] = [];
    page.on('pageerror', (error) => uncaughtErrors.push(`pageerror: ${error.message}`));
    page.on('console', (message) => {
        if (message.type() === 'error') {
            uncaughtErrors.push(`console: ${message.text()}`);
        }
    });

    await signIn(page, account!);

    for (const [label, path] of targets) {
        const before = uncaughtErrors.length;
        await page.goto(path);
        await page.waitForLoadState('networkidle');

        expect(
            uncaughtErrors.slice(before),
            `${label} (${path}) phát sinh uncaught JavaScript error`,
        ).toEqual([]);
    }
});
