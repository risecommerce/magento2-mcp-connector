<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpInventoryTools\Model;

use Magento\Framework\Exception\AggregateExceptionInterface;
use Throwable;

/**
 * Flattens an MSI service exception into something the caller can act on.
 *
 * MSI validators throw `ValidationException`, whose own message is the constant
 * "Validation Failed" — the reasons live in `getErrors()`. Reporting only the
 * message tells the caller nothing about what to change.
 */
class ServiceErrorMessage
{
    /**
     * @param Throwable $e
     * @return string
     */
    public function from(Throwable $e): string
    {
        $message = trim($e->getMessage());

        if (!$e instanceof AggregateExceptionInterface) {
            return $message;
        }

        $reasons = [];
        foreach ($e->getErrors() as $error) {
            $reason = trim($error->getMessage());
            if ($reason !== '' && $reason !== $message) {
                $reasons[$reason] = $reason;
            }
        }

        if ($reasons === []) {
            return $message;
        }

        return $message . ': ' . implode('; ', $reasons);
    }
}
