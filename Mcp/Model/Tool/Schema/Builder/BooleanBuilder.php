<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\Tool\Schema\Builder;

/**
 * Fluent builder for a `type: boolean` property.
 */
class BooleanBuilder extends PropertyBuilder
{
    /**
     * @inheritDoc
     */
    public function toSchemaArray(): array
    {
        return $this->withDescription(['type' => 'boolean']);
    }
}
