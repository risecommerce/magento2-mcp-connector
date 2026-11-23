<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\Tool\Schema;

use Risecommerce\Mcp\Model\Tool\Schema\Builder\ObjectBuilder;

/**
 * Reusable schema block applied via {@see ObjectBuilder::with()}.
 */
interface SchemaContribution
{
    /**
     * Implementations MUST NOT replace properties that already exist.
     *
     * @param ObjectBuilder $object
     * @return void
     */
    public function applyTo(ObjectBuilder $object): void;
}
