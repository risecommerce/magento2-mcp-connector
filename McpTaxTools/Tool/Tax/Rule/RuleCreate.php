<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpTaxTools\Tool\Tax\Rule;

use Risecommerce\Mcp\Api\ToolInterface;
use Risecommerce\Mcp\Api\ToolResultInterface;
use Risecommerce\Mcp\Api\UnderlyingAclAwareInterface;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\ArrayBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\BooleanBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\IntegerBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Builder\StringBuilder;
use Risecommerce\Mcp\Model\Tool\Schema\Schema;
use Risecommerce\Mcp\Model\Tool\ToolResult;
use Risecommerce\Mcp\Model\Tool\WriteMode;
use Magento\Framework\Exception\LocalizedException;
use Magento\Tax\Api\Data\TaxRuleInterfaceFactory;
use Magento\Tax\Api\TaxRuleRepositoryInterface;

class RuleCreate implements ToolInterface, UnderlyingAclAwareInterface
{
    public const TOOL_NAME = 'tax.rule.create';
    public const ACL_RESOURCE = 'Risecommerce_McpTaxTools::tool_tax_rule_create';

    /**
     * @param TaxRuleRepositoryInterface $ruleRepository
     * @param TaxRuleInterfaceFactory $ruleFactory
     * @param RuleFieldApplier $applier
     */
    public function __construct(
        private readonly TaxRuleRepositoryInterface $ruleRepository,
        private readonly TaxRuleInterfaceFactory $ruleFactory,
        private readonly RuleFieldApplier $applier
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
        return 'Create Tax Rule';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Create a tax rule linking tax rates to customer and product tax classes. A rate only '
            . 'starts being charged once a rule references it.';
    }

    /**
     * @inheritDoc
     */
    public function getInputSchema(): array
    {
        return Schema::object()
            ->string('code', fn (StringBuilder $s) => $s
                ->minLength(1)
                ->description('Unique rule code.')
                ->required())
            ->array('tax_rate_ids', fn (ArrayBuilder $a) => $a->ofIntegers()
                ->description('Ids of the tax rates this rule applies.')
                ->required())
            ->array('customer_tax_class_ids', fn (ArrayBuilder $a) => $a->ofIntegers()
                ->description('Customer tax class ids — see tax.class.list.')
                ->required())
            ->array('product_tax_class_ids', fn (ArrayBuilder $a) => $a->ofIntegers()
                ->description('Product tax class ids — see tax.class.list.')
                ->required())
            ->integer('priority', fn (IntegerBuilder $i) => $i
                ->minimum(0)
                ->description('Rules with the same priority are summed; higher priorities compound.'))
            ->integer('position', fn (IntegerBuilder $i) => $i
                ->minimum(0)
                ->description('Sort position in the admin grid.'))
            ->boolean('calculate_subtotal', fn (BooleanBuilder $b) => $b
                ->description('Calculate off the subtotal rather than per line.'))
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
        return 'Magento_Tax::manage_tax';
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
        return false;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $arguments): ToolResultInterface
    {
        $rule = $this->applier->apply($this->ruleFactory->create(), $arguments);
        $saved = $this->ruleRepository->save($rule);

        $payload = [
            'id' => $saved->getId() !== null ? (int) $saved->getId() : null,
            'code' => (string) $saved->getCode(),
            'tax_rate_ids' => $saved->getTaxRateIds(),
            'customer_tax_class_ids' => $saved->getCustomerTaxClassIds(),
            'product_tax_class_ids' => $saved->getProductTaxClassIds(),
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new LocalizedException(__('Failed to encode tax rule as JSON.'));
        }

        return new ToolResult(
            content: [['type' => 'text', 'text' => $json]],
            auditSummary: [
                'rule_id' => $saved->getId() !== null ? (int) $saved->getId() : null,
                'code' => (string) $saved->getCode(),
            ]
        );
    }
}
