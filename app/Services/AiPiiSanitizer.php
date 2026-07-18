<?php

namespace App\Services;

class AiPiiSanitizer
{
    public function redactText(string $text): string
    {
        $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu', '[EMAIL_DA_AN]', $text) ?? $text;
        $text = preg_replace('/(?<!\d)(?:\+?84|0)(?:[ .-]?\d){9,10}(?!\d)/u', '[SO_DIEN_THOAI_DA_AN]', $text) ?? $text;

        return $text;
    }

    public function redactRecursive(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->redactText($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        return array_map(fn ($item) => $this->redactRecursive($item), $value);
    }

    public function restoreReferences(mixed $value, array $referenceMap): mixed
    {
        if (is_string($value)) {
            return str_replace(array_keys($referenceMap), array_values($referenceMap), $value);
        }

        if (! is_array($value)) {
            return $value;
        }

        return array_map(fn ($item) => $this->restoreReferences($item, $referenceMap), $value);
    }
}
