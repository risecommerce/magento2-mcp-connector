<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Strategy;

use Risecommerce\PiiRedactor\PiiMatch;

interface StrategyInterface
{
    /**
     * @param PiiMatch $match
     * @param string $original The matched substring from the source text.
     * @return string The replacement text.
     */
    public function redact(PiiMatch $match, string $original): string;
}
