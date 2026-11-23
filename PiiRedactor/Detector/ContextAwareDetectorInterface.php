<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector;

interface ContextAwareDetectorInterface extends DetectorInterface
{
    /**
     * @return string[]
     */
    public function getContextWords(): array;
}
