<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\EntityType;

/**
 * Built-in IP_ADDRESS detector. Covers IPv4 and the compressed IPv6 form.
 */
final class IpAddressDetector
{
    /** Octet-validated IPv4; trailing lookaheads reject version strings like 1.2.3.4.5. */
    private const IPV4 = '/(?<![\d.])(?:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)\.){3}'
        . '(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(?!\d)(?!\.[A-Za-z0-9])/u';

    // Intentionally covers only the compressed (::) form; full-form and ::1 are out of
    // scope to avoid false positives on MAC addresses and timestamp-like sequences.
    private const IPV6 = '/(?<![:\w])(?:[A-Fa-f0-9]{1,4}:){1,6}:(?:[A-Fa-f0-9]{1,4}:){0,5}[A-Fa-f0-9]{1,4}(?![\w:])/u';

    /**
     * @param string[] $contextWords
     * @return PatternDetector
     */
    public static function create(array $contextWords = []): PatternDetector
    {
        return new PatternDetector('ip-address', EntityType::IP_ADDRESS->value, [
            new Pattern('ipv4', self::IPV4, 0.6),
            new Pattern('ipv6', self::IPV6, 0.6),
        ], null, $contextWords);
    }
}
