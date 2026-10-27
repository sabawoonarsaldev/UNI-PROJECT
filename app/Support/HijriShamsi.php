<?php

namespace App\Support;

class HijriShamsi
{
    public static function currentYear(): int
    {
        $now = now();

        return self::fromGregorian($now->year, $now->month, $now->day);
    }

    // Years shown in the semester form: newest first
    public static function years(): array
    {
        $current = self::currentYear();

        return range($current + 1, $current - 5);
    }

    public static function fromGregorian(int $gy, int $gm, int $gd): int
    {
        $cumulative = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

        if ($gy > 1600) {
            $jy = 979;
            $gy -= 1600;
        } else {
            $jy = 0;
            $gy -= 621;
        }

        $gy2 = $gm > 2 ? $gy + 1 : $gy;

        $days = 365 * $gy
            + intdiv($gy2 + 3, 4)
            - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400)
            - 80
            + $gd
            + $cumulative[$gm - 1];

        $jy += 33 * intdiv($days, 12053);
        $days %= 12053;

        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
        }

        return $jy;
    }
}