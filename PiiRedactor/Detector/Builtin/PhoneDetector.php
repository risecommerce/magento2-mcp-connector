<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\EntityType;

/**
 * Built-in PHONE detector. International form scores on its own; the bare
 * national form is weak and relies on context boosting to cross threshold.
 */
final class PhoneDetector
{
    /** +CC followed by 6-12 grouped digits; leading lookbehind avoids swallowing a stray prefix. */
    private const INTERNATIONAL = '/(?<![\w.+])\+\d{1,3}(?:[ \-.]?\(?\d{1,4}\)?)?(?:[ \-.]?\d){6,12}(?!\d)/u';

    // Lookbehinds prevent the match from starting mid-number: a stray preceding
    // digit (e.g. "1 26123456") must not be absorbed into the national match.
    private const NATIONAL = '/(?<![\d\w.+\-])(?<!\) )(?:(?<!\d )\(\d{2,4}\)[ \-.]?\d(?:[ \-.]?\d){6,9}'
        . '|(?<!\d\d )\d{2}(?:[ \-.]?\d){5,8})(?![\d.\-])/u';

    /**
     * @param string[] $contextWords
     * @return PatternDetector
     */
    public static function create(array $contextWords = []): PatternDetector
    {
        return new PatternDetector('phone', EntityType::PHONE->value, [
            new Pattern('international', self::INTERNATIONAL, 0.7, ['+']),
            new Pattern('national', self::NATIONAL, 0.25),
        ], null, $contextWords);
    }
}
