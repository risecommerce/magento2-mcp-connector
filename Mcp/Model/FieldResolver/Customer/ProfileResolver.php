<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\Customer;

use Risecommerce\Mcp\Api\CustomerFieldResolverInterface;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * dob / gender / taxvat — all PII. Opt out via the tool's `exclude` arg.
 */
class ProfileResolver implements CustomerFieldResolverInterface
{
    public const KEY = 'profile';

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
        return 30;
    }

    /**
     * @inheritDoc
     */
    public function resolve(CustomerInterface $customer, array $args): array
    {
        unset($args);
        return [
            'dob' => $customer->getDob(),
            'gender' => $customer->getGender(),
            'taxvat' => $customer->getTaxvat(),
        ];
    }
}
