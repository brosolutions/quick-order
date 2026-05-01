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

use BroSolutions\QuickOrder\Service\ScheduleEmailSender;
use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Observer for successful automated order placement.
 * * This observer handles post-order-placement logic, specifically
 * sending a confirmation email to the customer.
 * (Note: Successful orders are intentionally NOT logged to the history table).
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ScheduleSuccessObserver implements ObserverInterface
{
    /**
     * @var ScheduleEmailSender
     */
    private ScheduleEmailSender $emailSender;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param ScheduleEmailSender $emailSender
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScheduleEmailSender $emailSender,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        $this->emailSender = $emailSender;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * Sends notification email on successful order creation.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $orderId = (int)$observer->getEvent()->getOrderId();
        $customer = $observer->getEvent()->getCustomer();
        $listName = (string)$observer->getEvent()->getListName();
        $storeId = (int)$observer->getEvent()->getStoreId();

        try {
            $order = $this->orderRepository->get($orderId);
            $this->emailSender->sendSuccessEmail(
                $customer->getEmail(),
                trim($customer->getFirstname() . ' ' . $customer->getLastname()),
                $listName,
                (string)$order->getIncrementId(),
                $storeId
            );
        } catch (Exception $e) {
            $this->logger->error('Failed to send success email: ' . $e->getMessage());
        }
    }
}
