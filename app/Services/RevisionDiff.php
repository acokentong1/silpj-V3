<?php

namespace App\Services;

use Illuminate\Support\Arr;

class RevisionDiff
{
    public static function rows(?string $json): array
    {
        $d = json_decode($json ?? '{}', true);
        $before = $d['before'] ?? null;
        $after = $d['after'] ?? null;
        if (! is_array($before) || ! is_array($after)) {
            return [];
        }
        $clean = function ($value) use (&$clean) {
            if (! is_array($value)) {
                return $value;
            }
            foreach (['id', 'purchase_id', 'vendor_id', 'created_at', 'updated_at', 'school_profile_id'] as $key) {
                unset($value[$key]);
            }

            return array_map($clean, $value);
        };
        $a = Arr::dot($clean($before));
        $b = Arr::dot($clean($after));
        $rows = [];
        foreach (array_unique(array_merge(array_keys($a), array_keys($b))) as $key) {
            $old = $a[$key] ?? null;
            if (is_array($old)) {
                $old = json_encode($old, JSON_UNESCAPED_UNICODE);
            }
            $new = $b[$key] ?? null;
            if (is_array($new)) {
                $new = json_encode($new, JSON_UNESCAPED_UNICODE);
            }
            if ((string) $old !== (string) $new) {
                $rows[] = ['field' => $key, 'before' => $old, 'after' => $new];
            }
        }

        return $rows;
    }
}
