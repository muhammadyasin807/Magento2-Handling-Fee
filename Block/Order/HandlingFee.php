<?php

declare(strict_types=1);

namespace Doit\HandlingFee\Block\Order;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;

class HandlingFee extends Template
{
    public function initTotals(): self
    {
        $parent = $this->getParentBlock();
        $source = $parent->getSource();

        $fee = (float) $source->getData(
            'doit_handling_fee_amount'
        );

        if ($fee <= 0.0) {
            return $this;
        }

        $total = new DataObject([
            'code' => 'doit_handling_fee',
            'label' => __('Handling Fee'),
            'value' => $fee,
            'base_value' => (float) $source->getData(
                'base_doit_handling_fee_amount'
            ),
        ]);

        $parent->addTotalBefore($total, 'grand_total');

        return $this;
    }
}
