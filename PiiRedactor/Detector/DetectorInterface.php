<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector;

use Risecommerce\PiiRedactor\PiiMatch;

interface DetectorInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string[]
     */
    public function getSupportedEntityTypes(): array;

    /**
     * @param string $text
     * @return PiiMatch[]
     */
    public function detect(string $text): array;
}
