<?php

declare(strict_types=1);

namespace Doit\HandlingFee\Model\Refund;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Creditmemo;

class RemainingAmount
{
    private const FEE = 'doit_handling_fee_amount';
    private const BASE_FEE = 'base_doit_handling_fee_amount';

    /**
     * Calculate the order-wide remaining fee allowance.
     *
     * @return array{amount: float, base_amount: float}
     */
    public function getForOrder(Order $order): array
    {
        $invoiced = 0.0;
        $baseInvoiced = 0.0;

        // 1. Count fee amounts on saved, non-canceled invoices.
        foreach ($order->getInvoiceCollection() as $invoice) {
            if (!$invoice->getId()) {
                continue;
            }

            if ((int) $invoice->getState() === Invoice::STATE_CANCELED) {
                continue;
            }

            $invoiced += (float) $invoice->getData(self::FEE);
            $baseInvoiced += (float) $invoice->getData(self::BASE_FEE);
        }

        $allocated = 0.0;
        $baseAllocated = 0.0;

        // 2. Count fee amounts already allocated to saved credit memos.
        foreach ($order->getCreditmemosCollection() as $creditmemo) {
            if (!$creditmemo->getId()) {
                continue;
            }

            if ((int) $creditmemo->getState() === Creditmemo::STATE_CANCELED) {
                continue;
            }

            $allocated += (float) $creditmemo->getData(self::FEE);
            $baseAllocated += (float) $creditmemo->getData(self::BASE_FEE);
        }

        // 3. Return the remaining allowance in both currencies.
        return [
            'amount' => max(0.0, round($invoiced - $allocated, 4)),
            'base_amount' => max(
                0.0,
                round($baseInvoiced - $baseAllocated, 4)
            ),
        ];
    }

    /**
     * Calculate the remaining fee allowance for one saved invoice.
     *
     * Order-level refunds have no invoice link, so we conservatively
     * subtract their fee amounts from this invoice's allowance too.
     *
     * @return array{amount: float, base_amount: float}
     */
    public function getForInvoice(Invoice $invoice): array
    {
        if (!$invoice->getId()
            || (int) $invoice->getState() === Invoice::STATE_CANCELED
        ) {
            return [
                'amount' => 0.0,
                'base_amount' => 0.0,
            ];
        }

        $order = $invoice->getOrder();
        $invoiceId = (int) $invoice->getId();

        $allocated = 0.0;
        $baseAllocated = 0.0;

        foreach ($order->getCreditmemosCollection() as $creditmemo) {
            if (!$creditmemo->getId()
                || (int) $creditmemo->getState() === Creditmemo::STATE_CANCELED
            ) {
                continue;
            }

            $linkedInvoiceId = (int) $creditmemo->getInvoiceId();

            // Skip refunds linked to a different invoice.
            // Include refunds without an invoice link.
            if ($linkedInvoiceId !== 0
                && $linkedInvoiceId !== $invoiceId
            ) {
                continue;
            }

            $allocated += (float) $creditmemo->getData(self::FEE);
            $baseAllocated += (float) $creditmemo->getData(self::BASE_FEE);
        }

        $remaining = max(
            0.0,
            round((float) $invoice->getData(self::FEE) - $allocated, 4)
        );

        $baseRemaining = max(
            0.0,
            round(
                (float) $invoice->getData(self::BASE_FEE) - $baseAllocated,
                4
            )
        );

        $orderRemaining = $this->getForOrder($order);

        // Respect both the invoice limit and the order-wide limit.
        return [
            'amount' => min($remaining, $orderRemaining['amount']),
            'base_amount' => min(
                $baseRemaining,
                $orderRemaining['base_amount']
            ),
        ];
    }
}
