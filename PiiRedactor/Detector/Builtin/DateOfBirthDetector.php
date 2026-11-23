<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\EntityType;
use Risecommerce\PiiRedactor\Validator\DateRangeValidator;

/**
 * Built-in DATE_OF_BIRTH detector. Dates are ambiguous, so the base score is
 * very low and depends on nearby context words ("born", "DOB", ...) to fire.
 */
final class DateOfBirthDetector
{
    /** Day/month/year or month/day/year with a 19xx/20xx year. */
    private const DMY_OR_MDY = '/\b\d{1,2}[\/.\-]\d{1,2}[\/.\-](?:19|20)\d{2}\b/u';

    /** ISO-ish year-first ordering. */
    private const YMD = '/\b(?:19|20)\d{2}[\/.\-]\d{1,2}[\/.\-]\d{1,2}\b/u';

    /**
     * @param string[] $contextWords
     * @return PatternDetector
     */
    public static function create(array $contextWords = []): PatternDetector
    {
        return new PatternDetector('date-of-birth', EntityType::DATE_OF_BIRTH->value, [
            new Pattern('dmy-or-mdy', self::DMY_OR_MDY, 0.2),
            new Pattern('ymd', self::YMD, 0.2),
        ], new DateRangeValidator(), $contextWords);
    }
}
