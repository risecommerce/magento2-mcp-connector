<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Api\Data;

/**
 * Declared input on a {@see \Risecommerce\Mcp\Api\PromptInterface}. MCP only
 * defines string-typed prompt arguments — that's all this value object
 * carries. The optional flag makes a missing value substitute to the
 * empty string rather than rejecting the request.
 */
class PromptArgument
{
    /**
     * @param string $name Snake-case identifier; matches the `{{name}}`
     *                     placeholder in prompt message bodies.
     * @param string $description Plain-English helper text shown in client UIs.
     * @param bool $required Required arguments missing from the request raise
     *                       `INVALID_PARAMS`; optional ones substitute empty.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly bool $required = false
    ) {
    }
}
