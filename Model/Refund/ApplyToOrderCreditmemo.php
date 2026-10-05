<?php

declare(strict_types=1);

namespace Doit\HandlingFee\Model\Refund;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Creditmemo;

class ApplyToOrderCreditmemo
{
    private const FEE = 'doit_handling_fee_amount';
    private const BASE_FEE = 'base_doit_handling_fee_amount';

    public function __construct(
        private readonly AmountValidator $amountValidator,
        private readonly RemainingAmount $remainingAmount
    ) {
    }

    public function apply(
        Creditmemo $creditmemo,
        mixed $requestedAmount
    ): void {
        if ($creditmemo->getId()) {
            throw new LocalizedException(
                __('The handling fee cannot be recalculated on a saved credit memo.')
            );
        }

        $order = $creditmemo->getOrder();
        $invoice = $creditmemo->getInvoice();

// Do not silently treat a linked refund as an order-level refund.
        if ($creditmemo->getInvoiceId() && !$invoice) {
            throw new LocalizedException(
                __('The invoice linked to this credit memo could not be loaded.')
            );
        }

// 1. Choose the correct remaining fee allowance.
        if ($invoice) {
            if ((int) $invoice->getOrderId() !== (int) $order->getId()) {
                throw new LocalizedException(
                    __('The selected invoice does not belong to this order.')
                );
            }

            $remaining = $this->remainingAmount->getForInvoice($invoice);
        } else {
            $remaining = $this->remainingAmount->getForOrder($order);
        }

        $amount = $this->amountValidator->validate(
            $requestedAmount,
            $remaining['amount']
        );

        // Our current input accepts up to two decimal places.
        $roundedAmount = round($amount, 2);

        if (abs($amount - $roundedAmount) > 0.0000001) {
            throw new LocalizedException(
                __('Enter the handling fee refund with no more than two decimal places.')
            );
        }

        $amount = $roundedAmount;

        // 2. Calculate the corresponding base-currency amount.
        $baseAmount = 0.0;

        if ($amount > 0.0) {
            $orderFee = (float) $order->getData(self::FEE);
            $baseOrderFee = (float) $order->getData(self::BASE_FEE);

            if ($orderFee <= 0.0 || $baseOrderFee < 0.0) {
                throw new LocalizedException(
                    __('The saved handling fee amounts are invalid.')
                );
            }

            if (abs($amount - $remaining['amount']) < 0.0000001) {
                // Use the remaining base amount on the final fee refund.
                $baseAmount = $remaining['base_amount'];
            } else {
                $baseAmount = round(
                    $amount * $baseOrderFee / $orderFee,
                    4
                );
            }

            if ($baseAmount > $remaining['base_amount'] + 0.0000001) {
                throw new LocalizedException(
                    __('The base handling fee refund exceeds the remaining allowance.')
                );
            }
        }

        // 3. Replace only our previous contribution.
        $previousAmount = (float) $creditmemo->getData(self::FEE);
        $previousBaseAmount = (float) $creditmemo->getData(self::BASE_FEE);

        $grandTotal = round(
            (float) $creditmemo->getGrandTotal()
            - $previousAmount
            + $amount,
            4
        );

        $baseGrandTotal = round(
            (float) $creditmemo->getBaseGrandTotal()
            - $previousBaseAmount
            + $baseAmount,
            4
        );

        // 4. The complete refund must also fit the remaining paid balance.
        $availablePaid = round(
            (float) $order->getTotalPaid()
            - (float) $order->getTotalRefunded(),
            4
        );

        $baseAvailablePaid = round(
            (float) $order->getBaseTotalPaid()
            - (float) $order->getBaseTotalRefunded(),
            4
        );

        if ($grandTotal > $availablePaid + 0.0000001
            || $baseGrandTotal > $baseAvailablePaid + 0.0000001
        ) {
            throw new LocalizedException(
                __('The refund total exceeds the remaining paid amount.')
            );
        }

        // 5. Apply the validated amounts to the object.
        $creditmemo->setData(self::FEE, $amount);
        $creditmemo->setData(self::BASE_FEE, $baseAmount);
        $creditmemo->setGrandTotal($grandTotal);
        $creditmemo->setBaseGrandTotal($baseGrandTotal);
    }
}
