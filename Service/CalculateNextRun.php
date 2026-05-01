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

use Exception;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class CalculateNextRun
{
    /**
     * Calculate next run time
     *
     * @param string $currentUtcDate
     * @param string $timezone
     * @param string $frequency
     * @param int|null $frequencyValue
     * @return string
     * @throws Exception
     */
    public function execute(string $currentUtcDate, string $timezone, string $frequency, ?int $frequencyValue): string
    {
        $date = new \DateTime($currentUtcDate, new \DateTimeZone('UTC'));
        $date->setTimezone(new \DateTimeZone($timezone));

        if ($frequency === 'monthly') {
            $date->modify('+1 month');
        } elseif ($frequency === 'monthly_n') {
            $n = $frequencyValue ?: 1;
            $date->modify('+' . $n . ' months');
        } elseif ($frequency === 'weekly') {
            $date->modify('+1 week');
        }

        $date->setTimezone(new \DateTimeZone('UTC'));

        return $date->format('Y-m-d H:i:s');
    }
}
