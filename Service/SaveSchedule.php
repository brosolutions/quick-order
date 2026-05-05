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

use BroSolutions\QuickOrder\Model\AutomaticScheduleFactory;
use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule;
use DateTime;
use DateTimeZone;
use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Service to process and save schedule with address snapshots and rules.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class SaveSchedule
{
    /**
     * @var array
     */
    private const REQUIRED_FIELDS = [
        'list_id',
        'payment_method',
        'billing_address',
        'shipping_address',
        'shipping_method',
        'frequency',
        'start_at',
        'timezone'
    ];

    /**
     * @var AutomaticScheduleFactory
     */
    private $scheduleFactory;

    /**
     * @var AutomaticSchedule
     */
    private $resource;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var CalculateNextRun
     */
    private $calculateNextRun;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CreateDataAddListToCart
     */
    private $createDataAddListToCart;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * Constructor
     *
     * @param AutomaticScheduleFactory $scheduleFactory
     * @param AutomaticSchedule $resource
     * @param AddressRepositoryInterface $addressRepository
     * @param Json $json
     * @param CalculateNextRun $calculateNextRun
     * @param ProductRepositoryInterface $productRepository
     * @param CreateDataAddListToCart $createDataAddListToCart
     * @param LoggerInterface $logger
     * @param CustomerSession $customerSession
     */
    public function __construct(
        AutomaticScheduleFactory $scheduleFactory,
        AutomaticSchedule $resource,
        AddressRepositoryInterface $addressRepository,
        Json $json,
        CalculateNextRun $calculateNextRun,
        ProductRepositoryInterface $productRepository,
        CreateDataAddListToCart $createDataAddListToCart,
        LoggerInterface $logger,
        CustomerSession $customerSession
    ) {
        $this->scheduleFactory = $scheduleFactory;
        $this->resource = $resource;
        $this->addressRepository = $addressRepository;
        $this->json = $json;
        $this->calculateNextRun = $calculateNextRun;
        $this->productRepository = $productRepository;
        $this->createDataAddListToCart = $createDataAddListToCart;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
    }

    /**
     * Save schedule
     *
     * @param array $data
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute(array $data): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($data[$field])) {
                throw new LocalizedException(
                    __('Missing required field: %1', $field)
                );
            }
        }

        $shippingSnapshot = $this->createAddressSnapshot((int)$data['shipping_address']);
        $billingSnapshot  = $this->createAddressSnapshot((int)$data['billing_address']);

        $timezone = $data['timezone'];
        $localStartDate = new DateTime($data['start_at'], new DateTimeZone($timezone));

        $utcStartDate = clone $localStartDate;
        $utcStartDate->setTimezone(new DateTimeZone('UTC'));
        $utcStartString = $utcStartDate->format('Y-m-d H:i:s');

        $nextRunUtc = $this->calculateNextRun->execute(
            $utcStartString,
            $timezone,
            $data['frequency'],
            isset($data['frequency_value']) && $data['frequency_value'] !== '' ?
                (int)$data['frequency_value'] : null
        );

        $originalPrices = [];
        $productsJson = $this->createDataAddListToCart->execute((int)$data['list_id']);
        if ($productsJson) {
            $productsData = json_decode($productsJson, true);
            foreach ($productsData as $item) {
                if (!empty($item['sku'])) {
                    try {
                        $product = $this->productRepository->get($item['sku']);
                        $originalPrices[$item['sku']] = $this->calculateConfiguredPrice($product, $item);
                    } catch (NoSuchEntityException $e) {
                        $this->logger->warning(
                            sprintf('Product SKU %s not found during schedule creation.', $item['sku'])
                        );
                    }
                }
            }
        }

        $model = $this->scheduleFactory->create();

        $model->setData([
            'list_id'          => (int)$data['list_id'],
            'shipping_address' => $shippingSnapshot,
            'billing_address'  => $billingSnapshot,
            'shipping_method'  => $data['shipping_method'],
            'payment_method'   => $data['payment_method'],
            'frequency'        => $data['frequency'],
            'frequency_value'  => $data['frequency_value'] ?? null,
            'start_at'         => $utcStartString,
            'timezone'         => $timezone,
            'next_run_at'      => $nextRunUtc,
            'last_run_at'      => null,
            'status'           => AutomaticSchedule::STATUS_ACTIVE,
            'action_missing'   => $data['action_missing'] ?? AutomaticSchedule::ACTION_ERROR,
            'action_oos'       => $data['action_oos'] ?? AutomaticSchedule::ACTION_ERROR,
            'action_price'     => $data['action_price'] ?? AutomaticSchedule::ACTION_ALLOW,
            'price_threshold'  => !empty($data['price_threshold']) ? (float)$data['price_threshold'] : null,
            'original_prices'  => $this->json->serialize($originalPrices)
        ]);

        $this->resource->save($model);
    }

    /**
     * Creates a JSON snapshot of the customer address.
     *
     * @param int $addressId
     * @return string
     * @throws LocalizedException
     */
    private function createAddressSnapshot(int $addressId): string
    {
        $address = $this->addressRepository->getById($addressId);

        $snapshot = [
            'firstname'  => $address->getFirstname(),
            'lastname'   => $address->getLastname(),
            'company'    => $address->getCompany(),
            'street'     => $address->getStreet(),
            'city'       => $address->getCity(),
            'region'     => $address->getRegion() ? $address->getRegion()->getRegion() : '',
            'region_id'  => $address->getRegionId(),
            'postcode'   => $address->getPostcode(),
            'country_id' => $address->getCountryId(),
            'telephone'  => $address->getTelephone(),
        ];

        return $this->json->serialize($snapshot);
    }

    /**
     *  Calculates the exact price based on product type and selected options.
     *
     * @param ProductInterface $product
     * @param array $itemData
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function calculateConfiguredPrice(ProductInterface $product, array $itemData): float
    {
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        /** @var \Magento\Catalog\Model\Product $productModel */
        $productModel = $product;
        $productModel->setCustomerGroupId($customerGroupId);

        $price = 0.0;
        $typeId = $itemData['type_id'] ?? 'simple';

        if ($typeId === 'configurable' && !empty($itemData['active_product']['entity_id'])) {
            try {
                /** @var \Magento\Catalog\Model\Product $childProduct */
                $childProduct = $this->productRepository->getById($itemData['active_product']['entity_id']);
                $childProduct->setCustomerGroupId($customerGroupId);
                $price = (float)$childProduct->getFinalPrice();
            } catch (Exception $e) {
                $price = (float)$productModel->getFinalPrice();
            }
        } elseif ($typeId === 'bundle' && !empty($itemData['active_selections'])) {
            /** @var \Magento\Bundle\Model\Product\Type $typeInstance */
            $typeInstance = $productModel->getTypeInstance();
            $selections = $typeInstance->getSelectionsCollection(
                $typeInstance->getOptionsIds($productModel),
                $productModel
            );
            foreach ($itemData['active_selections'] as $option) {
                if (!empty($option['selection_value'])) {
                    foreach ($option['selection_value'] as $sel) {
                        /** @var \Magento\Catalog\Model\Product $selectionModel */
                        $selectionModel = $selections->getItemById($sel['value_id']);
                        if ($selectionModel) {
                            $selectionModel->setCustomerGroupId($customerGroupId);
                            $price += (float)$selectionModel->getFinalPrice() * (float)($sel['qty'] ?? 1);
                        }
                    }
                }
            }
        } elseif ($typeId === 'grouped' && !empty($itemData['active_selections'])) {
            foreach ($itemData['active_selections'] as $sel) {
                try {
                    /** @var \Magento\Catalog\Model\Product $childProduct */
                    $childProduct = $this->productRepository->getById($sel['id']);
                    $childProduct->setCustomerGroupId($customerGroupId);
                    $price += (float)$childProduct->getFinalPrice() * (float)($sel['qty'] ?? 1);
                } catch (Exception $e) {
                    $this->logger->warning(
                        sprintf('Could not load grouped child product ID %s: %s', $sel['id'], $e->getMessage())
                    );
                }
            }
        } else {
            $price = (float)$productModel->getFinalPrice();
        }

        return $price;
    }
}
