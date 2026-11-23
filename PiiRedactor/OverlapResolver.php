<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor;

/**
 * Resolves conflicts between detectors: higher score wins, then longer span;
 * identical spans are merged.
 */
final class OverlapResolver
{
    /**
     * @param PiiMatch[] $matches
     * @return PiiMatch[] Non-overlapping, sorted by start offset.
     */
    public function resolve(array $matches): array
    {
        // Zero or one match cannot overlap; skip both sorts and the scan.
        if (count($matches) < 2) {
            return array_values($matches);
        }

        usort(
            $matches,
            static fn (PiiMatch $a, PiiMatch $b): int => $b->score <=> $a->score
                ?: $b->length() <=> $a->length()
                ?: $a->start <=> $b->start,
        );

        /** @var PiiMatch[] $accepted */
        $accepted = [];
        foreach ($matches as $candidate) {
            foreach ($accepted as $i => $winner) {
                if (!$candidate->overlaps($winner)) {
                    continue;
                }
                if ($candidate->start === $winner->start && $candidate->end === $winner->end) {
                    $merged = trim(($winner->explanation ?? '') . '; ' . ($candidate->explanation ?? ''), '; ');
                    $accepted[$i] = $winner->withScore(
                        $winner->score,
                        $merged !== '' ? $merged : null,
                    );
                }
                continue 2;
            }
            $accepted[] = $candidate;
        }

        usort($accepted, static fn (PiiMatch $a, PiiMatch $b): int => $a->start <=> $b->start);

        return array_values($accepted);
    }
}
