<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor;

use Risecommerce\PiiRedactor\Detector\ContextAwareDetectorInterface;
use Risecommerce\PiiRedactor\Detector\DetectorRegistry;
use Risecommerce\PiiRedactor\Exception\DetectionException;
use Risecommerce\PiiRedactor\Exception\InputTooLongException;
use Throwable;

/**
 * Detection engine: runs registered detectors, enhances scores with context,
 * filters by options and resolves overlaps.
 */
final class Analyzer
{
    private readonly ContextEnhancerInterface $enhancer;
    private readonly OverlapResolver $resolver;

    // Defaults are null, not `new …()`: Magento's DI compiler var_exports default values into
    // generated/metadata/global.php, and an object default becomes a fatal `::__set_state()` call.
    public function __construct(
        private readonly DetectorRegistry $registry,
        ?ContextEnhancerInterface $enhancer = null,
        ?OverlapResolver $resolver = null,
    ) {
        $this->enhancer = $enhancer ?? new ContextEnhancer();
        $this->resolver = $resolver ?? new OverlapResolver();
    }

    /**
     * @param string $text
     * @param AnalyzerOptions|null $options
     * @return AnalysisResult
     * @throws InputTooLongException
     * @throws DetectionException Only in strict mode.
     */
    public function analyze(string $text, ?AnalyzerOptions $options = null): AnalysisResult
    {
        $options ??= new AnalyzerOptions();
        if (strlen($text) > $options->maxLength) {
            throw new InputTooLongException(strlen($text), $options->maxLength);
        }

        $all = [];
        $failures = [];
        foreach ([...$this->registry->all(), ...$options->adHocDetectors] as $detector) {
            if (
                $options->entityTypes !== null
                && array_intersect($detector->getSupportedEntityTypes(), $options->entityTypes) === []
            ) {
                continue;
            }

            try {
                $found = $detector->detect($text);
            } catch (Throwable $e) {
                if ($options->strict) {
                    throw new DetectionException($detector->getName(), $e);
                }
                $failures[$detector->getName()] = $e->getMessage();
                continue;
            }

            $contextWords = $detector instanceof ContextAwareDetectorInterface
                ? $detector->getContextWords()
                : [];

            foreach ($found as $match) {
                $match = $this->enhancer->enhance($text, $match, $contextWords);
                if ($match->score < $options->minScore) {
                    continue;
                }
                if ($options->entityTypes !== null && !in_array($match->entityType, $options->entityTypes, true)) {
                    continue;
                }
                if (in_array(substr($text, $match->start, $match->length()), $options->allowList, true)) {
                    continue;
                }
                $all[] = $match;
            }
        }

        return new AnalysisResult($this->resolver->resolve($all), $failures);
    }
}
