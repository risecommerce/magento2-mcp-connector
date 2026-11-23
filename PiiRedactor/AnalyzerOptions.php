<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor;

use Risecommerce\PiiRedactor\Detector\DetectorInterface;

final class AnalyzerOptions
{
    /** @var string[]|null */
    public readonly ?array $entityTypes;

    /**
     * @param string[]|EntityType[]|null $entityTypes Restrict analysis to these types (null = all).
     * @param float $minScore Matches below this score are dropped.
     * @param string[] $allowList Literal values never reported as PII.
     * @param DetectorInterface[] $adHocDetectors Extra detectors for this call only.
     * @param bool $strict Rethrow detector failures instead of recording them.
     * @param int $maxLength Maximum input size in bytes.
     */
    public function __construct(
        ?array $entityTypes = null,
        public readonly float $minScore = 0.5,
        public readonly array $allowList = [],
        public readonly array $adHocDetectors = [],
        public readonly bool $strict = false,
        public readonly int $maxLength = 1_000_000,
    ) {
        $this->entityTypes = $entityTypes === null
            ? null
            : array_map(
                static fn (string|EntityType $t): string => $t instanceof EntityType ? $t->value : $t,
                $entityTypes,
            );
    }
}
