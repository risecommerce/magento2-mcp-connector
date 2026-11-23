<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Validator;

/**
 * Tri-state checksum validation: true forces score 1.0, false discards
 * the match, null keeps the pattern's base score.
 */
interface ValidatorInterface
{
    /**
     * @param string $candidate Sanitized matched text.
     * @return bool|null
     */
    public function validate(string $candidate): ?bool;
}
