<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Validator;

final class LuhnValidator implements ValidatorInterface
{
    private const MIN_DIGITS = 13;
    private const MAX_DIGITS = 19;

    /**
     * @param string $candidate
     * @return bool|null
     */
    public function validate(string $candidate): ?bool
    {
        $length = strlen($candidate);
        if (!ctype_digit($candidate) || $length < self::MIN_DIGITS || $length > self::MAX_DIGITS) {
            return false;
        }

        $sum = 0;
        $double = false;
        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int)$candidate[$i];
            if ($double) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
            $double = !$double;
        }

        return $sum % 10 === 0;
    }
}
