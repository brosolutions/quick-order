<?php
/**
 * Copyright (c) 2026 BroSolutions
 * All rights reserved
 *
 * This product includes proprietary software developed at BroSolutions, Ukraine
 * For more information see https://www.brosolutions.net/
 *
 * To obtain a valid license for using this software please contact us at
 * contact@brosolutions.net
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Cron;

use BroSolutions\QuickOrder\Service\CheckSchedulePrices;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Cron job to independently check for price changes in active schedules.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class CheckPrices
{
    /**
     * @var string
     */
    private const CRON_ENABLE_PATH = 'brosolution_quick_order/scheduled_automated_orders/price_alert_enable';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var CheckSchedulePrices
     */
    private CheckSchedulePrices $checkSchedulePrices;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param CheckSchedulePrices $checkSchedulePrices
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CheckSchedulePrices $checkSchedulePrices,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->checkSchedulePrices = $checkSchedulePrices;
        $this->logger = $logger;
    }

    /**
     * Execute the cron job.
     *
     * @return void
     */
    public function execute(): void
    {
        $isEnabled = $this->scopeConfig->isSetFlag(
            self::CRON_ENABLE_PATH,
            ScopeInterface::SCOPE_WEBSITE
        );

        if (!$isEnabled) {
            return;
        }

        try {
            $this->checkSchedulePrices->execute();
        } catch (\Throwable $e) {
            $this->logger->error('BroSolutions QuickOrder CheckPrices Cron Error: ' . $e->getMessage());
        }
    }
}
