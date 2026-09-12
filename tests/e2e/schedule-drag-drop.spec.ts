import { expect, test } from '@playwright/test';
import { accountFor, signIn } from './support/auth';

test('teacher drags a calendar event and the backend receives the new slot', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'desktop', 'Kéo thả lịch chỉ bật trên màn hình desktop.');
    const account = accountFor('teacher');
    test.skip(!account, 'Chưa cấu hình tài khoản E2E giáo viên.');

    await signIn(page, account!);

    let originalDate = '';
    let submittedPayload: Record<string, unknown> | null = null;
    await page.route('**/schedules?*', async (route) => {
        const request = route.request();
        const url = new URL(request.url());
        if (request.method() !== 'GET' || !url.searchParams.has('start')) {
            await route.continue();
            return;
        }

        const eventDate = new Date(url.searchParams.get('start')!);
        eventDate.setDate(eventDate.getDate() + 2);
        originalDate = [
            eventDate.getFullYear(),
            String(eventDate.getMonth() + 1).padStart(2, '0'),
            String(eventDate.getDate()).padStart(2, '0'),
        ].join('-');

        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify([{
                id: 991001,
                title: 'Lịch kéo thả E2E (Lớp E2E)',
                start: `${originalDate}T10:00:00`,
                end: `${originalDate}T11:00:00`,
                extendedProps: {
                    class_id: 9001,
                    course_id: 9002,
                    room: 'E2E',
                    note: '',
                    series_id: null,
                },
            }]),
        });
    });
    await page.route('**/schedules/991001', async (route) => {
        if (route.request().method() !== 'PUT') {
            await route.continue();
            return;
        }
        submittedPayload = route.request().postDataJSON();
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ status: 'success', message: 'Đã cập nhật lịch E2E.' }),
        });
    });

    await page.goto('/schedules');
    const event = page.locator('.fc-timegrid-event').filter({ hasText: 'Lịch kéo thả E2E' });
    await expect(event).toBeVisible();
    const eventBox = await event.boundingBox();
    const columnsBox = await page.locator('.fc-timegrid-cols').boundingBox();
    expect(eventBox).not.toBeNull();
    expect(columnsBox).not.toBeNull();

    const startX = eventBox!.x + eventBox!.width / 2;
    const startY = eventBox!.y + Math.min(eventBox!.height / 2, 18);
    const oneDay = columnsBox!.width / 7;
    await page.mouse.move(startX, startY);
    await page.mouse.down();
    await page.mouse.move(startX + oneDay, startY, { steps: 12 });
    await page.mouse.up();

    await expect.poll(() => submittedPayload).not.toBeNull();
    expect(submittedPayload).toMatchObject({
        class_id: 9001,
        course_id: 9002,
        mutation_source: 'calendar_drag',
        update_scope: 'occurrence',
    });
    expect(submittedPayload!.schedule_date).not.toBe(originalDate);
    await expect(page.locator('#scheduleFeedback')).toContainText('Đã cập nhật lịch E2E.');
});

test('recurring drag offers this-and-following scope', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'desktop', 'Kéo thả lịch chỉ bật trên màn hình desktop.');
    const account = accountFor('teacher');
    test.skip(!account, 'Chưa cấu hình tài khoản E2E giáo viên.');

    await signIn(page, account!);
    await page.goto('/schedules');

    const scopeModal = page.locator('#scheduleDragScopeModal');
    await expect(scopeModal.getByRole('button', { name: 'Buổi này và các buổi sau' })).toHaveCount(1);
});
