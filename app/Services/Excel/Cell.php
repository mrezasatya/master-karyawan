<?php

namespace App\Services\Excel;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class Cell
{
    public static function isEmpty(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    /** Nilai sel sebagai teks; angka diubah tanpa notasi ilmiah agar NIK/nomor tidak rusak. */
    public static function text(mixed $value): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }
        if (is_float($value)) {
            return floor($value) == $value ? sprintf('%.0f', $value) : rtrim(rtrim(sprintf('%.4F', $value), '0'), '.');
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return trim((string) $value);
    }

    /**
     * Tanggal dari sel Excel (serial number) atau teks (dd/mm/yyyy, yyyy-mm-dd, dd-mm-yyyy, dd.mm.yyyy).
     * Mengembalikan [Y-m-d|null, pesanError|null].
     */
    public static function date(mixed $value): array
    {
        if (self::isEmpty($value)) {
            return [null, null];
        }
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value) && (float) $value > 1000 && (float) $value < 100000)) {
            try {
                return [Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d'), null];
            } catch (Throwable) {
                return [null, 'format tanggal tidak dikenali'];
            }
        }
        if ($value instanceof \DateTimeInterface) {
            return [Carbon::instance($value)->format('Y-m-d'), null];
        }

        $text = trim((string) $value);
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd.m.Y', 'd/m/y', 'Y/m/d', 'Y-m-d H:i:s'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $text);
                if ($date && $date->format($format) === $text) {
                    return [$date->format('Y-m-d'), null];
                }
            } catch (Throwable) {
                // coba format berikutnya
            }
        }

        return [null, "format tanggal \"{$text}\" tidak dikenali (gunakan DD/MM/YYYY)"];
    }

    /** Pemetaan nilai enumerasi lama ke nilai terstandar. Mengembalikan null bila tidak dikenali. */
    public static function enum(string $field, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $options = match ($field) {
            'education_level' => config('karyawan.options.education_level'),
            default => config("karyawan.options.$field") ?? [],
        };
        foreach ($options as $option) {
            if (mb_strtolower($option) === mb_strtolower($value)) {
                return $option;
            }
        }
        $key = mb_strtolower(preg_replace('/\s+/', ' ', trim($value)));
        if ($field === 'status_ptkp') {
            $key = strtoupper(str_replace(['/', ' ', '-'], '', $value));

            return in_array($key, $options, true) ? $key : null;
        }

        return config("karyawan.aliases.$field", [])[$key] ?? null;
    }
}
