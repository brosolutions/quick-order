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

namespace BroSolutions\QuickOrder\Service\Quote;

use BroSolutions\QuickOrder\Exception\PauseScheduleException;
use BroSolutions\QuickOrder\Model\AutomaticSchedule;
use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule as ScheduleResource;
use BroSolutions\QuickOrder\Service\Product\BulkLoader;
use Exception;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

/**
 * Validates and adds a collection of products to the quote using type strategies.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ProductAdder
{
    /**
     * @var BulkLoader
     */
    private BulkLoader $bulkLoader;

    /**
     * @var array
     */
    private array $strategies;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param BulkLoader $bulkLoader
     * @param LoggerInterface $logger
     * @param array $strategies
     */
    public function __construct(
        BulkLoader $bulkLoader,
        LoggerInterface $logger,
        array $strategies = []
    ) {
        $this->bulkLoader = $bulkLoader;
        $this->logger = $logger;
        $this->strategies = $strategies;
    }

    /**
     * Processes raw product data, validates, and adds to quote.
     *
     * @param Quote $quote
     * @param array $productsData
     * @param int $storeId
     * @param AutomaticSchedule $schedule
     * @throws LocalizedException
     * @throws PauseScheduleException
     */
    public function process(Quote $quote, array $productsData, int $storeId, AutomaticSchedule $schedule): void
    {
        $skus = array_column($productsData, 'sku');
        $loadedProducts = $this->bulkLoader->loadBySkus($skus, $storeId);
        $customerGroupId = (int)$quote->getCustomerGroupId();

        $originalPrices = json_decode((string)$schedule->getData('original_prices'), true) ?? [];
        $actionMissing = $schedule->getData('action_missing');
        $actionOos = $schedule->getData('action_oos');
        $actionPrice = $schedule->getData('action_price');
        $threshold = (float)$schedule->getData('price_threshold');

        foreach ($productsData as $itemData) {
            $sku = $itemData['sku'] ?? '';
            $typeId = $itemData['type_id'] ?? 'simple';

            if (empty($sku) || empty($itemData['qty'])) {
                continue;
            }

            if (!isset($loadedProducts[$sku])) {
                $this->handleError($sku, "Product does not exist.", $actionMissing);
                continue;
            }

            $product = $loadedProducts[$sku];

            if ($product->getStatus() != Status::STATUS_ENABLED) {
                $this->handleError($sku, "Product is disabled.", $actionMissing);
                continue;
            }

            if (!$product->isSalable()) {
                $this->handleError($sku, "Product is out of stock.", $actionOos);
                continue;
            }

            $strategy = $this->strategies[$typeId] ?? $this->strategies['simple'];
            $currentPrice = $strategy->calculatePrice($product, $itemData, $customerGroupId);

            if (isset($originalPrices[$sku])) {
                $oldPrice = (float)$originalPrices[$sku];

                if ($actionPrice === ScheduleResource::ACTION_BLOCK && $currentPrice > $oldPrice) {
                    $diffPercent = ($currentPrice - $oldPrice) / $oldPrice * 100;
                    if ($diffPercent > 0 && $diffPercent > $threshold) {
                        $this->handleError(
                            $sku,
                            sprintf(
                                "Price increased by %.2f%% (threshold %.2f%%).",
                                $diffPercent,
                                $threshold
                            ),
                            ScheduleResource::ACTION_ERROR
                        );
                        continue;
                    }
                }
            }

            try {
                $strategy->addToQuote($quote, $product, $itemData, $storeId);
            } catch (Exception $e) {
                $this->logger->warning('Failed to add product SKU ' . $sku . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Handles validation errors based on configuration.
     *
     * @param string $sku
     * @param string $message
     * @param string $action
     * @throws LocalizedException
     * @throws PauseScheduleException
     */
    private function handleError(string $sku, string $message, string $action): void
    {
        $fullMessage = "SKU {$sku}: {$message}";

        if ($action === ScheduleResource::ACTION_ERROR) {
            throw new LocalizedException(__($fullMessage . " Stopping order creation."));
        } elseif ($action === ScheduleResource::ACTION_PAUSE) {
            throw new PauseScheduleException(__($fullMessage . " Pausing schedule."));
        }

        $this->logger->warning($fullMessage . " Skipping product.");
    }
}
