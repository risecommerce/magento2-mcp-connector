<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\EntityType;

/**
 * Built-in MAC_ADDRESS detector. Colon- and hyphen-separated notations.
 */
final class MacAddressDetector
{
    /** Six hex pairs sharing one separator (colon or hyphen). */
    private const PATTERN = '/\b[0-9A-Fa-f]{2}(?:([:\-])[0-9A-Fa-f]{2}){5}\b/u';

    /**
     * @param string[] $contextWords
     * @return PatternDetector
     */
    public static function create(array $contextWords = []): PatternDetector
    {
        return new PatternDetector('mac-address', EntityType::MAC_ADDRESS->value, [
            new Pattern('mac', self::PATTERN, 0.7),
        ], null, $contextWords);
    }
}
