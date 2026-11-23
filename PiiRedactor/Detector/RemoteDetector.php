<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector;

use Risecommerce\PiiRedactor\Exception\RemoteDetectionException;
use Risecommerce\PiiRedactor\PiiMatch;
use Throwable;

/**
 * Base for detectors that call remote services (NER providers). Handles
 * chunking to provider size limits and fail-open/fail-closed behavior.
 */
abstract class RemoteDetector implements DetectorInterface
{
    public function __construct(
        private readonly int $chunkSize = 50_000,
        private readonly bool $failOpen = true,
    ) {
    }

    /**
     * @param string $chunk
     * @return PiiMatch[] Matches with offsets relative to the chunk.
     * @throws Throwable On transport or provider errors.
     */
    abstract protected function detectChunk(string $chunk): array;

    /**
     * @param string $text
     * @return PiiMatch[]
     * @throws RemoteDetectionException Only when configured fail-closed.
     */
    final public function detect(string $text): array
    {
        $matches = [];
        try {
            foreach ($this->chunk($text) as $offset => $chunk) {
                foreach ($this->detectChunk($chunk) as $match) {
                    $matches[] = $match->withOffset($offset);
                }
            }
        } catch (Throwable $e) {
            if ($this->failOpen) {
                return $matches;
            }
            throw new RemoteDetectionException($this->getName(), $e);
        }

        return $matches;
    }

    /**
     * @param string $text
     * @return array<int, string> Chunk byte offset => chunk. Prefers splitting
     *                            at whitespace so entities are not cut in half.
     *                            Hard cuts (no whitespace found) may split
     *                            multibyte characters; implementors should handle
     *                            invalid UTF-8 defensively.
     */
    private function chunk(string $text): array
    {
        if (strlen($text) <= $this->chunkSize) {
            return [0 => $text];
        }

        $chunks = [];
        $offset = 0;
        $total = strlen($text);
        while ($offset < $total) {
            $slice = substr($text, $offset, $this->chunkSize);
            if ($offset + strlen($slice) < $total) {
                $lastSpace = strrpos($slice, ' ');
                if ($lastSpace !== false && $lastSpace >= intdiv($this->chunkSize, 2)) {
                    $slice = substr($slice, 0, $lastSpace + 1);
                }
            }
            $chunks[$offset] = $slice;
            $offset += strlen($slice);
        }

        return $chunks;
    }
}
