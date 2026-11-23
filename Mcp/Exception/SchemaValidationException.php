<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Exception;

use RuntimeException;

/**
 * Tool-call arguments failed JSON Schema validation; carries the formatted opis error tree.
 */
class SchemaValidationException extends RuntimeException
{
    /**
     * @param string $message
     * @param array<int|string, mixed> $errors
     */
    public function __construct(
        string $message,
        private readonly array $errors = []
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
