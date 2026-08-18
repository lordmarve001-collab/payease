<?php

namespace App\Helpers;

use InvalidArgumentException;

class PhoneNumberHelper
{
    public static function normalize(string $input): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $input);

        if (strlen($cleaned) === 14 && str_starts_with($cleaned, '234')) {
            $cleaned = substr($cleaned, 3);
        }

        if (strlen($cleaned) === 13 && str_starts_with($cleaned, '234')) {
            $cleaned = substr($cleaned, 3);
        }

        if (strlen($cleaned) === 11 && str_starts_with($cleaned, '0')) {
            $cleaned = substr($cleaned, 1);
        }

        if (strlen($cleaned) !== 10 || !str_starts_with($cleaned, '7') && !str_starts_with($cleaned, '8') && !str_starts_with($cleaned, '9')) {
            throw new InvalidArgumentException('Invalid Nigerian phone number: ' . $input);
        }

        return $cleaned;
    }

    public static function formatForDisplay(string $number): string
    {
        $normalized = self::normalize($number);
        return '+234' . $normalized;
    }

    public static function formatForSms(string $number): string
    {
        return self::formatForDisplay($number);
    }
}
