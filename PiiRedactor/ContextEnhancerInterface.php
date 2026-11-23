<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor;

/**
 * Adjusts a match's confidence based on its surrounding text. Inject
 * NullContextEnhancer into the Analyzer to disable context boosting entirely.
 */
interface ContextEnhancerInterface
{
    /**
     * @param string $text
     * @param PiiMatch $match
     * @param string[] $contextWords
     * @return PiiMatch
     */
    public function enhance(string $text, PiiMatch $match, array $contextWords): PiiMatch;
}
