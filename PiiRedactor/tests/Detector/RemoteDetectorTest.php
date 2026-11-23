<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests\Detector;

use Risecommerce\PiiRedactor\Detector\RemoteDetector;
use Risecommerce\PiiRedactor\Exception\RemoteDetectionException;
use Risecommerce\PiiRedactor\PiiMatch;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RemoteDetectorTest extends TestCase
{
    /**
     * @param int $chunkSize
     * @param bool $failOpen
     * @param callable(string): array<PiiMatch> $handler
     * @return RemoteDetector
     */
    private function fake(int $chunkSize, bool $failOpen, callable $handler): RemoteDetector
    {
        return new class($chunkSize, $failOpen, $handler) extends RemoteDetector {
            /** @var callable(string): array<PiiMatch> */
            private $handler;

            /**
             * @param callable(string): array<PiiMatch> $handler
             */
            public function __construct(int $chunkSize, bool $failOpen, callable $handler)
            {
                parent::__construct($chunkSize, $failOpen);
                $this->handler = $handler;
            }

            public function getName(): string
            {
                return 'fake-ner';
            }

            /** @return string[] */
            public function getSupportedEntityTypes(): array
            {
                return ['PERSON'];
            }

            /** @return PiiMatch[] */
            protected function detectChunk(string $chunk): array
            {
                return ($this->handler)($chunk);
            }
        };
    }

    public function testSingleChunkPassesThrough(): void
    {
        $detector = $this->fake(1000, true, static fn (string $chunk): array => [
            new PiiMatch('PERSON', 0, 4, 0.85, 'fake-ner'),
        ]);

        $matches = $detector->detect('John was here');

        self::assertCount(1, $matches);
        self::assertSame(0, $matches[0]->start);
    }

    public function testChunkOffsetsAreShiftedToGlobal(): void
    {
        // Chunk size 20, text 38 bytes: 'John' appears in the second chunk
        $text = str_repeat('a', 19) . ' John and more text';
        $detector = $this->fake(20, true, static function (string $chunk): array {
            $pos = strpos($chunk, 'John');

            return $pos === false ? [] : [new PiiMatch('PERSON', $pos, $pos + 4, 0.85, 'fake-ner')];
        });

        $matches = $detector->detect($text);

        self::assertCount(1, $matches);
        self::assertSame('John', substr($text, $matches[0]->start, $matches[0]->length()));
    }

    public function testChunksSplitAtWhitespaceWhenPossible(): void
    {
        $seen = [];
        $detector = $this->fake(20, true, static function (string $chunk) use (&$seen): array {
            $seen[] = $chunk;

            return [];
        });

        $detector->detect('word1 word2 word3 word4 word5');

        foreach (array_slice($seen, 0, -1) as $chunk) {
            self::assertStringEndsWith(' ', $chunk);
        }
        self::assertSame('word1 word2 word3 word4 word5', implode('', $seen));
    }

    public function testFailOpenReturnsPartialResults(): void
    {
        $calls = 0;
        $detector = $this->fake(20, true, static function (string $chunk) use (&$calls): array {
            $calls++;
            if ($calls > 1) {
                throw new RuntimeException('remote down');
            }

            return [new PiiMatch('PERSON', 0, 4, 0.85, 'fake-ner')];
        });

        $matches = $detector->detect(str_repeat('x', 50));

        self::assertCount(1, $matches);
    }

    public function testFailClosedThrows(): void
    {
        $detector = $this->fake(1000, false, static function (): array {
            throw new RuntimeException('remote down');
        });

        $this->expectException(RemoteDetectionException::class);
        $detector->detect('anything');
    }
}
