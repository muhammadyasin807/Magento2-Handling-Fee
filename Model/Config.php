<?php

declare(strict_types=1);

namespace Doit\HandlingFee\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED =
        'doit_handling_fee/general/enabled';

    private const XML_PATH_AMOUNT =
        'doit_handling_fee/general/amount';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getAmount(int $storeId): ?float
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_AMOUNT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!is_numeric($value)) {
            return null;
        }

        $amount = (float) $value;

        if (!is_finite($amount) || $amount < 0.0) {
            return null;
        }

        return $amount;
    }
}
