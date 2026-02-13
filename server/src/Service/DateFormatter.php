<?php

namespace App\Service;

use DateTimeInterface;
use IntlDateFormatter;

final class DateFormatter
{
    public function short(DateTimeInterface | null $dt, string $tz = 'Europe/Paris'): string | null
    {
        return $this->format($dt, 'd MMM y', $tz); // 17 nov. 1999
    }

    public function long(DateTimeInterface | null $dt, string $tz = 'Europe/Paris'): string | null
    {
        return $this->format($dt, 'd MMMM y', $tz); // 17 novembre 1999
    }

    private function format(DateTimeInterface | null $dt, string $pattern, string $tz): string | null
    {
        if (!$dt) {
            return null;
        }
        $fmt = new IntlDateFormatter(
            'fr_FR',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $tz,
            IntlDateFormatter::GREGORIAN,
            $pattern
        );

        $out = $fmt->format($dt);
        return $out === false ? '' : $out;
    }
}
