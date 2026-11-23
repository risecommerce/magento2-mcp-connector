<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor;

final class RedactionResult
{
    /**
     * @param string $text
     * @param Replacement[] $replacements
     */
    public function __construct(
        private readonly string $text,
        private readonly array $replacements,
    ) {
    }

    /**
     * @return string
     */
    public function text(): string
    {
        return $this->text;
    }

    /**
     * @return Replacement[]
     */
    public function replacements(): array
    {
        return $this->replacements;
    }
}
