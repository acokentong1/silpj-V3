<?php

namespace App\Services;

use App\Models\V3\Purchase;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportQuery
{
    public static function filters(Request $r): array
    {
        return $r->validate([
            'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'tahun' => 'nullable|integer|between:2000,2100', 'bulan' => 'nullable|integer|between:1,12',
            'status' => ['nullable', Rule::in(['draf', 'diajukan', 'disetujui', 'revisi', 'dibatalkan'])],
            'activity_id' => ['nullable', 'integer', Rule::exists('v3_activities', 'id')->where('school_profile_id', $r->user()->school_profile_id)],
            'sumber_dana' => 'nullable|string|max:100',
        ]);
    }

    public static function query(int $schoolId, array $filters = [])
    {
        $q = Purchase::where('school_profile_id', $schoolId)->with('activity.budget', 'vendor');
        foreach (['tahun', 'status', 'activity_id'] as $key) {
            if (! empty($filters[$key])) {
                $q->where($key, $filters[$key]);
            }
        }
        if (! empty($filters['from'])) {
            $q->where('tanggal', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $q->where('tanggal', '<=', $filters['to']);
        }
        if (! empty($filters['bulan'])) {
            $q->whereMonth('tanggal', $filters['bulan']);
        }
        if (! empty($filters['sumber_dana'])) {
            $q->whereHas('activity.budget', fn ($b) => $b->where('sumber_dana', $filters['sumber_dana']));
        }

        return $q;
    }
}
