<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AfghanPhone implements ValidationRule
{
    // Returns the number as 07XXXXXXXX, or null when it is not a valid Afghan mobile number.
    // Accepted: 0700123456, 700123456, +93700123456, 0093700123456
    public static function normalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        // Persian and Arabic digits become normal digits; spaces, dashes and brackets are ignored
        $digits = strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $digits = preg_replace('/[\s\-().]/', '', $digits);

        if (preg_match('/^(?:\+93|0093|93|0)?(7\d{8})$/', $digits, $match)) {
            return '0' . $match[1];
        }

        return null;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::normalize((string) $value) === null) {
            $fail('The phone number is not valid. Use an Afghan mobile number, for example 0700123456 or +93700123456.');
        }
    }
}