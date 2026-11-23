<?php

declare(strict_types=1);

namespace Risecommerce\PiiRedactor\Detector\Secret;

use Risecommerce\PiiRedactor\Detector\Pattern;

/**
 * Declarative vendor-token rule: prefix + charset + length compile to a
 * Pattern whose needle pre-filter is the prefix itself.
 */
final class SecretRule
{
    public readonly string $regex;

    /**
     * @param string $id Rule id, shown in match explanations.
     * @param string $prefix Literal token prefix; doubles as the needle pre-filter.
     * @param string $charset Regex character-class body for the token tail.
     * @param int $minLength Minimum tail length (characters after the prefix).
     * @param int|null $maxLength Maximum tail length; null = unbounded.
     * @param float $baseScore
     * @param string|null $regex Explicit full pattern; skips derivation (see fromRegex()).
     */
    public function __construct(
        public readonly string $id,
        public readonly string $prefix,
        string $charset = 'A-Za-z0-9',
        int $minLength = 20,
        ?int $maxLength = null,
        public readonly float $baseScore = 0.9,
        ?string $regex = null,
    ) {
        $bounds = $maxLength === null
            ? sprintf('{%d,}', $minLength)
            : sprintf('{%d,%d}', $minLength, $maxLength);
        $this->regex = $regex
            ?? sprintf('/\b%s[%s]%s\b/u', preg_quote($prefix, '/'), $charset, $bounds);
    }

    /**
     * Rule with an explicit regex, for shapes prefix+charset can't express.
     *
     * @param string $id
     * @param string $regex Full pattern including delimiters and flags.
     * @param string $needle Literal substring guaranteed present in any match.
     * @param float $baseScore
     * @return self
     */
    public static function fromRegex(string $id, string $regex, string $needle, float $baseScore = 0.9): self
    {
        return new self($id, $needle, '', 0, 0, $baseScore, $regex);
    }

    /**
     * @return Pattern
     */
    public function toPattern(): Pattern
    {
        return new Pattern($this->id, $this->regex, $this->baseScore, [$this->prefix]);
    }
}
