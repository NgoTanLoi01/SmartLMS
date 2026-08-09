<?php

namespace App\Support;

final class Utf8MojibakeRepair
{
    /** @var list<string> */
    private const MOJIBAKE_MARKERS = ['Ã', 'Â', 'Ä', 'Æ', 'áº', 'á»'];

    public function repair(string $value): ?string
    {
        $beforeScore = $this->mojibakeScore($value);
        if ($beforeScore === 0) {
            return null;
        }

        $repaired = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');

        if (! mb_check_encoding($repaired, 'UTF-8') || $this->mojibakeScore($repaired) >= $beforeScore) {
            return null;
        }

        return $repaired;
    }

    private function mojibakeScore(string $value): int
    {
        $score = 0;

        foreach (self::MOJIBAKE_MARKERS as $marker) {
            $score += substr_count($value, $marker);
        }

        return $score;
    }
}
