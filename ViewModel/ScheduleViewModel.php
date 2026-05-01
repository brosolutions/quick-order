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

namespace BroSolutions\QuickOrder\ViewModel;

use BroSolutions\QuickOrder\Model\Config\Source\Frequency;
use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule\Collection;
use BroSolutions\QuickOrder\Service\GetOrders;
use Exception;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Payment\Model\Config as PaymentConfig;

/**
 * ViewModel for rendering Schedule data.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ScheduleViewModel implements ArgumentInterface
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
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CountryFactory
     */
    private $countryFactory;

    /**
     * @var Frequency
     */
    private $frequencySource;

    /**
     * Constructor
     *
     * @param GetOrders $getOrders
     * @param PaymentConfig $paymentConfig
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     * @param CountryFactory $countryFactory
     * @param Frequency $frequencySource
     */
    public function __construct(
        GetOrders $getOrders,
        PaymentConfig $paymentConfig,
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        CountryFactory $countryFactory,
        Frequency $frequencySource
    ) {
        $this->getOrders = $getOrders;
        $this->paymentConfig = $paymentConfig;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->countryFactory = $countryFactory;
        $this->frequencySource = $frequencySource;
    }

    /**
     * Get schedule order collection by schedule ID from the request.
     *
     * @return Collection
     */
    public function getOrder(): Collection
    {
        $scheduleId = $this->request->getParam('schedule_id');
        return $this->getOrders->execute((int)$scheduleId);
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

            $carrierTitle = $this->scopeConfig->getValue("carriers/{$carrierCode}/title");
            $methodTitle = $this->scopeConfig->getValue("carriers/{$carrierCode}/name");

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
