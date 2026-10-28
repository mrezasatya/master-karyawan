<?php

namespace App\Support;

use DateTimeInterface;

class Format
{
    public const MONTHS_SHORT = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public const MONTHS = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    /** "Mei 22" — format periode pada template CV Bukaka. */
    public static function monthYear(?int $month, ?int $year): string
    {
        if (! $month || ! $year) {
            return '-';
        }

        return self::MONTHS_SHORT[$month].' '.substr((string) $year, -2);
    }

    /** "28 Okt 2026" — tampilan tanggal di aplikasi. */
    public static function date(?DateTimeInterface $date): string
    {
        if (! $date) {
            return '-';
        }

        return $date->format('d').' '.self::MONTHS_SHORT[(int) $date->format('n')].' '.$date->format('Y');
    }

    public static function dateTime(?DateTimeInterface $date): string
    {
        return $date ? self::date($date).' '.$date->format('H:i') : '-';
    }

    /** "Dec 04, 1993" — format Date of Birth pada template CV. */
    public static function cvDate(?DateTimeInterface $date): string
    {
        return $date ? $date->format('M d, Y') : '';
    }

    public static function cvSex(?string $value): string
    {
        return match ($value) {
            'Laki-laki' => 'Male',
            'Perempuan' => 'Female',
            default => (string) $value,
        };
    }
}
