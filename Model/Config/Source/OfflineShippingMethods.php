<?php
/**
 * Copyright (c) 2025 BroSolutions
 * All rights reserved
 *
 * This product includes proprietary software developed at BroSolutions, Ukraine
 * For more information see https://www.brosolutions.net/
 *
 * To obtain a valid license for using this software please contact us at
 * contact@brosolutions.net
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Config as ShippingConfig;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class OfflineShippingMethods implements OptionSourceInterface
{
    /**
     * @var ShippingConfig
     */
    private $shippingConfig;

    /**
     * @param ShippingConfig $shippingConfig
     */
    public function __construct(
        ShippingConfig $shippingConfig
    ) {
        $this->shippingConfig = $shippingConfig;
    }

    /**
     * Options to array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];

        $carriers = $this->shippingConfig->getActiveCarriers();

        foreach ($carriers as $carrierCode => $carrierModel) {

            if ($carrierModel instanceof AbstractCarrierOnline) {
                continue;
            }

            $carrierTitle = $carrierModel->getConfigData('title');
            $allowedMethods = $carrierModel->getAllowedMethods();

            if (!$allowedMethods) {
                continue;
            }

            foreach ($allowedMethods as $methodCode => $methodTitle) {
                $options[] = [
                    'value' => $carrierCode . '_' . $methodCode,
                    'label' => $carrierTitle . ' - ' . $methodTitle
                ];
            }
        }

        return $options;
    }
}
