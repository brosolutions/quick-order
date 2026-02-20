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

namespace BroSolutions\QuickOrder\Block\Customer;

use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\Collection;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\CollectionFactory;
use BroSolutions\QuickOrder\Service\GetOfflinePaymentMethods;
use BroSolutions\QuickOrder\Service\GetOfflineShippingMethods;
use Magento\Config\Model\Config\Source\Locale\Timezone;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Shipping\Model\Config;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class AutomatedOrders extends Template
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var GetOfflinePaymentMethods
     */
    private $getOfflinePaymentMethods;

    /**
     * @var PaymentConfig
     */
    private $paymentConfig;

    /**
     * @var Timezone
     */
    private $timezoneSource;

    /**
     * @var Config
     */
    private $shippingConfig;

    /**
     * @var GetOfflineShippingMethods
     */
    private $getOfflineShippingMethods;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @inheritdoc
     */
    public function __construct(
        Template\Context $context,
        CollectionFactory $collectionFactory,
        Session $customerSession,
        TimezoneInterface $timezone,
        GetOfflinePaymentMethods $getOfflinePaymentMethods,
        PaymentConfig $paymentConfig,
        Timezone $timezoneSource,
        Config $shippingConfig,
        GetOfflineShippingMethods $getOfflineShippingMethods,
        AddressRepositoryInterface $addressRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->collectionFactory = $collectionFactory;
        $this->customerSession = $customerSession;
        $this->timezone = $timezone;
        $this->getOfflinePaymentMethods = $getOfflinePaymentMethods;
        $this->paymentConfig = $paymentConfig;
        $this->timezoneSource = $timezoneSource;
        $this->shippingConfig = $shippingConfig;
        $this->getOfflineShippingMethods = $getOfflineShippingMethods;
        $this->addressRepository = $addressRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get orders
     *
     * @return Collection
     */
    public function getOrders(): Collection
    {
        $customerId = $this->customerSession->getCustomerId();
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->setOrder('created_at', 'DESC');

        return $collection;
    }

    /**
     * Get payment methods
     *
     * @return array
     */
    public function getPaymentMethods(): array
    {
        $methods = [];
        $allMethods = $this->paymentConfig->getActiveMethods();
        $allowedMethodCodes = $this->getOfflinePaymentMethods->execute();

        foreach ($allowedMethodCodes as $code) {
            if (isset($allMethods[$code])) {
                $method = $allMethods[$code];

                $methods[] = [
                    'code'  => $code,
                    'title' => $method->getTitle()
                ];
            }
        }

        return $methods;
    }

    /**
     * Get shipping methods
     *
     * @return array
     */
    public function getShippingMethods(): array
    {
        $methods = [];
        $allCarriers = $this->shippingConfig->getActiveCarriers();
        $allowedMethodCodes = $this->getOfflineShippingMethods->execute();

        foreach ($allowedMethodCodes as $fullCode) {

            $parts = explode('_', $fullCode, 2);
            if (count($parts) !== 2) {
                continue;
            }

            [$carrierCode, $methodCode] = $parts;

            if (!isset($allCarriers[$carrierCode])) {
                continue;
            }

            $carrier = $allCarriers[$carrierCode];
            $allowedMethods = $carrier->getAllowedMethods();

            if (!$allowedMethods || !isset($allowedMethods[$methodCode])) {
                continue;
            }

            $carrierTitle = $carrier->getConfigData('title');
            $methodTitle = $allowedMethods[$methodCode];

            $methods[] = [
                'code'  => $fullCode,
                'title' => $carrierTitle . ' - ' . $methodTitle
            ];
        }

        return $methods;
    }

    /**
     * Get customer address
     *
     * @return array|AddressInterface[]
     * @throws
     */
    public function getCustomerAddresses(): array
    {
        try {
            $customer = $this->customerSession->getCustomer();
            if (!$customer || !$customer->getId()) {
                return [];
            }

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('parent_id', $customer->getId(), 'eq')
                ->create();

            $searchResults = $this->addressRepository->getList($searchCriteria);
            $addresses = $searchResults->getItems();
        } catch (NoSuchEntityException $e) {
            return [];
        }
        return $addresses;
    }

    /**
     * Get address label
     *
     * @param AddressInterface $address
     * @return string
     */
    public function getAddressLabel(AddressInterface $address): string
    {
        $street = is_array($address->getStreet()) ? implode(
            ', ',
            $address->getStreet()
        ) : $address->getStreet();

        $region = $address->getRegion();
        if (is_object($region)) {
            $regionName = $region->getRegion() ?: '';
        } else {
            $regionName = $region ?: '';
        }
        return $address->getFirstname() . ' ' . $address->getLastname()
            . ', ' . $street
            . ', ' . $address->getCity()
            . ', ' . $regionName
            . ' ' . $address->getPostcode();
    }

    /**
     * Get default shipping address ID for current customer
     *
     * @return string|null
     */
    public function getDefaultShippingAddressId(): ?string
    {
        $customer = $this->customerSession->getCustomer();
        if (!$customer || !$customer->getId()) {
            return null;
        }

        $defaultShippingId = $customer->getDefaultShipping();

        $addresses = $customer->getAddresses();
        if (!$defaultShippingId && !empty($addresses)) {
            $firstAddress = reset($addresses);
            $defaultShippingId = $firstAddress ? $firstAddress->getId() : null;
        }

        return $defaultShippingId;
    }

    /**
     * Get default billing address ID for current customer
     *
     * @return string|null
     */
    public function getDefaultBillingAddressId(): ?string
    {
        $customer = $this->customerSession->getCustomer();
        if (!$customer || !$customer->getId()) {
            return null;
        }

        $defaultBillingId = $customer->getDefaultBilling();

        $addresses = $customer->getAddresses();
        if (!$defaultBillingId && !empty($addresses)) {
            $firstAddress = reset($addresses);
            $defaultBillingId = $firstAddress ? $firstAddress->getId() : null;
        }

        return $defaultBillingId;
    }

    /**
     * Get store timezone
     *
     * @return string
     */
    public function getStoreTimezone(): string
    {
        return $this->timezone->getConfigTimezone();
    }

    /**
     * Get time zones
     *
     * @return array
     */
    public function getTimezones(): array
    {
        return  $this->timezoneSource->toOptionArray();
    }
}
