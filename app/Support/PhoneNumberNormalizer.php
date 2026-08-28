<?php

namespace App\Support;

use InvalidArgumentException;

class PhoneNumberNormalizer
{
    public static function forProvider(?string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phoneNumber) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) < 10 || strlen($digits) > 14) {
            throw new InvalidArgumentException(
                'We could not generate your virtual account. Please confirm your profile phone number or contact support.'
            );
        }

        return $digits;
    }
}
