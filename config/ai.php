<?php

return [
    'rate_limits' => [
        'chat_per_minute' => (int) env('AI_CHAT_RATE_LIMIT', 20),
        'generation_per_minute' => (int) env('AI_GENERATION_RATE_LIMIT', 8),
    ],

    'course_plan' => [
        'connect_timeout_seconds' => (int) env('AI_COURSE_PLAN_CONNECT_TIMEOUT_SECONDS', 10),
        'timeout_seconds' => (int) env('AI_COURSE_PLAN_TIMEOUT_SECONDS', 180),
        'outline_max_tokens' => (int) env('AI_COURSE_PLAN_OUTLINE_MAX_TOKENS', 7000),
        'max_tokens' => (int) env('AI_COURSE_PLAN_MAX_TOKENS', 7000),
        'detail_batch_size' => (int) env('AI_COURSE_PLAN_DETAIL_BATCH_SIZE', 2),
        'thinking_enabled' => filter_var(env('AI_COURSE_PLAN_THINKING_ENABLED', false), FILTER_VALIDATE_BOOL),
        'detail_validation_attempts' => (int) env('AI_COURSE_PLAN_DETAIL_VALIDATION_ATTEMPTS', 2),
        'request_attempts' => (int) env('AI_COURSE_PLAN_REQUEST_ATTEMPTS', 2),
        'retry_delay_milliseconds' => (int) env('AI_COURSE_PLAN_RETRY_DELAY_MILLISECONDS', 1000),
        'job_timeout_seconds' => (int) env('AI_COURSE_PLAN_JOB_TIMEOUT_SECONDS', 570),
        'poll_interval_milliseconds' => (int) env('AI_COURSE_PLAN_POLL_INTERVAL_MILLISECONDS', 2000),
        'poll_timeout_seconds' => (int) env('AI_COURSE_PLAN_POLL_TIMEOUT_SECONDS', 900),
    ],

    'rag' => [
        'result_limit' => (int) env('AI_RAG_RESULT_LIMIT', 5),
        'context_limit' => (int) env('AI_RAG_CONTEXT_LIMIT', 9000),
        'max_distance' => (float) env('AI_RAG_MAX_DISTANCE', 0.65),
        'distance_margin' => (float) env('AI_RAG_DISTANCE_MARGIN', 0.18),
    ],

    'embedding' => [
        'dimensions' => (int) env('AI_EMBEDDING_DIMENSIONS', 3072),
        'chunk_size' => (int) env('AI_EMBEDDING_CHUNK_SIZE', 1200),
        'chunk_overlap' => (int) env('AI_EMBEDDING_CHUNK_OVERLAP', 200),
    ],

    'ocr' => [
        'enabled' => filter_var(env('AI_OCR_ENABLED', true), FILTER_VALIDATE_BOOL),
        'languages' => env('AI_OCR_LANGUAGES', 'vie+eng'),
        'max_pages' => (int) env('AI_OCR_MAX_PAGES', 50),
        'timeout_seconds_per_page' => (int) env('AI_OCR_TIMEOUT_PER_PAGE', 60),
        'minimum_text_characters' => (int) env('AI_OCR_MINIMUM_TEXT_CHARACTERS', 80),
    ],
];
