<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Strategy;

use Risecommerce\PiiRedactor\PiiMatch;

final class ReplaceStrategy implements StrategyInterface
{
    public function __construct(
        private readonly string $template = '[{type}]',
    ) {
    }

    /**
     * @param PiiMatch $match
     * @param string $original
     * @return string
     */
    public function redact(PiiMatch $match, string $original): string
    {
        return str_replace('{type}', $match->entityType, $this->template);
    }
}
