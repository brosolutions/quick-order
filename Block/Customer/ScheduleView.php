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
use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule\Collection;
use BroSolutions\QuickOrder\Model\ResourceModel\History\Collection as HistoryCollection;
use BroSolutions\QuickOrder\Model\ResourceModel\History\CollectionFactory as HistoryCollectionFactory;
use BroSolutions\QuickOrder\Service\GetOrders;
use Exception;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Model\Config as PaymentConfig;

/**
 * Block for rendering Schedule data.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ScheduleView extends Template
{
    /**
     * @var GetOrders
     */
    private $getOrders;

    /**
     * @var PaymentConfig
     */
    private $paymentConfig;

    /**
     * @var CountryFactory
     */
    private $countryFactory;

    /**
     * @var Frequency
     */
    private $frequencySource;

    /**
     * @var HistoryCollectionFactory
     */
    private $historyCollectionFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param GetOrders $getOrders
     * @param PaymentConfig $paymentConfig
     * @param CountryFactory $countryFactory
     * @param Frequency $frequencySource
     * @param HistoryCollectionFactory $historyCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        GetOrders $getOrders,
        PaymentConfig $paymentConfig,
        CountryFactory $countryFactory,
        Frequency $frequencySource,
        HistoryCollectionFactory $historyCollectionFactory,
        array $data = []
    ) {
        $this->getOrders = $getOrders;
        $this->paymentConfig = $paymentConfig;
        $this->countryFactory = $countryFactory;
        $this->frequencySource = $frequencySource;
        $this->historyCollectionFactory = $historyCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get schedule order collection by schedule ID from the request.
     *
     * @return Collection
     */
    public function getOrder(): Collection
    {
        $scheduleId = $this->getRequest()->getParam('schedule_id');
        return $this->getOrders->execute((int)$scheduleId);
    }

    /**
     * Get execution logs for the current schedule
     *
     * @param int $scheduleId
     * @return HistoryCollection
     */
    public function getScheduleLogs(int $scheduleId): HistoryCollection
    {
        $collection = $this->historyCollectionFactory->create();
        $collection->addFieldToFilter('schedule_id', $scheduleId);
        $collection->setOrder('created_at', 'DESC');
        return $collection;
    }

    /**
     * Retrieve address details as an array for flexible rendering from Snapshot.
     *
     * @param string|null $addressSnapshotJson
     * @return array
     */
    public function getAddressData(?string $addressSnapshotJson): array
    {
        if (!$addressSnapshotJson) {
            return [];
        }

        try {
            $address = json_decode($addressSnapshotJson, true);
            if (!is_array($address)) {
                return [];
            }

            $street = is_array($address['street']) ? $address['street'] : [$address['street']];

            $countryName = $address['country_id'] ?? '';
            if ($countryName) {
                $country = $this->countryFactory->create()->loadByCode($countryName);
                if ($country->getId()) {
                    $countryName = $country->getName();
                }
            }

            return [
                'name'      => trim(($address['firstname'] ?? '') . ' ' . ($address['lastname'] ?? '')),
                'company'   => $address['company'] ?? '',
                'street'    => $street,
                'city'      => $address['city'] ?? '',
                'region'    => $address['region'] ?? '',
                'postcode'  => $address['postcode'] ?? '',
                'country'   => $countryName,
                'telephone' => $address['telephone'] ?? '',
            ];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get the full title of the payment method by its code.
     *
     * @param string|null $code
     * @return string
     */
    public function getPaymentMethodTitle(?string $code): string
    {
        if (!$code) {
            return '';
        }
        $methods = $this->paymentConfig->getActiveMethods();
        return isset($methods[$code]) ? $methods[$code]->getTitle() : $code;
    }

    /**
     * Get the full title of the shipping method by its full code.
     *
     * @param string|null $fullCode
     * @return string
     */
    public function getShippingMethodTitle(?string $fullCode): string
    {
        if (!$fullCode) {
            return '';
        }

        $parts = explode('_', $fullCode, 2);
        if (count($parts) === 2) {
            $carrierCode = $parts[0];

            $carrierTitle = $this->_scopeConfig->getValue("carriers/{$carrierCode}/title");
            $methodTitle = $this->_scopeConfig->getValue("carriers/{$carrierCode}/name");

            if ($carrierTitle) {
                return $methodTitle ? $carrierTitle . ' - ' . $methodTitle : $carrierTitle;
            }
        }

        return $fullCode;
    }

    /**
     * Get human-readable label for frequency code
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
