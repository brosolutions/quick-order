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

use BroSolutions\QuickOrder\Model\Config\Source\Frequency;
use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule\Collection as ScheduleCollection;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\Collection as ProductListCollection;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\CollectionFactory;
use BroSolutions\QuickOrder\Service\GetOfflinePaymentMethods;
use BroSolutions\QuickOrder\Service\GetOfflineShippingMethods;
use BroSolutions\QuickOrder\Service\GetOrders;
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
use Magento\Framework\Exception\LocalizedException;
use Exception;

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
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var GetOrders
     */
    private $getOrders;

    /**
     * @var Frequency
     */
    private $frequencySource;

    /**
     * @param Template\Context $context
     * @param CollectionFactory $collectionFactory
     * @param Session $customerSession
     * @param TimezoneInterface $timezone
     * @param GetOfflinePaymentMethods $getOfflinePaymentMethods
     * @param PaymentConfig $paymentConfig
     * @param Timezone $timezoneSource
     * @param Config $shippingConfig
     * @param GetOfflineShippingMethods $getOfflineShippingMethods
     * @param AddressRepositoryInterface $addressRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetOrders $getOrders
     * @param Frequency $frequencySource
     * @param array $data
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
        GetOrders $getOrders,
        Frequency $frequencySource,
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
        $this->getOrders = $getOrders;
        $this->frequencySource = $frequencySource;
    }

    /**
     * Get automated orders collection.
     *
     * @return ScheduleCollection
     */
    public function getOrders(): ScheduleCollection
    {
        return $this->getOrders->execute(null);
    }

    /**
     * Get raw product lists for the dropdown.
     *
     * @return ProductListCollection
     */
    public function getProductLists(): ProductListCollection
    {
        $customerId = $this->customerSession->getCustomerId();
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->setOrder('created_at', 'DESC');

        return $collection;
    }

    /**
     * Get available offline payment methods.
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
                $methods[] = ['code' => $code, 'title' => $method->getTitle()];
            }
        }
        return $methods;
    }

    /**
     * Get available offline shipping methods.
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
            $methods[] = ['code' => $fullCode, 'title' => $carrierTitle . ' - ' . $methodTitle];
        }
        return $methods;
    }

    /**
     * Get customer address collection.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getCustomerAddresses(): array
    {
        try {
            $customer = $this->customerSession->getCustomer();
            if (!$customer || !$customer->getId()) {
                return [];
            }

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('parent_id', $customer->getId(), 'eq')->create();

            $searchResults = $this->addressRepository->getList($searchCriteria);
            return $searchResults->getItems();
        } catch (NoSuchEntityException| LocalizedException $e) {
            return [];
        }
    }

    /**
     * Get formatted address label for dropdowns.
     *
     * @param AddressInterface $address
     * @return string
     */
    public function getAddressLabel(AddressInterface $address): string
    {
        $street = is_array($address->getStreet()) ? implode(', ', $address->getStreet()) : $address->getStreet();
        $region = is_object($address->getRegion()) ? $address->getRegion()->getRegion() : $address->getRegion();

        return $address->getFirstname() . ' ' . $address->getLastname()
            . ', ' . $street . ', ' . $address->getCity() . ', ' . $region . ' ' . $address->getPostcode();
    }

    /**
     * Retrieve default shipping address ID.
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
     * Retrieve default billing address ID.
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
     * Get store timezone code.
     *
     * @return string
     */
    public function getStoreTimezone(): string
    {
        return $this->timezone->getConfigTimezone();
    }

    /**
     * Get timezone options array.
     *
     * @return array
     */
    public function getTimezones(): array
    {
        return $this->timezoneSource->toOptionArray();
    }

    /**
     * Get human-readable label for a frequency code.
     *
     * @param string|null $code
     * @return string
     */
    public function getFrequencyLabel(?string $code): string
    {
        if (!$code) {
            return '';
        }
        return $this->frequencySource->getLabel($code);
    }

    /**
     * Retrieve an array of frequency options for the creation form dropdown.
     *
     * @return array
     */
    public function getFrequencyOptions(): array
    {
        return $this->frequencySource->toOptionArray();
    }

    /**
     * Format date from UTC to schedule timezone.
     *
     * @param string|null $date
     * @param string $timezoneCode
     * @return string
     */
    public function formatScheduleDate(?string $date, string $timezoneCode): string
    {
        if (!$date) {
            return '';
        }
        try {
            $dateTime = new \DateTime($date, new \DateTimeZone('UTC'));
            $dateTime->setTimezone(new \DateTimeZone($timezoneCode));
            return $dateTime->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return $date;
        }
    }
}
