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

namespace BroSolutions\QuickOrder\Service;

use BroSolutions\QuickOrder\Model\AutomaticSchedule;
use BroSolutions\QuickOrder\Model\PriceHistoryFactory;
use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule as ScheduleResource;
use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule\CollectionFactory as ScheduleCollectionFactory;
use BroSolutions\QuickOrder\Model\ResourceModel\PriceHistory as PriceHistoryResource;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\CollectionFactory as ListCollectionFactory;
use BroSolutions\QuickOrder\Service\Product\BulkLoader;
use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Store\Model\App\Emulation;
use Psr\Log\LoggerInterface;

/**
 * Service to process and verify price changes for all active schedules.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class CheckSchedulePrices
{
    /**
     * @var ScheduleCollectionFactory
     */
    private ScheduleCollectionFactory $scheduleCollectionFactory;

    /**
     * @var ListCollectionFactory
     */
    private ListCollectionFactory $listCollectionFactory;

    /**
     * @var CreateDataAddListToCart
     */
    private CreateDataAddListToCart $createDataAddListToCart;

    /**
     * @var BulkLoader
     */
    private BulkLoader $bulkLoader;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var Emulation
     */
    private Emulation $emulation;

    /**
     * @var PriceHistoryFactory
     */
    private PriceHistoryFactory $priceHistoryFactory;

    /**
     * @var PriceHistoryResource
     */
    private PriceHistoryResource $priceHistoryResource;

    /**
     * @var ScheduleResource
     */
    private ScheduleResource $scheduleResource;

    /**
     * @var EventManagerInterface
     */
    private EventManagerInterface $eventManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var array
     */
    private array $strategies;

    /**
     * Constructor.
     *
     * @param ScheduleCollectionFactory $scheduleCollectionFactory
     * @param ListCollectionFactory $listCollectionFactory
     * @param CreateDataAddListToCart $createDataAddListToCart
     * @param BulkLoader $bulkLoader
     * @param CustomerRepositoryInterface $customerRepository
     * @param Emulation $emulation
     * @param PriceHistoryFactory $priceHistoryFactory
     * @param PriceHistoryResource $priceHistoryResource
     * @param ScheduleResource $scheduleResource
     * @param EventManagerInterface $eventManager
     * @param LoggerInterface $logger
     * @param array $strategies
     */
    public function __construct(
        ScheduleCollectionFactory $scheduleCollectionFactory,
        ListCollectionFactory $listCollectionFactory,
        CreateDataAddListToCart $createDataAddListToCart,
        BulkLoader $bulkLoader,
        CustomerRepositoryInterface $customerRepository,
        Emulation $emulation,
        PriceHistoryFactory $priceHistoryFactory,
        PriceHistoryResource $priceHistoryResource,
        ScheduleResource $scheduleResource,
        EventManagerInterface $eventManager,
        LoggerInterface $logger,
        array $strategies = []
    ) {
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
        $this->listCollectionFactory = $listCollectionFactory;
        $this->createDataAddListToCart = $createDataAddListToCart;
        $this->bulkLoader = $bulkLoader;
        $this->customerRepository = $customerRepository;
        $this->emulation = $emulation;
        $this->priceHistoryFactory = $priceHistoryFactory;
        $this->priceHistoryResource = $priceHistoryResource;
        $this->scheduleResource = $scheduleResource;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        $this->strategies = $strategies;
    }

    /**
     * Iterate over active schedules to find and log price changes.
     *
     * @return void
     */
    public function execute(): void
    {
        $schedules = $this->scheduleCollectionFactory->create()
            ->addFieldToFilter('main_table.status', ScheduleResource::STATUS_ACTIVE);

        /** @var AutomaticSchedule $schedule */
        foreach ($schedules as $schedule) {
            try {
                $this->processSchedule($schedule);
            } catch (Exception $e) {
                $this->logger->error(
                    sprintf(
                        "Failed to check prices for Schedule ID %s: %s",
                        $schedule->getId(),
                        $e->getMessage()
                    )
                );
            }
        }
    }

    /**
     * Process a single schedule.
     *
     * @param AutomaticSchedule $schedule
     * @return void
     * @throws Exception
     */
    private function processSchedule(AutomaticSchedule $schedule): void
    {
        $list = $this->listCollectionFactory->create()
            ->addFieldToFilter('id', $schedule->getData('list_id'))
            ->getFirstItem();

        if (!$list->getId()) {
            return;
        }

        $storeId = (int)$list->getData('store_id');
        $customerId = (int)$list->getData('customer_id');

        $this->emulation->startEnvironmentEmulation($storeId);

        try {
            $customer = $this->customerRepository->getById($customerId);
            $customerGroupId = (int)$customer->getGroupId();

            $productsJson = $this->createDataAddListToCart->execute((int)$list->getId());
            if (!$productsJson) {
                return;
            }

            $productsData = json_decode((string)$productsJson, true);
            $skus = array_column($productsData, 'sku');
            $loadedProducts = $this->bulkLoader->loadBySkus($skus, $storeId);

            $originalPrices = json_decode((string)$schedule->getData('original_prices'), true) ?? [];
            $priceChanges = [];
            $hasChanges = false;

            foreach ($productsData as $itemData) {
                $sku = $itemData['sku'] ?? '';
                $typeId = $itemData['type_id'] ?? 'simple';

                if (empty($sku) || !isset($loadedProducts[$sku])) {
                    continue;
                }

                $strategy = $this->strategies[$typeId] ?? $this->strategies['simple'];
                $currentPrice = $strategy->calculatePrice($loadedProducts[$sku], $itemData, $customerGroupId);

                if (isset($originalPrices[$sku])) {
                    $oldPrice = (float)$originalPrices[$sku];

                    if (abs($currentPrice - $oldPrice) > 0.001) {
                        $priceChanges[] = [
                            'sku' => $sku,
                            'old_price' => $oldPrice,
                            'new_price' => $currentPrice
                        ];

                        $historyModel = $this->priceHistoryFactory->create();
                        $historyModel->setData([
                            'schedule_id' => $schedule->getId(),
                            'sku'         => $sku,
                            'old_price'   => $oldPrice,
                            'new_price'   => $currentPrice
                        ]);

                        $this->priceHistoryResource->save($historyModel);

                        $originalPrices[$sku] = $currentPrice;
                        $hasChanges = true;
                    }
                }
            }

            if ($hasChanges) {
                $schedule->setData('original_prices', json_encode($originalPrices));
                $this->scheduleResource->save($schedule);

                $this->eventManager->dispatch('brosolutions_quickorder_price_changed', [
                    'schedule' => $schedule,
                    'price_changes' => $priceChanges,
                    'store_id' => $storeId
                ]);
            }
        } finally {
            $this->emulation->stopEnvironmentEmulation();
        }
    }
}
