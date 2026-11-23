<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor;

/**
 * No-op enhancer: returns matches at their raw detector score. Use it to opt
 * out of context boosting (e.g. new Analyzer($registry, new NullContextEnhancer())).
 */
final class NullContextEnhancer implements ContextEnhancerInterface
{
    /**
     * @param string $text
     * @param PiiMatch $match
     * @param string[] $contextWords
     * @return PiiMatch
     */
    public function enhance(string $text, PiiMatch $match, array $contextWords): PiiMatch
    {
        return $match;
    }
}
