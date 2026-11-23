<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Model;

use Magento\Framework\Exception\LocalizedException;

/**
 * Argument validation shared by the bulk inventory tools. These operations
 * touch thousands of rows with no per-item reporting, so the lists are bounded
 * and validated before anything is written.
 */
class BulkArguments
{
    public const MAX_SKUS = 1000;
    public const MAX_SOURCES = 50;

    /**
     * @param array $arguments
     * @phpstan-param array<string, mixed> $arguments
     * @param string $key
     * @return string[]
     * @throws LocalizedException
     */
    public function skus(array $arguments, string $key = 'skus'): array
    {
        return $this->stringList($arguments, $key, self::MAX_SKUS);
    }

    /**
     * @param array $arguments
     * @phpstan-param array<string, mixed> $arguments
     * @param string $key
     * @return string[]
     * @throws LocalizedException
     */
    public function sourceCodes(array $arguments, string $key = 'source_codes'): array
    {
        return $this->stringList($arguments, $key, self::MAX_SOURCES);
    }

    /**
     * @param array $arguments
     * @phpstan-param array<string, mixed> $arguments
     * @param string $key
     * @return string
     * @throws LocalizedException
     */
    public function requiredString(array $arguments, string $key): string
    {
        $value = $arguments[$key] ?? null;
        if (!is_scalar($value) || (string) $value === '') {
            throw new LocalizedException(__('"%1" is required.', $key));
        }

        return (string) $value;
    }

    /**
     * @param array $arguments
     * @phpstan-param array<string, mixed> $arguments
     * @param string $key
     * @param int $max
     * @return string[]
     * @throws LocalizedException
     */
    private function stringList(array $arguments, string $key, int $max): array
    {
        $values = $arguments[$key] ?? null;
        if (!is_array($values) || $values === []) {
            throw new LocalizedException(__('"%1" must be a non-empty array.', $key));
        }

        $resolved = [];
        foreach ($values as $value) {
            if (!is_scalar($value) || (string) $value === '') {
                throw new LocalizedException(__('"%1" entries must be non-empty strings.', $key));
            }
            $resolved[(string) $value] = (string) $value;
        }

        if (count($resolved) > $max) {
            throw new LocalizedException(__(
                '"%1" accepts at most %2 entries; %3 given.',
                $key,
                $max,
                count($resolved)
            ));
        }

        return array_values($resolved);
    }
}
