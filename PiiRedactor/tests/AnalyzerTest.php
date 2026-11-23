<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Tests;

use Risecommerce\PiiRedactor\Analyzer;
use Risecommerce\PiiRedactor\AnalyzerOptions;
use Risecommerce\PiiRedactor\Detector\DetectorInterface;
use Risecommerce\PiiRedactor\Detector\DetectorRegistry;
use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\Exception\DetectionException;
use Risecommerce\PiiRedactor\Exception\InputTooLongException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AnalyzerTest extends TestCase
{
    private function registryWith(DetectorInterface ...$detectors): DetectorRegistry
    {
        $registry = new DetectorRegistry();
        foreach ($detectors as $detector) {
            $registry->register($detector);
        }

        return $registry;
    }

    private function emailDetector(): PatternDetector
    {
        return new PatternDetector('email', 'EMAIL', [
            new Pattern('email', '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/u', 0.9),
        ]);
    }

    private function weakPhoneDetector(): PatternDetector
    {
        return new PatternDetector('phone', 'PHONE', [
            new Pattern('national', '/(?<!\d)\d{8}(?!\d)/u', 0.3),
        ], null, ['phone', 'tel']);
    }

    private function throwingDetector(): DetectorInterface
    {
        return new class implements DetectorInterface {
            public function getName(): string
            {
                return 'broken';
            }

            /** @return string[] */
            public function getSupportedEntityTypes(): array
            {
                return ['EMAIL'];
            }

            /** @return \Risecommerce\PiiRedactor\PiiMatch[] */
            public function detect(string $text): array
            {
                throw new RuntimeException('boom');
            }
        };
    }

    public function testFindsAndResolvesMatches(): void
    {
        $analyzer = new Analyzer($this->registryWith($this->emailDetector()));

        $result = $analyzer->analyze('Contact john@example.com please');

        self::assertSame(1, $result->count());
        self::assertSame('EMAIL', $result->matches()[0]->entityType);
    }

    public function testThresholdFiltersWeakMatchesWithoutContext(): void
    {
        $analyzer = new Analyzer($this->registryWith($this->weakPhoneDetector()));

        self::assertTrue($analyzer->analyze('Order ref 26123456')->isEmpty());
        self::assertSame(1, $analyzer->analyze('My phone 26123456')->count());
    }

    public function testNullEnhancerDisablesContextBoosting(): void
    {
        $analyzer = new Analyzer(
            $this->registryWith($this->weakPhoneDetector()),
            new \Risecommerce\PiiRedactor\NullContextEnhancer(),
        );

        // Without boosting the weak national number never reaches the 0.5 threshold.
        self::assertTrue($analyzer->analyze('My phone 26123456')->isEmpty());
    }

    public function testEntityTypeFilter(): void
    {
        $analyzer = new Analyzer($this->registryWith($this->emailDetector(), $this->weakPhoneDetector()));

        $result = $analyzer->analyze(
            'phone 26123456 and john@example.com',
            new AnalyzerOptions(entityTypes: ['EMAIL']),
        );

        self::assertSame(1, $result->count());
        self::assertSame('EMAIL', $result->matches()[0]->entityType);
    }

    public function testAllowListExemptsLiteralValues(): void
    {
        $analyzer = new Analyzer($this->registryWith($this->emailDetector()));

        $result = $analyzer->analyze(
            'Contact support@risecommerce.com or john@example.com',
            new AnalyzerOptions(allowList: ['support@risecommerce.com']),
        );

        self::assertSame(1, $result->count());
        self::assertSame('EMAIL', $result->matches()[0]->entityType);
    }

    public function testFailingDetectorIsReportedNotThrown(): void
    {
        $analyzer = new Analyzer($this->registryWith($this->throwingDetector(), $this->emailDetector()));

        $result = $analyzer->analyze('john@example.com');

        self::assertSame(1, $result->count());
        self::assertArrayHasKey('broken', $result->failures());
    }

    public function testStrictModeRethrows(): void
    {
        $analyzer = new Analyzer($this->registryWith($this->throwingDetector()));

        $this->expectException(DetectionException::class);
        $analyzer->analyze('anything', new AnalyzerOptions(strict: true));
    }

    public function testInputLengthGuard(): void
    {
        $analyzer = new Analyzer($this->registryWith($this->emailDetector()));

        $this->expectException(InputTooLongException::class);
        $analyzer->analyze(str_repeat('a', 101), new AnalyzerOptions(maxLength: 100));
    }

    public function testEntityTypeEnumInstancesAcceptedInFilter(): void
    {
        $analyzer = new Analyzer($this->registryWith($this->emailDetector(), $this->weakPhoneDetector()));

        $result = $analyzer->analyze(
            'phone 26123456 and john@example.com',
            new AnalyzerOptions(entityTypes: [\Risecommerce\PiiRedactor\EntityType::EMAIL]),
        );

        self::assertSame(1, $result->count());
        self::assertSame('EMAIL', $result->matches()[0]->entityType);
    }

    public function testAdHocDetectorRunsForThisCallOnly(): void
    {
        $analyzer = new Analyzer($this->registryWith());

        $result = $analyzer->analyze(
            'token ABC-123',
            new AnalyzerOptions(adHocDetectors: [
                new PatternDetector('token', 'CUSTOM_TOKEN', [new Pattern('t', '/ABC-\d{3}/u', 0.9)]),
            ]),
        );

        self::assertSame(1, $result->count());
        self::assertSame('CUSTOM_TOKEN', $result->matches()[0]->entityType);
    }
}
