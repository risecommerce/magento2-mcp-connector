<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Exception;

use Throwable;

final class DetectionException extends PiiRedactorException
{
    /**
     * @param string $detectorName
     * @param Throwable|null $previous
     */
    public function __construct(string $detectorName, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Detector "%s" failed during analysis.', $detectorName), 0, $previous);
    }
}
