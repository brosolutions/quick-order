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

use BroSolutions\QuickOrder\Model\HistoryFactory;
use BroSolutions\QuickOrder\Model\ResourceModel\History as HistoryResource;
use BroSolutions\QuickOrder\Service\ScheduleEmailSender;
use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

/**
 * Observer for failures during automated order placement.
 * * This observer captures exceptions during the automated schedule run,
 * logs the error details, formats payment-related error messages,
 * and sends an error notification email to the customer.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ScheduleErrorObserver implements ObserverInterface
{
    /**
     * @var HistoryFactory
     */
    private HistoryFactory $historyFactory;

    /**
     * @var HistoryResource
     */
    private HistoryResource $historyResource;

    /**
     * @var ScheduleEmailSender
     */
    private ScheduleEmailSender $emailSender;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param HistoryFactory $historyFactory
     * @param HistoryResource $historyResource
     * @param ScheduleEmailSender $emailSender
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        HistoryFactory $historyFactory,
        HistoryResource $historyResource,
        ScheduleEmailSender $emailSender,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->historyFactory = $historyFactory;
        $this->historyResource = $historyResource;
        $this->emailSender = $emailSender;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * Processes execution error, logs it and notifies the customer.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $schedule = $observer->getEvent()->getSchedule();
        $exception = $observer->getEvent()->getException();
        $listName = (string)$observer->getEvent()->getListName();
        $storeId = (int)$observer->getEvent()->getStoreId();
        $customerId = (int)$observer->getEvent()->getCustomerId();

        $errorMessage = $exception->getMessage();
        $paymentMethod = $schedule->getData('payment_method');

        if (str_contains($errorMessage, 'Purchase order number is a required field')) {
            $errorMessage = sprintf(
                'Payment method "%s" requires a Purchase
                Order Number, which is not supported for automated schedules.',
                $paymentMethod
            );
        }

        try {
            $log = $this->historyFactory->create();
            $log->setData([
                'schedule_id' => $schedule->getId(),
                'order_id' => null,
                'status' => 'error',
                'message' => substr($errorMessage, 0, 1000)
            ]);
            $this->historyResource->save($log);
        } catch (Exception $e) {
            $this->logger->critical('Could not save schedule log: ' . $e->getMessage());
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            $this->emailSender->sendErrorEmail(
                $customer->getEmail(),
                trim($customer->getFirstname() . ' ' . $customer->getLastname()),
                $listName,
                $errorMessage,
                $storeId
            );
        } catch (Exception $e) {
            $this->logger->warning(
                sprintf(
                    'Failed to send error email for schedule ID %s: %s',
                    $schedule->getId(),
                    $e->getMessage()
                )
            );
        }
    }
}
