<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\EntityType;

/**
 * Built-in CRYPTO_ADDRESS detector. BTC (legacy base58 + bech32) and ETH.
 */
final class CryptoAddressDetector
{
    /** Legacy BTC: base58 alphabet (no 0/O/I/l), 25-34 chars after a 1 or 3. */
    private const BTC_LEGACY = '/\b[13][a-km-zA-HJ-NP-Z1-9]{25,34}\b/u';

    /** Bech32 BTC: bc1 prefix. */
    private const BTC_BECH32 = '/\bbc1[a-z0-9]{20,60}\b/u';

    /** Ethereum: 0x followed by exactly 40 hex chars. */
    private const ETH = '/\b0x[a-fA-F0-9]{40}\b/u';

    /**
     * @param string[] $contextWords
     * @return PatternDetector
     */
    public static function create(array $contextWords = []): PatternDetector
    {
        return new PatternDetector('crypto-address', EntityType::CRYPTO_ADDRESS->value, [
            new Pattern('btc-legacy', self::BTC_LEGACY, 0.6),
            new Pattern('btc-bech32', self::BTC_BECH32, 0.8, ['bc1']),
            new Pattern('eth', self::ETH, 0.8, ['0x']),
        ], null, $contextWords);
    }
}
