<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor;

use Risecommerce\PiiRedactor\Strategy\ReplaceStrategy;
use Risecommerce\PiiRedactor\Strategy\StrategyInterface;

final class RedactionConfig
{
    /**
     * @param array<string, StrategyInterface> $strategies Entity type => strategy.
     * @param StrategyInterface $default Fallback for unconfigured types.
     */
    public function __construct(
        private readonly array $strategies = [],
        private readonly StrategyInterface $default = new ReplaceStrategy(),
    ) {
    }

    /**
     * @param string $entityType
     * @return StrategyInterface
     */
    public function strategyFor(string $entityType): StrategyInterface
    {
        return $this->strategies[$entityType] ?? $this->default;
    }
}
