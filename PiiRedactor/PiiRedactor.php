<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor;

use Risecommerce\PiiRedactor\Detector\DetectorRegistry;
use Risecommerce\PiiRedactor\Exception\DetectionException;
use Risecommerce\PiiRedactor\Exception\InputTooLongException;

/**
 * Convenience facade wiring default registry, analyzer and redactor.
 */
final class PiiRedactor
{
    private readonly Analyzer $analyzer;
    private readonly Redactor $redactor;

    public function __construct(
        ?DetectorRegistry $registry = null,
        private readonly ?RedactionConfig $config = null,
        private readonly ?AnalyzerOptions $options = null,
    ) {
        $this->analyzer = new Analyzer($registry ?? DetectorRegistry::withDefaults());
        $this->redactor = new Redactor();
    }

    /**
     * @param string $text
     * @return AnalysisResult
     * @throws InputTooLongException
     * @throws DetectionException
     */
    public function analyze(string $text): AnalysisResult
    {
        return $this->analyzer->analyze($text, $this->options);
    }

    /**
     * @param string $text
     * @return RedactionResult
     * @throws InputTooLongException
     * @throws DetectionException
     */
    public function redact(string $text): RedactionResult
    {
        return $this->redactor->redact($text, $this->analyze($text), $this->config);
    }
}
