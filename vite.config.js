import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/pages/app-layout.css',
                'resources/css/pages/attendance-show.css',
                'resources/css/pages/chatbot.css',
                'resources/css/pages/class-progress.css',
                'resources/css/pages/class-students.css',
                'resources/css/pages/course-modals.css',
                'resources/css/pages/course-show.css',
                'resources/css/pages/dashboard.css',
                'resources/css/pages/landing.css',
                'resources/css/pages/question-bank.css',
                'resources/css/pages/submission-review.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
