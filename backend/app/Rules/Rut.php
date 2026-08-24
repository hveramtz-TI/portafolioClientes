<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Rut implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid((string) $value)) {
            $fail('El RUT ingresado no es válido.');
        }
    }

    /**
     * Normalize a RUT string to the canonical form `12345678-5`.
     */
    public static function normalize(string $rut): string
    {
        $rut = strtoupper($rut);
        $rut = preg_replace('/[^0-9K]/', '', $rut);

        if (strlen($rut) < 2) {
            return $rut;
        }

        return substr($rut, 0, -1).'-'.substr($rut, -1);
    }

    /**
     * Validate a RUT (Chilean) using the módulo 11 algorithm.
     */
    private static function isValid(string $rut): bool
    {
        $rut = strtoupper(preg_replace('/[^0-9K]/', '', $rut));

        if (strlen($rut) < 2) {
            return false;
        }

        $body = substr($rut, 0, -1);
        $dv = substr($rut, -1);

        if (! ctype_digit($body) || ! preg_match('/^[0-9K]$/', $dv)) {
            return false;
        }

        $sum = 0;
        $multiplier = 2;

        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $sum += intval($body[$i]) * $multiplier;
            $multiplier = $multiplier < 7 ? $multiplier + 1 : 2;
        }

        $expected = 11 - ($sum % 11);
        $expected = $expected === 11 ? '0' : ($expected === 10 ? 'K' : (string) $expected);

        return $expected === $dv;
    }
}
