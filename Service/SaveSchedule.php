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

use BroSolutions\QuickOrder\Model\AutomaticScheduleFactory;
use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class SaveSchedule
{
    /**
     * @var array
     */
    private const REQUIRED_FIELDS = [
        'list_id',
        'payment_method',
        'billing_address',
        'shipping_address',
        'shipping_method',
        'frequency',
        'start_at'
    ];

    /**
     * @var AutomaticScheduleFactory
     */
    private $scheduleFactory;

    /**
     * @var AutomaticSchedule
     */
    private $resource;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param AutomaticScheduleFactory $scheduleFactory
     * @param AutomaticSchedule $resource
     * @param DateTime $dateTime
     */
    public function __construct(
        AutomaticScheduleFactory $scheduleFactory,
        AutomaticSchedule $resource,
        DateTime $dateTime
    ) {
        $this->scheduleFactory = $scheduleFactory;
        $this->resource = $resource;
        $this->dateTime = $dateTime;
    }

    /**
     * Save schedule
     *
     * @param array $data
     * @return void
     * @throws AlreadyExistsException
     * @throws LocalizedException
     */
    public function execute(array $data) :void
    {

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!isset($data[$field])) {
                throw new LocalizedException(
                    __('Missing required field: %1', $field)
                );
            }
        }

        $startTimestamp = strtotime($data['start_at']);

        if (!$startTimestamp) {
            throw new LocalizedException(__('Invalid start_at date'));
        }

        switch ($data['frequency']) {

            case 'weekly':
                $nextTimestamp = strtotime('+1 week', $startTimestamp);
                break;

            case 'monthly':
                $nextTimestamp = strtotime('+1 month', $startTimestamp);
                break;

            case 'monthly_n':
                if (empty($data['frequency_value']) || (int)$data['frequency_value'] < 1) {
                    throw new LocalizedException(
                        __('Frequency value must be greater than 0 for monthly_n')
                    );
                }

                $months = (int)$data['frequency_value'];
                $nextTimestamp = strtotime('+' . $months . ' month', $startTimestamp);
                break;

            default:
                throw new LocalizedException(__('Invalid frequency type'));
        }

        $nextRunAt = $this->dateTime->gmtDate(
            'Y-m-d H:i:s',
            $nextTimestamp
        );

        $model = $this->scheduleFactory->create();

        $model->setData([
            'list_id'          => (int)$data['list_id'],
            'shipping_address' => $data['shipping_address'],
            'billing_address'  => $data['billing_address'],
            'shipping_method'  => $data['shipping_method'],
            'payment_method'   => $data['payment_method'],
            'frequency'        => $data['frequency'],
            'frequency_value'  => $data['frequency_value'] ?? null,
            'start_at'         => $this->dateTime->gmtDate('Y-m-d H:i:s', $startTimestamp),
            'timezone'         => $data['timezone'],
            'next_run_at'      => $nextRunAt,
            'last_run_at'      => null,
            'status'           => AutomaticSchedule::STATUS_ACTIVE
        ]);

        $this->resource->save($model);
    }
}
