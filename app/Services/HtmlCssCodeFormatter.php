<?php

namespace App\Services;

class HtmlCssCodeFormatter
{
    private const VOID_ELEMENTS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
        'link', 'meta', 'param', 'source', 'track', 'wbr',
    ];

    public function format(string $code): string
    {
        $code = trim(str_replace(["\r\n", "\r"], "\n", $code));
        if ($code === '') {
            return '';
        }

        $code = preg_replace_callback(
            '/(<style\b[^>]*>)(.*?)(<\/style\s*>)/isu',
            fn (array $matches) => $matches[1]."\n".$this->formatCss($matches[2])."\n".$matches[3],
            $code
        ) ?? $code;

        $tokens = preg_split(
            '/(<!--[\s\S]*?-->|<![^>]*>|<[^>]+>)/u',
            $code,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        ) ?: [$code];

        $lines = [];
        $indent = 0;
        $inStyle = false;
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            $isClosingStyle = (bool) preg_match('/^<\s*\/\s*style\b/iu', $token);
            if ($isClosingStyle) {
                $inStyle = false;
            }

            if (preg_match('/^<\s*\/\s*([a-z0-9:-]+)/iu', $token)) {
                $indent = max(0, $indent - 1);
            }

            foreach (preg_split('/\n/u', $token) ?: [] as $line) {
                $line = $inStyle ? rtrim($line) : trim($line);
                if (trim($line) !== '') {
                    $lines[] = str_repeat('  ', $indent).$line;
                }
            }

            if ($this->isOpeningTag($token)) {
                $indent++;
                if (preg_match('/^<\s*style\b/iu', $token)) {
                    $inStyle = true;
                }
            }
        }

        return implode("\n", $lines);
    }

    private function formatCss(string $css): string
    {
        $css = trim(str_replace(["\r\n", "\r"], "\n", $css));
        $output = '';
        $indent = 0;
        $buffer = '';
        $quote = null;
        $inComment = false;
        $length = strlen($css);

        $flush = function () use (&$buffer, &$output, &$indent): void {
            $value = trim(preg_replace('/\s+/u', ' ', $buffer) ?? $buffer);
            if ($value !== '') {
                $output .= str_repeat('  ', $indent).$value;
            }
            $buffer = '';
        };

        for ($index = 0; $index < $length; $index++) {
            $char = $css[$index];
            $next = $index + 1 < $length ? $css[$index + 1] : '';

            if ($inComment) {
                $buffer .= $char;
                if ($char === '*' && $next === '/') {
                    $buffer .= '/';
                    $index++;
                    $inComment = false;
                }

                continue;
            }

            if ($quote !== null) {
                $buffer .= $char;
                if ($char === '\\' && $next !== '') {
                    $buffer .= $next;
                    $index++;
                } elseif ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '/' && $next === '*') {
                $buffer .= '/*';
                $index++;
                $inComment = true;

                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                $buffer .= $char;

                continue;
            }

            if ($char === '{') {
                $flush();
                $output = rtrim($output)." {\n";
                $indent++;

                continue;
            }

            if ($char === ';') {
                $flush();
                $output = rtrim($output).";\n";

                continue;
            }

            if ($char === '}') {
                $flush();
                $indent = max(0, $indent - 1);
                $output = rtrim($output)."\n".str_repeat('  ', $indent)."}\n";

                continue;
            }

            $buffer .= $char;
        }

        $flush();

        return trim($output);
    }

    private function isOpeningTag(string $token): bool
    {
        if (! preg_match('/^<\s*([a-z0-9:-]+)/iu', $token, $matches)) {
            return false;
        }

        $name = mb_strtolower($matches[1]);

        return ! str_ends_with(trim($token), '/>')
            && ! in_array($name, self::VOID_ELEMENTS, true)
            && ! preg_match('/<\s*\/\s*'.preg_quote($name, '/').'\s*>\s*$/iu', $token);
    }
}
