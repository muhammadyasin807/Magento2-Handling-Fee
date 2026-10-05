<?php

declare(strict_types=1);

namespace Doit\HandlingFee\Model\Order\Invoice\Total;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

class HandlingFee extends AbstractTotal
{
    private const FEE = 'doit_handling_fee_amount';
    private const BASE_FEE = 'base_doit_handling_fee_amount';

    public function collect(Invoice $invoice)
    {
        // Only calculate new invoices. Leave saved invoices unchanged.
        if ($invoice->getId()) {
            return $this;
        }

        // 1. Remove our previous contribution on this invoice object.
        $previousFee = (float) $invoice->getData(self::FEE);
        $previousBaseFee = (float) $invoice->getData(self::BASE_FEE);

        $invoice->setGrandTotal(
            (float) $invoice->getGrandTotal() - $previousFee
        );

        $invoice->setBaseGrandTotal(
            (float) $invoice->getBaseGrandTotal() - $previousBaseFee
        );

        $invoice->setData(self::FEE, 0.0);
        $invoice->setData(self::BASE_FEE, 0.0);

        if ((float) $invoice->getTotalQty() <= 0) {
            return $this;
        }

        // 2. Count the fee allocated to previous saved invoices.
        $order = $invoice->getOrder();
        $invoicedFee = 0.0;
        $baseInvoicedFee = 0.0;

        foreach ($order->getInvoiceCollection() as $previousInvoice) {
            if (!$previousInvoice->getId()) {
                continue;
            }

            if ((int) $previousInvoice->getState() === Invoice::STATE_CANCELED) {
                continue;
            }

            $invoicedFee += (float) $previousInvoice->getData(self::FEE);
            $baseInvoicedFee += (float) $previousInvoice->getData(self::BASE_FEE);
        }

        // 3. Calculate the remaining fee in each currency.
        $fee = max(
            0.0,
            (float) $order->getData(self::FEE) - $invoicedFee
        );

        $baseFee = max(
            0.0,
            (float) $order->getData(self::BASE_FEE) - $baseInvoicedFee
        );

        // 4. Store the allocation and add it to invoice totals.
        $invoice->setData(self::FEE, $fee);
        $invoice->setData(self::BASE_FEE, $baseFee);

        $invoice->setGrandTotal(
            (float) $invoice->getGrandTotal() + $fee
        );

        $invoice->setBaseGrandTotal(
            (float) $invoice->getBaseGrandTotal() + $baseFee
        );

        return $this;
    }
}
