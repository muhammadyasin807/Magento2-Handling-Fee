<?php

declare(strict_types=1);

namespace Doit\HandlingFee\Block\Adminhtml\Creditmemo;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;

class HandlingFee extends Template
{
    public function initTotals(): self
    {
        $this->getParentBlock()->addTotalBefore(
            new DataObject([
                'code' => 'doit_handling_fee_input',
                'block_name' => $this->getNameInLayout(),
            ]),
            'grand_total'
        );

        return $this;
    }

    public function getRefundAmount(): float
    {
        return (float) $this->getParentBlock()
            ->getSource()
            ->getData('doit_handling_fee_amount');
    }

    public function getCurrencyCode(): string
    {
        return (string) $this->getParentBlock()
            ->getOrder()
            ->getOrderCurrencyCode();
    }
}
