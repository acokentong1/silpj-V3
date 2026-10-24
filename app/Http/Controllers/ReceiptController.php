<?php

namespace App\Http\Controllers;

use App\Services\ReceiptService;
use Illuminate\Http\Request;

class ReceiptController extends SchoolController
{
    public function store(Request $r, int $id)
    {
        $this->editor();
        $data = $r->validate(['token' => 'required|uuid', 'tanggal' => 'required|date_format:Y-m-d', 'items' => 'required|array|max:200',
            'items.*.baik' => 'required|numeric|min:0|max:1000000|decimal:0,3',
            'items.*.rusak' => 'required|numeric|min:0|max:1000000|decimal:0,3', 'items.*.catatan' => 'nullable|string|max:255']);
        app(ReceiptService::class)->receive($id, $data);

        return back()->with('success', 'Tahap penerimaan tersimpan. BAPB diterbitkan setelah seluruh volume diterima.');
    }
}
