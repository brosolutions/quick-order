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

namespace BroSolutions\QuickOrder\Observer;

use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\CollectionFactory as ListCollectionFactory;
use BroSolutions\QuickOrder\Service\ScheduleEmailSender;
use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

/**
 * Observer to handle price changes during automated order creation.
 * Sends an email notification to the customer.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class PriceChangeObserver implements ObserverInterface
{
    /**
     * @var ScheduleEmailSender
     */
    private $emailSender;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var ListCollectionFactory
     */
    private $listCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ScheduleEmailSender $emailSender
     * @param CustomerRepositoryInterface $customerRepository
     * @param ListCollectionFactory $listCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScheduleEmailSender $emailSender,
        CustomerRepositoryInterface $customerRepository,
        ListCollectionFactory $listCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->emailSender = $emailSender;
        $this->customerRepository = $customerRepository;
        $this->listCollectionFactory = $listCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Executes the observer. Generates the price change text and triggers the email notification.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $schedule = $observer->getEvent()->getSchedule();
        $priceChanges = $observer->getEvent()->getPriceChanges();
        $storeId = (int)$observer->getEvent()->getStoreId();

        try {
            $list = $this->listCollectionFactory->create()
                ->addFieldToFilter('id', $schedule->getData('list_id'))
                ->getFirstItem();

            if (!$list->getId()) {
                return;
            }

            $customer = $this->customerRepository->getById((int)$list->getData('customer_id'));

            $changesText = '';
            foreach ($priceChanges as $change) {
                $changesText .= sprintf(
                    "SKU: %s | Old Price: %s | New Price: %s<br>",
                    $change['sku'],
                    $change['old_price'],
                    $change['new_price']
                );
            }

            $this->emailSender->sendPriceChangeEmail(
                $customer->getEmail(),
                trim($customer->getFirstname() . ' ' . $customer->getLastname()),
                (string)$schedule->getId(),
                $changesText,
                $storeId
            );
        } catch (Exception $e) {
            $this->logger->error('Failed to send price change email: ' . $e->getMessage());
        }
    }
}
