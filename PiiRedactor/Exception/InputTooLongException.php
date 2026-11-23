<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Exception;

final class InputTooLongException extends PiiRedactorException
{
    /**
     * @param int $length
     * @param int $maxLength
     */
    public function __construct(int $length, int $maxLength)
    {
        parent::__construct(sprintf('Input of %d bytes exceeds the configured maximum of %d bytes.', $length, $maxLength));
    }
}
