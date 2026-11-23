<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor;

/**
 * One audit-trail record. Offsets are byte offsets; start/end refer to the
 * original text, newStart/newEnd to the redacted text. The original PII value
 * is deliberately not stored.
 */
final class Replacement
{
    /**
     * @param string $entityType
     * @param int $start Byte offset in the original text.
     * @param int $end Byte offset in the original text (exclusive).
     * @param string $replacement The redacted text that replaced the span.
     * @param int $newStart Byte offset in the redacted text.
     * @param int $newEnd Byte offset in the redacted text (exclusive).
     */
    public function __construct(
        public readonly string $entityType,
        public readonly int $start,
        public readonly int $end,
        public readonly string $replacement,
        public readonly int $newStart,
        public readonly int $newEnd,
    ) {
    }
}
