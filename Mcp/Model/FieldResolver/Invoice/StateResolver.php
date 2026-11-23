<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\Mcp\Model\FieldResolver\Invoice;

use Risecommerce\Mcp\Api\InvoiceFieldResolverInterface;
use Risecommerce\Mcp\Model\FieldResolver\CastsScalars;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\Order\Invoice;

/**
 * Invoice lifecycle slice — state as both its numeric code and human label.
 */
class StateResolver implements InvoiceFieldResolverInterface
{
    use CastsScalars;

    public const KEY = 'state';

    /**
     * @var array<int, string> Magento Invoice state constants by code.
     */
    private const LABELS = [
        Invoice::STATE_OPEN => 'open',
        Invoice::STATE_PAID => 'paid',
        Invoice::STATE_CANCELED => 'canceled',
    ];

    /** @inheritDoc */
    public function getKey(): string
    {
        return self::KEY;
    }

    /** @inheritDoc */
    public function getSortOrder(): int
    {
        return 20;
    }

    /** @inheritDoc */
    public function resolve(InvoiceInterface $invoice, array $args): array
    {
        $code = $invoice->getState();
        $label = (is_int($code) && isset(self::LABELS[$code])) ? self::LABELS[$code] : 'unknown';
        return [
            'state_code' => $this->asInt($code),
            'state_label' => $label,
        ];
    }
}
