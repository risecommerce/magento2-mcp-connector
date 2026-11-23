<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector;

use Risecommerce\PiiRedactor\Exception\PiiRedactorException;
use Risecommerce\PiiRedactor\PiiMatch;
use Risecommerce\PiiRedactor\Validator\ValidatorInterface;

/**
 * Regex-driven detector with tri-state checksum validation; fully
 * configurable without subclassing.
 */
final class PatternDetector implements ContextAwareDetectorInterface
{
    /**
     * @param string $name
     * @param string $entityType
     * @param Pattern[] $patterns
     * @param ValidatorInterface|null $validator
     * @param string[] $contextWords
     * @param array<string, string> $sanitizePairs strtr() map applied before validation
     */
    public function __construct(
        private readonly string $name,
        private readonly string $entityType,
        private readonly array $patterns,
        private readonly ?ValidatorInterface $validator = null,
        private readonly array $contextWords = [],
        private readonly array $sanitizePairs = [],
    ) {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getSupportedEntityTypes(): array
    {
        return [$this->entityType];
    }

    /**
     * @return string[]
     */
    public function getContextWords(): array
    {
        return $this->contextWords;
    }

    /**
     * @param string $text
     * @return PiiMatch[]
     * @throws PiiRedactorException
     */
    public function detect(string $text): array
    {
        $matches = [];
        foreach ($this->patterns as $pattern) {
            if (!$pattern->mayMatch($text)) {
                continue;
            }
            if (preg_match_all($pattern->regex, $text, $found, PREG_OFFSET_CAPTURE) === false) {
                throw new PiiRedactorException(
                    sprintf('Pattern "%s" of detector "%s" failed: %s', $pattern->name, $this->name, preg_last_error_msg())
                );
            }

            foreach ($found[0] as [$matched, $offset]) {
                $verdict = $this->validator?->validate(strtr($matched, $this->sanitizePairs));
                if ($verdict === false) {
                    continue;
                }

                $matches[] = new PiiMatch(
                    $this->entityType,
                    $offset,
                    $offset + strlen($matched),
                    $verdict === true ? 1.0 : $pattern->baseScore,
                    $this->name,
                    sprintf('pattern "%s"%s', $pattern->name, $verdict === true ? ', checksum valid' : ''),
                );
            }
        }

        return $matches;
    }
}
