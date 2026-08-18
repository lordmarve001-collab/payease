<?php

namespace App\Helpers;

class PinSecurity
{
    /**
     * Common weak 6-digit PINs that should be rejected.
     *
     * @var array<int, string>
     */
    private static array $commonPins = [
        '000000', '111111', '222222', '333333', '444444',
        '555555', '666666', '777777', '888888', '999999',
        '123456', '654321', '121212', '123123', '112233',
        '101010', '202020', '303030', '404040', '505050',
        '606060', '707070', '808080', '909090',
    ];

    /**
     * Determine whether a 6-digit PIN is considered weak.
     */
    public static function isWeak(string $pin): bool
    {
        if (!preg_match('/^\d{6}$/', $pin)) {
            return true;
        }

        if (in_array($pin, self::$commonPins, true)) {
            return true;
        }

        // Reject strictly sequential (123456, 654321 handled above but catch 234567 etc.)
        if (self::isSequential($pin)) {
            return true;
        }

        // Reject repeated single digit (handled above)
        if (self::isRepeated($pin)) {
            return true;
        }

        return false;
    }

    /**
     * Get a validation error message for weak PINs.
     */
    public static function weakPinMessage(): string
    {
        return 'This PIN is too easy to guess. Choose a 6-digit PIN that is not sequential, repeated, or commonly used.';
    }

    private static function isSequential(string $pin): bool
    {
        $ascending = true;
        $descending = true;

        for ($i = 1; $i < strlen($pin); $i++) {
            $current = (int) $pin[$i];
            $previous = (int) $pin[$i - 1];

            if ($current !== $previous + 1) {
                $ascending = false;
            }
            if ($current !== $previous - 1) {
                $descending = false;
            }
        }

        return $ascending || $descending;
    }

    private static function isRepeated(string $pin): bool
    {
        return count(array_unique(str_split($pin))) === 1;
    }
}
