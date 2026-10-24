<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class V3Calculator
{
    // Decimal arithmetic with BCMath; values returned as whole rupiah strings.
    public static function round(string $value): string
    {
        return bcadd($value, '0.5', 0);
    }

    public static function calculate(array $items, array $components): array
    {
        $subtotal = '0';
        $add = '0';
        $deduct = '0';
        $tax = '0';
        $out = [];
        $comp = [];
        foreach ($items as $i => $item) {
            $amount = self::round(bcmul((string) $item['volume'], (string) $item['harga'], 5));
            $subtotal = bcadd($subtotal, $amount, 0);
            $out[] = ['urutan' => $i + 1, 'nama' => $item['nama'], 'volume' => $item['volume'], 'satuan' => $item['satuan'], 'harga' => $item['harga'], 'jumlah' => $amount];
        }
        foreach ($components as $c) {
            $amount = $c['metode'] === 'persentase' ? self::round(bcdiv(bcmul((string) $c['dasar'], (string) $c['persentase'], 6), '100', 6)) : self::round((string) $c['nominal']);
            $comp[] = ['label' => $c['label'], 'kategori' => $c['kategori'], 'metode' => $c['metode'], 'persentase' => $c['metode'] === 'persentase' ? $c['persentase'] : null, 'dasar' => $c['metode'] === 'persentase' ? $c['dasar'] : null, 'nominal' => $amount, 'perlakuan' => $c['perlakuan']];
            if ($c['perlakuan'] === 'tambah') {
                $add = bcadd($add, $amount, 0);
            }
            if ($c['perlakuan'] === 'potong') {
                $deduct = bcadd($deduct, $amount, 0);
            }
            if ($c['kategori'] === 'pajak') {
                $tax = bcadd($tax, $amount, 0);
            }
        }
        $bruto = bcadd($subtotal, $add, 0);
        $netto = bcsub($bruto, $deduct, 0);
        if (bccomp($netto, '0') < 0) {
            throw ValidationException::withMessages(['components' => 'Potongan melebihi nilai bruto.']);
        }
        if (bccomp($bruto, '99999999999999') > 0) {
            throw ValidationException::withMessages(['items' => 'Nilai transaksi terlalu besar.']);
        }

        return ['items' => $out, 'components' => $comp, 'totals' => ['subtotal' => $subtotal, 'bruto' => $bruto, 'potongan' => $deduct, 'netto' => $netto, 'total_pajak' => $tax]];
    }
}
