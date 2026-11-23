<?php
/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Risecommerce\McpOrderTools\Model\FieldResolver\Order;

use Risecommerce\McpOrderTools\Api\OrderFieldResolverInterface;
use Risecommerce\McpOrderTools\Model\Payment\AdditionalInformationFilter;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Payment-method slice. `additional_information` is filtered through an allowlist
 * ({@see AdditionalInformationFilter}) before returning — the base-module PII
 * redactor only sanitizes the audit row, never this response payload.
 */
class PaymentResolver implements OrderFieldResolverInterface
{
    public const KEY = 'payment';

    /**
     * @param AdditionalInformationFilter $additionalInformationFilter
     */
    public function __construct(
        private readonly AdditionalInformationFilter $additionalInformationFilter
    ) {
    }

    /** @inheritDoc */
    public function getKey(): string
    {
        return self::KEY;
    }

    /** @inheritDoc */
    public function getSortOrder(): int
    {
        return 70;
    }

    /** @inheritDoc */
    public function resolve(OrderInterface $order, array $args): array
    {
        $payment = $order->getPayment();
        if (!$payment instanceof OrderPaymentInterface) {
            return [];
        }
        return [
            'method' => (string) $payment->getMethod(),
            'cc_type' => $payment->getCcType(),
            'cc_last4' => $payment->getCcLast4(),
            'additional_information' => $this->additionalInformationFilter->filter(
                $payment->getAdditionalInformation()
            ),
        ];
    }
}
