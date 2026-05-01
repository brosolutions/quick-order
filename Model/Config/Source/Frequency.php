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

namespace BroSolutions\QuickOrder\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class Frequency implements OptionSourceInterface
{
    /**
     * @var string
     */
    public const WEEKLY = 'weekly';

    /**
     * @var string
     */
    public const MONTHLY = 'monthly';

    /**
     * @var string
     */
    public const MONTHLY_N = 'monthly_n';

    /**
     * To option array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::WEEKLY, 'label' => __('Once per week')],
            ['value' => self::MONTHLY, 'label' => __('Once per month')],
            ['value' => self::MONTHLY_N, 'label' => __('Every N months')],
        ];
    }

    /**
     * Get label by value
     *
     * @param string $value
     * @return string
     */
    public function getLabel(string $value): string
    {
        foreach ($this->toOptionArray() as $option) {
            if ($option['value'] === $value) {
                return (string)$option['label'];
            }
        }
        return $value;
    }
}
