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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManager;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class GetStoreCurrency
{
    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @param StoreManager $storeManager
     */
    public function __construct(
        StoreManager $storeManager,
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * Get currency code
     *
     * @param string $storeCode
     * @return string
     */
    public function execute(string $storeCode): string
    {
        try {
            $store = $this->storeManager->getStore($storeCode);
            return $store->getCurrentCurrencyCode();
        } catch (NoSuchEntityException $e) {
            return '';
        }
    }
}
