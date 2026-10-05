<?php

declare(strict_types=1);

namespace Doit\HandlingFee\Model\Refund;

use Magento\Framework\Exception\LocalizedException;

class AmountValidator
{
    public function validate(
        mixed $value,
        float $remainingAmount
    ): float {
        // An omitted or empty input means no handling-fee refund.
        if ($value === null || $value === '') {
            return 0.0;
        }

        // Reject text, arrays, and other nonnumeric values.
        if (!is_numeric($value)) {
            throw new LocalizedException(
                __('Enter a valid handling fee refund amount.')
            );
        }

        $amount = (float) $value;

        // The amount must be finite and nonnegative.
        if (!is_finite($amount) || $amount < 0.0) {
            throw new LocalizedException(
                __('The handling fee refund must be a finite, nonnegative amount.')
            );
        }

        // Do not refund more than the remaining allowance.
        if ($amount > $remainingAmount) {
            throw new LocalizedException(
                __(
                    'The handling fee refund cannot exceed the remaining amount of %1.',
                    $remainingAmount
                )
            );
        }

        return $amount;
    }
}
