<?php

namespace App\Casts;

use App\Services\HtmlSanitizer;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class SanitizedHtml implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return is_string($value) ? (new HtmlSanitizer)->sanitize($value) : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return is_string($value) ? (new HtmlSanitizer)->sanitize($value) : null;
    }
}
