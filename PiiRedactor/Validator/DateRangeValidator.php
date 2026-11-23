<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Validator;

/**
 * Plausibility check for birth-date candidates. Never confirms (a date alone
 * is not proof of a DOB), only discards implausible ones.
 */
final class DateRangeValidator implements ValidatorInterface
{
    /**
     * @param int $minYear
     */
    public function __construct(
        private readonly int $minYear = 1900,
    ) {
    }

    /**
     * @param string $candidate
     * @return bool|null
     */
    public function validate(string $candidate): ?bool
    {
        if (preg_match('/(?:19|20)\d{2}/', $candidate, $matches) !== 1) {
            return false;
        }

        $year = (int)$matches[0];
        if ($year < $this->minYear || $year > (int)date('Y')) {
            return false;
        }

        return null;
    }
}
