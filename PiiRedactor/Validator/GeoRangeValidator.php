<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Validator;

/**
 * Range check for "lat,long" pairs: out-of-range discards, in-range proves
 * nothing (never returns true, so no score-1.0 promotion).
 */
final class GeoRangeValidator implements ValidatorInterface
{
    /**
     * @param string $candidate
     * @return bool|null
     */
    public function validate(string $candidate): ?bool
    {
        $parts = explode(',', $candidate, 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$lat, $long] = array_map(static fn (string $p): float => (float)trim($p), $parts);

        return (abs($lat) > 90.0 || abs($long) > 180.0) ? false : null;
    }
}
