<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector;

/**
 * A named regex with the confidence assigned to its raw (unvalidated) matches.
 */
final class Pattern
{
    /**
     * @param string $name
     * @param string $regex
     * @param float $baseScore
     * @param string[] $requiredNeedles Cheap pre-filter: the regex can only match
     *     when at least one of these literal substrings is present. Empty = always run.
     *     Every needle MUST be guaranteed present in any actual match, or matches are lost.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $regex,
        public readonly float $baseScore,
        public readonly array $requiredNeedles = [],
    ) {
    }

    /**
     * True when the regex could match given a cheap literal-substring scan
     * (always true when no needles are configured).
     *
     * @param string $text
     * @return bool
     */
    public function mayMatch(string $text): bool
    {
        if ($this->requiredNeedles === []) {
            return true;
        }

        foreach ($this->requiredNeedles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
