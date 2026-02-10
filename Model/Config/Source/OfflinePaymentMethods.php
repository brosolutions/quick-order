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
use Magento\Payment\Model\Config as PaymentConfig;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class OfflinePaymentMethods implements OptionSourceInterface
{
    /**
     * @var PaymentConfig
     */
    private $paymentConfig;

    /**
     * @param PaymentConfig $paymentConfig
     */
    public function __construct(
        PaymentConfig $paymentConfig
    ) {
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $options = [];

        /** @var \Magento\Payment\Model\MethodInterface[] $methods */
        $methods = $this->paymentConfig->getActiveMethods();

        foreach ($methods as $code => $method) {
            if ($method->isOffline()) {
                $options[] = [
                    'value' => $code,
                    'label' => $method->getTitle()
                ];
            }
        }

        return $options;
    }
}
