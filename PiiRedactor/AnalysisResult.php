<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor;

final class AnalysisResult
{
    /**
     * @param PiiMatch[] $matches Non-overlapping, sorted by start offset.
     * @param array<string, string> $failures Detector name => error message.
     */
    public function __construct(
        private readonly array $matches,
        private readonly array $failures = [],
    ) {
    }

    /**
     * @return PiiMatch[]
     */
    public function matches(): array
    {
        return $this->matches;
    }

    /**
     * @return array<string, string>
     */
    public function failures(): array
    {
        return $this->failures;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->matches === [];
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->matches);
    }
}
