<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpTaxTools\Model\FieldResolver\TaxClass;

use Risecommerce\McpTaxTools\Api\TaxClassFieldResolverInterface;
use Magento\Tax\Api\Data\TaxClassInterface;

class IdentityResolver implements TaxClassFieldResolverInterface
{
    public const KEY = 'identity';

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return self::KEY;
    }

    /**
     * @inheritDoc
     */
    public function getSortOrder(): int
    {
        return 10;
    }

    /**
     * @inheritDoc
     */
    public function resolve(TaxClassInterface $taxClass, array $args): array
    {
        unset($args);
        return [
            'id' => $taxClass->getClassId() !== null ? (int) $taxClass->getClassId() : null,
            'name' => (string) $taxClass->getClassName(),
            'type' => (string) $taxClass->getClassType(),
        ];
    }
}
