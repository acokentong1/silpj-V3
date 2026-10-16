<?php

namespace App\Support;

final class Terbilang
{
    private const ANGKA = [
        '',
        'satu',
        'dua',
        'tiga',
        'empat',
        'lima',
        'enam',
        'tujuh',
        'delapan',
        'sembilan',
        'sepuluh',
        'sebelas',
    ];

    public static function rupiah(int $nilai): string
    {
        if ($nilai === 0) {
            return 'nol';
        }

        if ($nilai < 0) {
            return 'minus ' . self::rupiah(abs($nilai));
        }

        return trim(self::spell($nilai));
    }

    private static function spell(int $nilai): string
    {
        if ($nilai < 12) {
            return self::ANGKA[$nilai];
        }

        if ($nilai < 20) {
            return self::spell($nilai - 10) . ' belas';
        }

        if ($nilai < 100) {
            return self::spell(intdiv($nilai, 10)) . ' puluh' . self::remainder($nilai % 10);
        }

        if ($nilai < 200) {
            return 'seratus' . self::remainder($nilai - 100);
        }

        if ($nilai < 1000) {
            return self::spell(intdiv($nilai, 100)) . ' ratus' . self::remainder($nilai % 100);
        }

        if ($nilai < 2000) {
            return 'seribu' . self::remainder($nilai - 1000);
        }

        if ($nilai < 1_000_000) {
            return self::spell(intdiv($nilai, 1000)) . ' ribu' . self::remainder($nilai % 1000);
        }

        if ($nilai < 1_000_000_000) {
            return self::spell(intdiv($nilai, 1_000_000)) . ' juta' . self::remainder($nilai % 1_000_000);
        }

        if ($nilai < 1_000_000_000_000) {
            return self::spell(intdiv($nilai, 1_000_000_000)) . ' miliar' . self::remainder($nilai % 1_000_000_000);
        }

        return self::spell(intdiv($nilai, 1_000_000_000_000)) . ' triliun' . self::remainder($nilai % 1_000_000_000_000);
    }

    private static function remainder(int $nilai): string
    {
        return $nilai > 0 ? ' ' . self::spell($nilai) : '';
    }
}
