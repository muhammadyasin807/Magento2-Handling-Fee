<?php

declare(strict_types=1);

namespace Doit\HandlingFee\Model\Quote\Address\Total;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Doit\HandlingFee\Model\Config;

class HandlingFee extends AbstractTotal
{
    private const CODE = 'doit_handling_fee';

    public function __construct(
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly Config $config

    ) {
        $this->setCode(self::CODE);
    }

    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

// Reset this address's contribution.
        $total->setTotalAmount(self::CODE, 0.0);
        $total->setBaseTotalAmount(self::CODE, 0.0);

// These cart types do not receive our fee.
        if ($quote->getIsMultiShipping() || $quote->isVirtual()) {
            $quote->setData(self::CODE . '_amount', 0.0);
            $quote->setData('base_' . self::CODE . '_amount', 0.0);

            return $this;
        }

        $address = $shippingAssignment->getShipping()->getAddress();

// Billing collection must not erase the shipping fee.
        if ($address->getAddressType() !== 'shipping') {
            return $this;
        }

// Only the shipping address manages these quote-level values.
        $quote->setData(self::CODE . '_amount', 0.0);
        $quote->setData('base_' . self::CODE . '_amount', 0.0);

        $storeId = (int) $quote->getStoreId();

        if (!$this->config->isEnabled($storeId)) {
            return $this;
        }

        $hasPhysicalItem = false;

        foreach ($shippingAssignment->getItems() as $item) {
            if ($item->isDeleted() || (float) $item->getQty() <= 0) {
                continue;
            }

            if (!$item->getIsVirtual()) {
                $hasPhysicalItem = true;
                break;
            }
        }

        if (!$hasPhysicalItem) {
            return $this;
        }

        $baseFee = $this->config->getAmount($storeId);

        if ($baseFee === null || $baseFee === 0.0) {
            return $this;
        }

        $fee = $this->priceCurrency->convertAndRound(
            $baseFee,
            $quote->getStore(),
            $quote->getQuoteCurrencyCode()
        );

        // Contribute to Grand's aggregation; do not increment grand_total here.
        $total->setTotalAmount(self::CODE, $fee);
        $total->setBaseTotalAmount(self::CODE, $baseFee);

        // Quote::getTotals() supplies quote data to TotalsReader.
        $quote->setData(self::CODE . '_amount', $fee);
        $quote->setData('base_' . self::CODE . '_amount', $baseFee);

        return $this;
    }

    public function fetch(Quote $quote, Total $total)
    {
        $fee = (float) $quote->getData(self::CODE . '_amount');


        if ($fee <= 0.0) {
            return null;
        }

        return [
            'code' => self::CODE,
            'title' => __('Handling Fee'),
            'value' => $fee,
        ];
    }
}
