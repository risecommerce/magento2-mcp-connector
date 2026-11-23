<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor;

/**
 * Redaction engine: applies per-entity-type strategies to analyzed matches,
 * right-to-left so byte offsets stay valid during replacement.
 */
final class Redactor
{
    /**
     * @param string $text
     * @param AnalysisResult $analysis
     * @param RedactionConfig|null $config
     * @return RedactionResult
     */
    public function redact(string $text, AnalysisResult $analysis, ?RedactionConfig $config = null): RedactionResult
    {
        $config ??= new RedactionConfig();

        // Defensively normalize: sort by start asc (ties: longer span first), then drop overlaps.
        $matches = $analysis->matches();
        if (count($matches) > 1) {
            usort($matches, static fn (PiiMatch $a, PiiMatch $b): int => $a->start <=> $b->start ?: $b->end <=> $a->end);
            $normalized = [];
            $lastEnd = -1;
            foreach ($matches as $match) {
                if ($match->start < $lastEnd) {
                    continue;
                }
                $normalized[] = $match;
                $lastEnd = $match->end;
            }
            $matches = $normalized;
        }

        $redacted = $text;
        /** @var array<int, string> $replacementTexts */
        $replacementTexts = [];
        // $matches is now sorted, non-overlapping; start offsets are unique.
        foreach (array_reverse($matches) as $match) {
            $replacementText = $config->strategyFor($match->entityType)->redact(
                $match,
                substr($text, $match->start, $match->length()),
            );
            $redacted = substr_replace($redacted, $replacementText, $match->start, $match->length());
            $replacementTexts[$match->start] = $replacementText;
        }

        $replacements = [];
        $delta = 0;
        foreach ($matches as $match) {
            $replacementText = $replacementTexts[$match->start];
            $newStart = $match->start + $delta;
            $replacements[] = new Replacement(
                $match->entityType,
                $match->start,
                $match->end,
                $replacementText,
                $newStart,
                $newStart + strlen($replacementText),
            );
            $delta += strlen($replacementText) - $match->length();
        }

        return new RedactionResult($redacted, $replacements);
    }
}
