<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor;

/**
 * Boosts a match's confidence when one of the detector's context words
 * appears shortly before it (Presidio's LemmaContextAwareEnhancer, simplified).
 */
final class ContextEnhancer implements ContextEnhancerInterface
{
    private const WINDOW_BYTES = 40;
    private const BOOST = 0.35;
    private const MIN_SCORE_WITH_CONTEXT = 0.4;

    /**
     * @param string $text
     * @param PiiMatch $match
     * @param string[] $contextWords
     * @return PiiMatch
     */
    public function enhance(string $text, PiiMatch $match, array $contextWords): PiiMatch
    {
        if ($contextWords === [] || $match->score >= 1.0) {
            return $match;
        }

        $windowStart = max(0, $match->start - self::WINDOW_BYTES);
        $window = substr($text, $windowStart, $match->start - $windowStart);

        // Trim to the current sentence
        $cut = max(
            (int)strrpos($window, '.'),
            (int)strrpos($window, '!'),
            (int)strrpos($window, '?'),
            (int)strrpos($window, "\n"),
        );
        if ($cut > 0) {
            $window = substr($window, $cut + 1);
        }

        $window = mb_strtolower($window);
        foreach ($contextWords as $word) {
            if (str_contains($window, mb_strtolower($word))) {
                $boosted = min(1.0, max(self::MIN_SCORE_WITH_CONTEXT, $match->score + self::BOOST));

                $base = $match->explanation !== null ? $match->explanation . ', ' : '';

                return $match->withScore(
                    $boosted,
                    sprintf('%scontext "%s" (+%.2f)', $base, $word, self::BOOST),
                );
            }
        }

        return $match;
    }
}
