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

use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Currency\Exception\CurrencyException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class GetCurrencySymbol
{
    /**
     * @var CurrencyInterface
     */
    private $localeCurrency;

    /**
     * @param CurrencyInterface $localeCurrency
     */
    public function __construct(
        CurrencyInterface $localeCurrency
    ) {
        $this->localeCurrency = $localeCurrency;
    }

    /**
     * Get currency code
     *
     * @param string $currencyCode
     * @return string
     */
    public function execute(string $currencyCode): string
    {
        try {
            $currency = $this->localeCurrency->getCurrency($currencyCode);
            return $currency->getSymbol();
        } catch (CurrencyException | NoSuchEntityException $e) {
            return '';
        }
    }
}
