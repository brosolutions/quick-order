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

namespace BroSolutions\QuickOrder\Cron;

use BroSolutions\QuickOrder\Exception\PauseScheduleException;
use BroSolutions\QuickOrder\Model\AutomaticSchedule;
use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule as ScheduleResource;
use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule\CollectionFactory;
use BroSolutions\QuickOrder\Service\CalculateNextRun;
use BroSolutions\QuickOrder\Service\PlaceAutomatedOrder;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Cron class to process automated orders with locking and rule validation.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ProcessAutomatedOrders
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ScheduleResource
     */
    private $scheduleResource;

    /**
     * @var PlaceAutomatedOrder
     */
    private $placeAutomatedOrder;

    /**
     * @var CalculateNextRun
     */
    private $calculateNextRun;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ScheduleResource $scheduleResource
     * @param PlaceAutomatedOrder $placeAutomatedOrder
     * @param CalculateNextRun $calculateNextRun
     * @param DateTime $dateTime
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ScheduleResource $scheduleResource,
        PlaceAutomatedOrder $placeAutomatedOrder,
        CalculateNextRun $calculateNextRun,
        DateTime $dateTime,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->scheduleResource = $scheduleResource;
        $this->placeAutomatedOrder = $placeAutomatedOrder;
        $this->calculateNextRun = $calculateNextRun;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
    }

    /**
     * Process due schedules and create automated orders.
     *
     * @return void
     */
    public function execute(): void
    {
        $currentUtc = $this->dateTime->gmtDate();

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('main_table.status', ScheduleResource::STATUS_ACTIVE);

        $quotedUtc = $collection->getConnection()->quote($currentUtc);
        $collection->getSelect()->where(
            "(main_table.last_run_at IS NULL AND main_table.start_at <= {$quotedUtc})
            OR (main_table.last_run_at IS NOT NULL AND main_table.next_run_at <= {$quotedUtc})"
        );

        /** @var AutomaticSchedule $schedule */
        foreach ($collection as $schedule) {
            try {
                $schedule->setStatus(ScheduleResource::STATUS_PROCESSING);
                $this->scheduleResource->save($schedule);

                $orderId = $this->placeAutomatedOrder->execute($schedule);

                $schedule->setData('last_order_id', $orderId);

                $nextRunAt = (string)$schedule->getNextRunAt();

                while ($nextRunAt <= $currentUtc) {
                    $nextRunAt = $this->calculateNextRun->execute(
                        $nextRunAt,
                        (string)$schedule->getTimezone(),
                        (string)$schedule->getFrequency(),
                        (int)$schedule->getFrequencyValue()
                    );
                }

                $schedule->setLastRunAt($currentUtc);
                $schedule->setNextRunAt($nextRunAt);
                $schedule->setStatus(ScheduleResource::STATUS_ACTIVE);
                $this->scheduleResource->save($schedule);

            } catch (PauseScheduleException $e) {
                $this->logger->info("Pausing Schedule ID {$schedule->getId()}: " . $e->getMessage());

                $schedule->setStatus(ScheduleResource::STATUS_PAUSED);
                try {
                    $this->scheduleResource->save($schedule);
                } catch (Throwable $saveException) {
                    $this->logger->error("Could not update schedule status to paused (ID: " .
                        $schedule->getId() . ")");
                }
            } catch (Throwable $e) {
                $this->logger->error("Automated Schedule Failed (ID: " . $schedule->getId() . "): " .
                    $e->getMessage());

                $schedule->setStatus(ScheduleResource::STATUS_ERROR);

                try {
                    $this->scheduleResource->save($schedule);
                } catch (Throwable $saveException) {
                    $this->logger->error("Could not update schedule status to error (ID: " .
                        $schedule->getId() . ")");
                }
            }
        }
    }
}
