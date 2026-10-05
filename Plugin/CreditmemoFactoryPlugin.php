<?php

declare(strict_types=1);

namespace Doit\HandlingFee\Plugin;

use Doit\HandlingFee\Model\Refund\ApplyToOrderCreditmemo;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;

class CreditmemoFactoryPlugin
{
    public function __construct(
        private readonly ApplyToOrderCreditmemo $feeRefund
    ) {
    }

    public function afterCreateByOrder(
        CreditmemoFactory $subject,
        Creditmemo $result,
        Order $order,
        array $data = []
    ): Creditmemo {
        $this->feeRefund->apply(
            $result,
            $data['doit_handling_fee_amount'] ?? 0
        );

        return $result;
    }

    public function afterCreateByInvoice(
        CreditmemoFactory $subject,
        Creditmemo $result,
        Invoice $invoice,
        array $data = []
    ): Creditmemo {
        $this->feeRefund->apply(
            $result,
            $data['doit_handling_fee_amount'] ?? 0
        );

        return $result;
    }
}
