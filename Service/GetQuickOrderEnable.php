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

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class GetQuickOrderEnable
{
    /**
     * @var string
     */
    private const QUICK_ORDER_ENABLE_CONFIG_PATH = 'brosolution_quick_order/general/enable';

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
     * Get also bought enable
     *
     * @return bool
     */
    public function execute(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::QUICK_ORDER_ENABLE_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }
}
