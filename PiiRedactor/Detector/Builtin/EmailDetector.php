<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\EntityType;

/**
 * Built-in EMAIL detector. RFC-pragmatic pattern with a high base score.
 */
final class EmailDetector
{
    /** Pragmatic RFC subset: bounded labels keep it ReDoS-safe. */
    private const PATTERN = '/[A-Za-z0-9._%+-]+@[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?'
        . '(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?)*\.[A-Za-z]{2,}/u';

    /**
     * @param string[] $contextWords
     * @return PatternDetector
     */
    public static function create(array $contextWords = []): PatternDetector
    {
        return new PatternDetector('email', EntityType::EMAIL->value, [
            new Pattern('rfc-pragmatic', self::PATTERN, 0.9, ['@']),
        ], null, $contextWords);
    }
}
