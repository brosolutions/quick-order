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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class GetOfflinePaymentMethods
{
    /**
     * @var string
     */
    private const GET_OFFLINE_PAYMENT_METHODS_CONFIG_PATH =
        'brosolution_quick_order/scheduled_automated_orders/offline_payment_methods';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get offline payment methods config
     *
     * @return array
     */
    public function execute(): array
    {
        $value = (string)$this->scopeConfig->getValue(
            self::GET_OFFLINE_PAYMENT_METHODS_CONFIG_PATH,
            ScopeInterface::SCOPE_WEBSITE
        );

        if ($value === '') {
            return [];
        }

        return array_values(array_filter(explode(',', $value)));
    }
}
