<?php

declare(strict_types=1);

namespace Doit\HandlingFee\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class Amount extends Value
{
    public function beforeSave(): self
    {
        $value = $this->getValue();

        if (!is_numeric($value)
            || !is_finite((float) $value)
            || (float) $value < 0.0
        ) {
            throw new LocalizedException(
                __('Handling Fee Amount must be a finite number of zero or greater.')
            );
        }

        return parent::beforeSave();
    }
}
