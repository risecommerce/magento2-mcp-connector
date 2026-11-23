<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\EntityType;
use Risecommerce\PiiRedactor\Validator\LuhnValidator;

/**
 * Built-in CREDIT_CARD detector. Low base score; the Luhn checksum promotes
 * a real match to certainty, a failing checksum discards it.
 */
final class CreditCardDetector
{
    /** 12-19 digit run with optional space/dash grouping. */
    private const PATTERN = '/(?<![\d\-])\d(?:[ \-]?\d){11,18}(?![\d\-])/u';

    /**
     * @param string[] $contextWords
     * @return PatternDetector
     */
    public static function create(array $contextWords = []): PatternDetector
    {
        return new PatternDetector('credit-card', EntityType::CREDIT_CARD->value, [
            new Pattern('digit-run', self::PATTERN, 0.3),
        ], new LuhnValidator(), $contextWords, ['-' => '', ' ' => '']);
    }
}
