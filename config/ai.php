<?php

return [
    'rate_limits' => [
        'chat_per_minute' => (int) env('AI_CHAT_RATE_LIMIT', 20),
        'generation_per_minute' => (int) env('AI_GENERATION_RATE_LIMIT', 8),
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
