<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'a', 'b', 'blockquote', 'br', 'caption', 'code', 'div', 'em', 'figcaption', 'figure',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'img', 'li', 'ol', 'p', 'pre',
        's', 'span', 'strike', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tfoot',
        'th', 'thead', 'tr', 'u', 'ul',
    ];

    private const DROP_WITH_CONTENT = [
        'applet', 'audio', 'base', 'button', 'canvas', 'embed', 'form', 'frame', 'frameset',
        'iframe', 'input', 'link', 'math', 'meta', 'noscript', 'object', 'option', 'script',
        'select', 'source', 'style', 'svg', 'template', 'textarea', 'video',
    ];

    private const GLOBAL_ATTRIBUTES = ['style', 'title'];

    private const TAG_ATTRIBUTES = [
        'a' => ['href', 'rel', 'target'],
        'img' => ['alt', 'height', 'src', 'width'],
        'ol' => ['start', 'type'],
        'li' => ['value'],
        'table' => ['align', 'border', 'cellpadding', 'cellspacing', 'width'],
        'td' => ['align', 'colspan', 'height', 'rowspan', 'valign', 'width'],
        'th' => ['align', 'colspan', 'height', 'rowspan', 'scope', 'valign', 'width'],
        'tr' => ['align', 'valign'],
    ];

    private const SAFE_STYLE_PROPERTIES = [
        'background', 'background-color', 'border', 'border-bottom', 'border-collapse',
        'border-color', 'border-left', 'border-right', 'border-style', 'border-top',
        'border-width', 'color', 'font', 'font-family', 'font-size', 'font-style',
        'font-weight', 'height', 'line-height', 'margin', 'margin-bottom', 'margin-left',
        'margin-right', 'margin-top', 'max-height', 'max-width', 'min-height', 'min-width',
        'padding', 'padding-bottom', 'padding-left', 'padding-right', 'padding-top',
        'text-align', 'text-decoration', 'text-indent', 'vertical-align', 'white-space', 'width',
    ];

    public function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadHTML(
                '<?xml encoding="utf-8" ?><div data-smartlms-sanitizer-root="1">'.$html.'</div>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );

            if (! $loaded) {
                return e(strip_tags($html));
            }

            $root = (new DOMXPath($document))->query('//*[@data-smartlms-sanitizer-root="1"]')->item(0);
            if (! $root instanceof DOMElement) {
                return e(strip_tags($html));
            }

            $this->sanitizeChildren($root);
            $root->removeAttribute('data-smartlms-sanitizer-root');

            $output = '';
            foreach ($root->childNodes as $child) {
                $output .= $document->saveHTML($child);
            }

            return trim($output);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) {
                $parent->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($child);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                $this->sanitizeChildren($child);
                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);

                continue;
            }

            $this->sanitizeAttributes($child, $tag);
            $this->sanitizeChildren($child);
        }
    }

    private function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = array_merge(self::GLOBAL_ATTRIBUTES, self::TAG_ATTRIBUTES[$tag] ?? []);

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            $value = trim($attribute->value);

            if (! in_array($name, $allowed, true)) {
                $element->removeAttributeNode($attribute);

                continue;
            }

            if ($name === 'style') {
                $style = $this->sanitizeStyle($value);
                $style === '' ? $element->removeAttribute('style') : $element->setAttribute('style', $style);

                continue;
            }

            if ($name === 'href' && ! $this->isSafeUrl($value, false)) {
                $element->removeAttribute($name);

                continue;
            }

            if ($name === 'src' && ! $this->isSafeUrl($value, true)) {
                $element->removeAttribute($name);

                continue;
            }

            if (in_array($name, ['height', 'width', 'border', 'cellpadding', 'cellspacing', 'colspan', 'rowspan', 'start', 'value'], true)
                && ! preg_match('/^\d{1,4}(?:\.\d+)?%?$/', $value)) {
                $element->removeAttribute($name);

                continue;
            }

            if ($name === 'target' && ! in_array(strtolower($value), ['_blank', '_self'], true)) {
                $element->removeAttribute($name);
            }
        }

        if ($tag === 'a' && strtolower($element->getAttribute('target')) === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private function sanitizeStyle(string $style): string
    {
        $safe = [];

        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);
            $normalizedValue = strtolower(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if (! in_array($property, self::SAFE_STYLE_PROPERTIES, true)
                || $value === ''
                || preg_match('/(?:expression|javascript|vbscript|data\s*:|url\s*\(|@import|behavior\s*:|-moz-binding|var\s*\()/i', $normalizedValue)
                || preg_match('/[{}<>]/', $value)) {
                continue;
            }

            $safe[] = $property.': '.$value;
        }

        return implode('; ', $safe);
    }

    private function isSafeUrl(string $url, bool $allowRasterDataImage): bool
    {
        $decoded = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $normalized = strtolower(preg_replace('/[\x00-\x20\x7f]+/', '', $decoded) ?? '');

        if ($normalized === '') {
            return false;
        }

        if ($allowRasterDataImage && preg_match('#^data:image/(?:png|gif|jpe?g|webp);base64,[a-z0-9+/=\s]+$#i', $decoded)) {
            return true;
        }

        if (str_starts_with($normalized, '#') || str_starts_with($normalized, '/') || str_starts_with($normalized, '?')) {
            return true;
        }

        $scheme = parse_url($decoded, PHP_URL_SCHEME);
        if ($scheme === false) {
            return false;
        }

        if ($scheme === null) {
            return ! str_contains($normalized, ':');
        }

        $allowedSchemes = $allowRasterDataImage ? ['http', 'https'] : ['http', 'https', 'mailto', 'tel'];

        return in_array(strtolower($scheme), $allowedSchemes, true);
    }
}
