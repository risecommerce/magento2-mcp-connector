<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\EntityType;
use Risecommerce\PiiRedactor\Validator\BicCountryValidator;

/**
 * Built-in SWIFT_BIC detector. Country-code validation kills all-caps words;
 * the sub-threshold base score means context is required to report.
 */
final class SwiftBicDetector
{
    /** 4 bank chars + 2 country letters + 2 location chars + optional 3-char branch. */
    private const PATTERN = '/\b[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}(?:[A-Z0-9]{3})?\b/u';

    /**
     * @param string[] $contextWords
     * @return PatternDetector
     */
    public static function create(array $contextWords = []): PatternDetector
    {
        return new PatternDetector('swift-bic', EntityType::SWIFT_BIC->value, [
            new Pattern('bic', self::PATTERN, 0.45),
        ], new BicCountryValidator(), $contextWords);
    }
}
