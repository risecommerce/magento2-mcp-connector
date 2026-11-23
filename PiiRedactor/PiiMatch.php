<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor;

use InvalidArgumentException;

/**
 * A single PII finding: a byte-offset span in the analyzed text.
 */
final class PiiMatch
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly string $entityType,
        public readonly int $start,
        public readonly int $end,
        public readonly float $score,
        public readonly string $detectorName,
        public readonly ?string $explanation = null,
    ) {
        if ($start < 0 || $end <= $start) {
            throw new InvalidArgumentException(
                sprintf('Invalid span [%d, %d): end must be greater than start, start must be >= 0.', $start, $end)
            );
        }
        if ($score < 0.0 || $score > 1.0) {
            throw new InvalidArgumentException(sprintf('Score %g is outside [0.0, 1.0].', $score));
        }
    }

    /**
     * @return int
     */
    public function length(): int
    {
        return $this->end - $this->start;
    }

    /**
     * @param PiiMatch $other
     * @return bool
     */
    public function overlaps(self $other): bool
    {
        return $this->start < $other->end && $other->start < $this->end;
    }

    /**
     * @param PiiMatch $other
     * @return bool
     */
    public function contains(self $other): bool
    {
        return $this->start <= $other->start && $other->end <= $this->end;
    }

    /**
     * @param float $score
     * @param string|null $explanation
     * @return self
     * @throws InvalidArgumentException
     */
    public function withScore(float $score, ?string $explanation = null): self
    {
        return new self(
            $this->entityType,
            $this->start,
            $this->end,
            $score,
            $this->detectorName,
            $explanation ?? $this->explanation,
        );
    }

    /**
     * @param int $delta
     * @return self
     * @throws InvalidArgumentException
     */
    public function withOffset(int $delta): self
    {
        return new self(
            $this->entityType,
            $this->start + $delta,
            $this->end + $delta,
            $this->score,
            $this->detectorName,
            $this->explanation,
        );
    }
}
