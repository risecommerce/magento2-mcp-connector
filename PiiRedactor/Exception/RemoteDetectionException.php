<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Exception;

use Throwable;

final class RemoteDetectionException extends PiiRedactorException
{
    /**
     * @param string $detectorName
     * @param Throwable|null $previous
     */
    public function __construct(string $detectorName, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Remote detector "%s" failed and is configured fail-closed.', $detectorName), 0, $previous);
    }
}
