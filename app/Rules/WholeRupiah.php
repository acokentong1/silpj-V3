<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WholeRupiah implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Accept legacy whole values such as 1000.00, never fractional rupiah.
        if (! preg_match('/^\d+(?:\.0+)?$/D', (string) $value)) {
            $fail('Nominal rupiah harus berupa angka bulat tanpa desimal. Contoh: 100000.');
        }
    }
}
