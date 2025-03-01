<?php
/**
 * Copyright (c) 2025 BroSolutions
 * All rights reserved
 *
 * This product includes proprietary software developed at BroSolutions, Ukraine
 * For more information see https://www.brosolutions.net/
 *
 * To obtain a valid license for using this software please contact us at
 * contact@brosolutions.net
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Service;

use Magento\Directory\Model\CurrencyFactory;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManager;
use Magento\Framework\Exception\LocalizedException;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ConvertCurrency
{
    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var CurrencyFactory
     */
    private $currencyFactory;

    /**
     * @param StoreManager $storeManager
     * @param CurrencyFactory $currencyFactory
     */
    public function __construct(
        StoreManager    $storeManager,
        CurrencyFactory $currencyFactory
    ) {
        $this->storeManager = $storeManager;
        $this->currencyFactory = $currencyFactory;
    }

    /**
     * Convert currency
     *
     * @param string $amount
     * @param string $currencyToCurrencyCode
     * @return float
     */
    public function execute(string $amount, string $currencyToCurrencyCode): float
    {
        try {
            $baseCurrencyCode = $this->storeManager->getStore()->getBaseCurrencyCode();
            $сurrency = $this->currencyFactory->create()->load($baseCurrencyCode);

            return $сurrency->convert($amount, $currencyToCurrencyCode);
        } catch (NoSuchEntityException|LocalizedException $e) {
            return 0;
        }
    }
}
