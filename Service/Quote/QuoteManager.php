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

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Handles the initialization and configuration of the quote object.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class QuoteManager
{
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $cartRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * Initializes an empty quote for the customer.
     *
     * @param CustomerInterface $customer
     * @param int $storeId
     * @return Quote
     * @throws LocalizedException
     */
    public function initializeQuote(CustomerInterface $customer, int $storeId): Quote
    {
        $cartId = $this->cartManagement->createEmptyCart();

        /** @var Quote $quote */
        $quote = $this->cartRepository->get($cartId);
        $quote->setStoreId($storeId);

        $currencyCode = $this->storeManager->getStore($storeId)->getBaseCurrencyCode();
        $quote->setQuoteCurrencyCode($currencyCode);
        $quote->setBaseCurrencyCode($currencyCode);
        $quote->setStoreCurrencyCode($currencyCode);
        $quote->setGlobalCurrencyCode($currencyCode);

        $quote->setCustomerId($customer->getId());
        $quote->setCustomerEmail($customer->getEmail());
        $quote->setCustomerFirstname($customer->getFirstname());
        $quote->setCustomerLastname($customer->getLastname());
        $quote->setCustomerGroupId($customer->getGroupId());
        $quote->setCustomerIsGuest(false);

        return $quote;
    }

    /**
     *  Applies shipping, billing, and payment configurations.
     *
     * @param Quote $quote
     * @param CustomerInterface $customer
     * @param array $shippingData
     * @param array $billingData
     * @param string $shippingMethod
     * @param string $paymentMethod
     * @return void
     * @throws LocalizedException
     */
    public function applyAddressesAndPayment(
        Quote $quote,
        CustomerInterface $customer,
        array $shippingData,
        array $billingData,
        string $shippingMethod,
        string $paymentMethod
    ): void {
        $billingAddress = $quote->getBillingAddress();
        $billingAddress->setData('should_ignore_validation', true);
        $billingAddress->addData($this->formatAddressData($billingData, $customer));

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setData('should_ignore_validation', true);
        $shippingAddress->addData($this->formatAddressData($shippingData, $customer));

        $shippingAddress->setCollectShippingRates(true)->setShippingMethod($shippingMethod);

        $quote->setInventoryProcessed(false);
        $quote->getPayment()->importData(['method' => $paymentMethod]);

        $quote->collectTotals();
        $this->cartRepository->save($quote);
    }

    /**
     * Formats raw address arrays.
     *
     * @param array $data
     * @param CustomerInterface $customer
     * @return array
     */
    private function formatAddressData(array $data, CustomerInterface $customer): array
    {
        return [
            'firstname'  => !empty($data['firstname']) ? $data['firstname'] : ($customer->getFirstname() ?: 'N/A'),
            'lastname'   => !empty($data['lastname']) ? $data['lastname'] : ($customer->getLastname() ?: 'N/A'),
            'company'    => !empty($data['company']) ? $data['company'] : 'N/A',
            'street'     => !empty($data['street']) ? (is_array($data['street']) ? $data['street']
                : [$data['street']]) : ['N/A'],
            'city'       => !empty($data['city']) ? $data['city'] : 'N/A',
            'region'     => $data['region'] ?? '',
            'region_id'  => $data['region_id'] ?? null,
            'postcode'   => !empty($data['postcode']) ? $data['postcode'] : '00000',
            'country_id' => !empty($data['country_id']) ? strtoupper((string)$data['country_id']) : 'US',
            'telephone'  => !empty($data['telephone']) ? $data['telephone'] : '0000000000',
            'email'      => $customer->getEmail(),
            'customer_id'=> $customer->getId(),
            'same_as_billing' => 0,
            'save_in_address_book' => 0
        ];
    }
}
