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
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\CollectionFactory as ListCollectionFactory;
use BroSolutions\QuickOrder\Service\Quote\ProductAdder;
use BroSolutions\QuickOrder\Service\Quote\QuoteManager;
use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Orchestrates the automated order placement process.
 * * This service acts as the primary entry point for generating automated orders
 * based on a predefined schedule.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class PlaceAutomatedOrder
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CreateDataAddListToCart
     */
    private $createDataAddListToCart;

    /**
     * @var ListCollectionFactory
     */
    private $listCollectionFactory;

    /**
     * @var Emulation
     */
    private $emulation;

    /**
     * @var QuoteManager
     */
    private $quoteManager;

    /**
     * @var ProductAdder
     */
    private $productAdder;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    /**
     * Constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param CartManagementInterface $cartManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param CreateDataAddListToCart $createDataAddListToCart
     * @param ListCollectionFactory $listCollectionFactory
     * @param Emulation $emulation
     * @param QuoteManager $quoteManager
     * @param ProductAdder $productAdder
     * @param EventManagerInterface $eventManager
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CartManagementInterface $cartManagement,
        CustomerRepositoryInterface $customerRepository,
        CreateDataAddListToCart $createDataAddListToCart,
        ListCollectionFactory $listCollectionFactory,
        Emulation $emulation,
        QuoteManager $quoteManager,
        ProductAdder $productAdder,
        EventManagerInterface $eventManager
    ) {
        $this->storeManager = $storeManager;
        $this->cartManagement = $cartManagement;
        $this->customerRepository = $customerRepository;
        $this->createDataAddListToCart = $createDataAddListToCart;
        $this->listCollectionFactory = $listCollectionFactory;
        $this->emulation = $emulation;
        $this->quoteManager = $quoteManager;
        $this->productAdder = $productAdder;
        $this->eventManager = $eventManager;
    }

    /**
     * Executes the automated order placement process.
     *
     * Initializes a quote, processes products, applies addresses and payment methods,
     * places the final order, and triggers corresponding success or error events.
     *
     * @param AutomaticSchedule $schedule
     * @return int
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute(AutomaticSchedule $schedule): int
    {
        $list = $this->listCollectionFactory->create()
            ->addFieldToFilter('id', $schedule->getData('list_id'))
            ->getFirstItem();

        if (!$list->getId()) {
            throw new LocalizedException(__('Product List not found.'));
        }

        $storeId = (int)($list->getData('store_id') ?: $this->storeManager->getDefaultStoreView()->getId());
        $this->emulation->startEnvironmentEmulation($storeId);

        try {
            $customer = $this->customerRepository->getById($list->getData('customer_id'));

            $productsJson = $this->createDataAddListToCart->execute((int)$list->getId());
            $productsData = json_decode((string)$productsJson, true);

            if (empty($productsData)) {
                throw new LocalizedException(__('Product list is empty.'));
            }

            $quote = $this->quoteManager->initializeQuote($customer, $storeId);
            $this->productAdder->process($quote, $productsData, $storeId, $schedule);

            if (count($quote->getAllItems()) === 0) {
                throw new LocalizedException(__('No valid products left to order.'));
            }

            $shippingData = json_decode((string)$schedule->getData('shipping_address'), true);
            $billingData = json_decode((string)$schedule->getData('billing_address'), true);

            $this->quoteManager->applyAddressesAndPayment(
                $quote,
                $customer,
                $shippingData,
                $billingData,
                (string)$schedule->getData('shipping_method'),
                (string)$schedule->getData('payment_method')
            );

            $orderId = (int)$this->cartManagement->placeOrder($quote->getId());

            $this->eventManager->dispatch('brosolutions_quickorder_schedule_success', [
                'schedule' => $schedule,
                'order_id' => $orderId,
                'customer' => $customer,
                'list_name' => $list->getData('list_name'),
                'store_id' => $storeId
            ]);

            return $orderId;

        } catch (Exception $e) {
            $this->eventManager->dispatch('brosolutions_quickorder_schedule_error', [
                'schedule' => $schedule,
                'exception' => $e,
                'list_name' => $list->getData('list_name'),
                'store_id' => $storeId,
                'customer_id' => $list->getData('customer_id')
            ]);
            throw $e;
        } finally {
            $this->emulation->stopEnvironmentEmulation();
        }
    }
}
