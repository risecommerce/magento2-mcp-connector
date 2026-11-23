<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\EntityType;
use Risecommerce\PiiRedactor\Validator\IbanValidator;

/**
 * Built-in IBAN detector. Low base score; mod-97 validation confirms or
 * discards. Spaces are stripped before validating.
 */
final class IbanDetector
{
    /** Two-letter country + two check digits + 11-30 grouped alphanumerics. */
    private const PATTERN = '/\b[A-Z]{2}\d{2}(?: ?[A-Z0-9]){11,30}\b/u';

    /**
     * @param string[] $contextWords
     * @return PatternDetector
     */
    public static function create(array $contextWords = []): PatternDetector
    {
        return new PatternDetector('iban', EntityType::IBAN->value, [
            new Pattern('iban', self::PATTERN, 0.3),
        ], new IbanValidator(), $contextWords, [' ' => '']);
    }
}
