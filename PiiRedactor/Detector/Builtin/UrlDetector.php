<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Builtin;

use Risecommerce\PiiRedactor\Detector\Pattern;
use Risecommerce\PiiRedactor\Detector\PatternDetector;
use Risecommerce\PiiRedactor\EntityType;

/**
 * Built-in URL detector. Scheme-or-www heuristic; no context words needed.
 */
final class UrlDetector
{
    /** http(s):// or www. prefix; bounded run stops at whitespace/quotes/parens. */
    private const PATTERN = '~(?:https?://|www\.)[^\s<>"\'\)]{1,2048}~u';

    /**
     * @param string[] $contextWords
     * @return PatternDetector
     */
    public static function create(array $contextWords = []): PatternDetector
    {
        return new PatternDetector('url', EntityType::URL->value, [
            new Pattern('url', self::PATTERN, 0.6, ['://', 'www.']),
        ], null, $contextWords);
    }
}
