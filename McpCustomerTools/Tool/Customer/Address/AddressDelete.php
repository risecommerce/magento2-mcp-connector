<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpCustomerTools\Tool\Customer\Address;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Api\UnderlyingAclAwareInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Risecommerce\McpCustomerTools\Model\EntityFinder;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;

class AddressDelete implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'customer.address.delete';
    public const ACL_RESOURCE = 'Risecommerce_McpCustomerTools::tool_customer_address_delete';

    /**
     * @param EntityFinder $entityFinder
     * @param AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        private readonly EntityFinder $entityFinder,
        private readonly AddressRepositoryInterface $addressRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::TOOL_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return 'Delete Customer Address';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Permanently delete a customer address by id. If the address '
            . 'was a default billing/shipping, Magento clears the pointer on '
            . 'the customer automatically.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->integer('id', fn (IntegerBuilder $i) => $i->minimum(1)->required())
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getAclResource(): string
    {
        return self::ACL_RESOURCE;
    }

    /**
     * @inheritDoc
     */
    public function getUnderlyingAclResource(): ?string
    {
        return 'Magento_Customer::manage';
    }

    /**
     * @inheritDoc
     */
    public function getWriteMode(): WriteMode
    {
        return WriteMode::WRITE;
    }

    /**
     * @inheritDoc
     */
    public function getConfirmationRequired(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $arguments): ToolResultInterface
    {
        $address = $this->entityFinder->addressFrom($arguments);
        $addressId = (int) $address->getId();
        $customerId = (int) $address->getCustomerId();

        $deleted = $this->addressRepository->delete($address);

        $payload = [
            'deleted' => (bool) $deleted,
            'id' => $addressId,
            'customer_id' => $customerId,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode delete result as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'id' => $addressId,
                'customer_id' => $customerId,
                'deleted' => (bool) $deleted,
            ]
        );
    }
}
