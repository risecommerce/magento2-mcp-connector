<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\EntityType;

/**
 * Built-in PRIVATE_KEY detector for PEM key blocks. A fence is unambiguous,
 * so matches score 1.0; the bounded non-greedy body keeps large inputs safe.
 */
final class PrivateKeyDetector
{
    /** Covers plain, RSA/EC/DSA/OPENSSH/ENCRYPTED and PGP "... BLOCK" labels. */
    private const PATTERN = '/-{5}BEGIN [A-Z ]{0,20}PRIVATE KEY(?: BLOCK)?-{5}'
        . '[\s\S]{0,65535}?'
        . '-{5}END [A-Z ]{0,20}PRIVATE KEY(?: BLOCK)?-{5}/u';

    /**
     * @param string[] $contextWords
     * @return PatternDetector
     */
    public static function create(array $contextWords = []): PatternDetector
    {
        return new PatternDetector('private-key', EntityType::PRIVATE_KEY->value, [
            new Pattern('pem-block', self::PATTERN, 1.0, ['PRIVATE KEY']),
        ], null, $contextWords);
    }
}
