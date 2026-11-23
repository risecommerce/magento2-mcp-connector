<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor;

use Risecommerce\PiiRedactor\Strategy\ReplaceStrategy;
use Risecommerce\PiiRedactor\Strategy\StrategyInterface;

final class RedactionConfig
{
    private readonly StrategyInterface $default;

    /**
     * Default is null, not `new ReplaceStrategy()`: see Analyzer::__construct (Magento DI compile).
     *
     * @param array<string, StrategyInterface> $strategies Entity type => strategy.
     * @param StrategyInterface|null $default Fallback for unconfigured types (ReplaceStrategy when null).
     */
    public function __construct(
        private readonly array $strategies = [],
        ?StrategyInterface $default = null,
    ) {
        $this->default = $default ?? new ReplaceStrategy();
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
