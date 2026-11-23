<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Strategy;

use Risecommerce\PiiRedactor\PiiMatch;

final class MaskStrategy implements StrategyInterface
{
    /**
     * @param string $maskChar
     * @param int $unmaskedCount Characters left visible.
     * @param bool $unmaskedFromEnd Keep visible characters at the end (true) or start (false).
     */
    public function __construct(
        private readonly string $maskChar = '*',
        private readonly int $unmaskedCount = 0,
        private readonly bool $unmaskedFromEnd = true,
    ) {
    }

    /**
     * @param PiiMatch $match
     * @param string $original
     * @return string
     */
    public function redact(PiiMatch $match, string $original): string
    {
        $length = mb_strlen($original);
        $keep = min($this->unmaskedCount, $length);
        $masked = str_repeat($this->maskChar, $length - $keep);

        return $this->unmaskedFromEnd
            ? $masked . mb_substr($original, $length - $keep)
            : mb_substr($original, 0, $keep) . $masked;
    }
}
