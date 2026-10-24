<?php

namespace App\Services;

use App\Models\V3\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiptService
{
    public static function batches(Purchase $p)
    {
        return DB::table('v3_receipts')->where('purchase_id', $p->id)->where('revision', $p->revision)
            ->whereNull('cancelled_at')->where('nopes_id', V3Workflow::current($p, 'nopes')?->id)->orderBy('id')->get();
    }

    public static function totals(Purchase $p): array
    {
        $totals = [];
        foreach (self::batches($p) as $batch) {
            foreach (json_decode($batch->items, true) as $key => $row) {
                foreach (['baik', 'rusak'] as $field) {
                    $totals[$key][$field] = bcadd($totals[$key][$field] ?? '0', (string) $row[$field], 3);
                }
                $totals[$key]['catatan'] = trim(($totals[$key]['catatan'] ?? '').' '.$batch->tanggal.': '.($row['catatan'] ?? ''));
            }
        }

        return $totals;
    }

    public function receive(int $id, array $data): void
    {
        DB::transaction(function () use ($id, $data) {
            $p = V3Workflow::purchase($id, true);
            V3Workflow::assertEditable($p);
            $n = V3Workflow::current($p, 'nopes');
            if (! $n || V3Workflow::current($p, 'bapb')) {
                throw ValidationException::withMessages(['receipt' => 'Penerimaan memerlukan nota pesanan aktif dan BAPB belum terbit.']);
            }
            if (DB::table('v3_receipts')->where('token', $data['token'])->exists()) {
                throw ValidationException::withMessages(['receipt' => 'Formulir ini sudah dikirim. Muat ulang halaman.']);
            }
            $last = self::batches($p)->max('tanggal') ?? $n->tanggal;
            if ($data['tanggal'] < $last || (int) substr($data['tanggal'], 0, 4) !== $p->tahun) {
                throw ValidationException::withMessages(['tanggal' => 'Tanggal penerimaan harus berurutan, setelah nota pesanan, dan dalam tahun anggaran.']);
            }
            $totals = self::totals($p);
            $rows = [];
            $received = '0';
            foreach ($p->items as $item) {
                $key = $item->urutan;
                $row = $data['items'][$key] ?? null;
                if (! $row) {
                    throw ValidationException::withMessages(['items' => 'Isi setiap baris penerimaan.']);
                }
                $amount = bcadd((string) $row['baik'], (string) $row['rusak'], 3);
                $total = bcadd($amount, bcadd($totals[$key]['baik'] ?? '0', $totals[$key]['rusak'] ?? '0', 3), 3);
                if (bccomp($total, (string) $item->volume, 3) > 0) {
                    throw ValidationException::withMessages(['items' => 'Penerimaan kumulatif melebihi pesanan: '.$item->nama]);
                }
                $rows[$key] = $row;
                $received = bcadd($received, $amount, 3);
            }
            if (bccomp($received, '0', 3) <= 0) {
                throw ValidationException::withMessages(['items' => 'Isi minimal satu jumlah barang yang diterima.']);
            }
            $receipt = DB::table('v3_receipts')->insertGetId(['purchase_id' => $p->id, 'nopes_id' => $n->id, 'revision' => $p->revision, 'token' => $data['token'], 'tanggal' => $data['tanggal'], 'items' => json_encode($rows), 'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
            V3Workflow::event('terima_barang', $p, null, ['receipt_id' => $receipt, 'items' => $rows, 'tanggal' => $data['tanggal']]);
        }, 3);
    }
}
