<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Validator;

final class IbanValidator implements ValidatorInterface
{
    /** @var array<string, int> */
    private const LENGTHS = [
        'AD' => 24, 'AE' => 23, 'AT' => 20, 'BE' => 16, 'BG' => 22, 'CH' => 21,
        'CY' => 28, 'CZ' => 24, 'DE' => 22, 'DK' => 18, 'EE' => 20, 'ES' => 24,
        'FI' => 18, 'FR' => 27, 'GB' => 22, 'GR' => 27, 'HR' => 21, 'HU' => 28,
        'IE' => 22, 'IS' => 26, 'IT' => 27, 'LI' => 21, 'LT' => 20, 'LU' => 20,
        'LV' => 21, 'MC' => 27, 'MT' => 31, 'NL' => 18, 'NO' => 15, 'PL' => 28,
        'PT' => 25, 'RO' => 24, 'SE' => 24, 'SI' => 19, 'SK' => 24, 'SM' => 27,
    ];

    /**
     * @param string $candidate
     * @return bool|null
     */
    public function validate(string $candidate): ?bool
    {
        $iban = strtoupper($candidate);
        if (preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]+$/', $iban) !== 1) {
            return false;
        }

        $country = substr($iban, 0, 2);
        $expectedLength = self::LENGTHS[$country] ?? null;
        if ($expectedLength === null || strlen($iban) !== $expectedLength) {
            return false;
        }

        return $this->mod97($iban) === 1;
    }

    /**
     * @param string $iban
     * @return int
     */
    private function mod97(string $iban): int
    {
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        $numeric = '';
        foreach (str_split($rearranged) as $char) {
            $numeric .= ctype_alpha($char) ? (string)(ord($char) - 55) : $char;
        }

        $remainder = 0;
        foreach (str_split($numeric, 7) as $chunk) {
            $remainder = (int)((int)($remainder . $chunk) % 97);
        }

        return $remainder;
    }
}
