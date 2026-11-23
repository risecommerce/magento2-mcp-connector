<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\EntityType;

/**
 * Built-in VAT_ID detector: strict EU-27 per-country shapes, no checksum
 * math. RO is narrowed to 6-10 digits to avoid prose-number noise.
 */
final class VatIdDetector
{
    /** Countries whose id is country code + digits only. */
    private const NUMERIC = '/\b(?:BG ?\d{9,10}|CZ ?\d{8,10}|DE ?\d{9}|DK ?\d{8}|EE ?\d{9}|EL ?\d{9}'
        . '|FI ?\d{8}|HR ?\d{11}|HU ?\d{8}|IT ?\d{11}|LT ?\d{9}(?:\d{3})?|LU ?\d{8}|LV ?\d{11}'
        . '|MT ?\d{8}|PL ?\d{10}|PT ?\d{9}|RO ?\d{6,10}|SE ?\d{12}|SI ?\d{8}|SK ?\d{10})\b/u';

    /** Countries with letters or check characters in the id. */
    private const SPECIAL = '/\b(?:ATU ?\d{8}|BE ?[01]\d{9}|CY ?\d{8}[A-Z]|ES ?[A-Z0-9]\d{7}[A-Z0-9]'
        . '|FR ?[A-Z0-9]{2} ?\d{9}|IE ?\d{7}[A-W][A-I]?|NL ?\d{9}B\d{2})\b/u';

    /**
     * @param string[] $contextWords
     * @return PatternDetector
     */
    public static function create(array $contextWords = []): PatternDetector
    {
        return new PatternDetector('vat-id', EntityType::VAT_ID->value, [
            new Pattern('vat-numeric', self::NUMERIC, 0.6),
            new Pattern('vat-special', self::SPECIAL, 0.6),
        ], null, $contextWords);
    }
}
