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

namespace BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule\Source;

use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class Status implements OptionSourceInterface
{
    /**
     * To option array
     *
     * @inerhitDoc
     */
    public function toOptionArray()
    {
        return [
            ['value' => AutomaticSchedule::STATUS_ACTIVE, 'label' => __('Active')],
            ['value' => AutomaticSchedule::STATUS_PAUSED, 'label' => __('Paused')],
            ['value' => AutomaticSchedule::STATUS_DISABLED, 'label' => __('Disabled by Admin')],
            ['value' => AutomaticSchedule::STATUS_ERROR, 'label' => __('Error')]
        ];
    }
}
