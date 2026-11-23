<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\EntityType;
use Risecommerce\PiiRedactor\Validator\GeoRangeValidator;

/**
 * Built-in GEO_COORDINATE detector for decimal-degree pairs. Low base score
 * by design: only context boosting lifts it past the reporting threshold.
 */
final class GeoCoordinateDetector
{
    /** ≥3 decimals per component keeps short price pairs ("12.99, 24.50") out. */
    private const PATTERN = '/(?<![\d.\-])-?\d{1,2}\.\d{3,8}\s{0,3},\s{0,3}-?\d{1,3}\.\d{3,8}(?![\d.])/u';

    /**
     * @param string[] $contextWords
     * @return PatternDetector
     */
    public static function create(array $contextWords = []): PatternDetector
    {
        return new PatternDetector('geo-coordinate', EntityType::GEO_COORDINATE->value, [
            new Pattern('decimal-pair', self::PATTERN, 0.3),
        ], new GeoRangeValidator(), $contextWords);
    }
}
