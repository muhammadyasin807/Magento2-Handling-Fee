<?php

declare(strict_types=1);

namespace Doit\HandlingFee\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

class CopyHandlingFeeToOrder implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        $order->setData(
            'doit_handling_fee_amount',
            (float) $quote->getData('doit_handling_fee_amount')
        );

        $order->setData(
            'base_doit_handling_fee_amount',
            (float) $quote->getData('base_doit_handling_fee_amount')
        );
    }
}
