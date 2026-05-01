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

use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule\Collection;
use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule\CollectionFactory;
use Magento\Customer\Model\Session;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class GetOrders
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @param CollectionFactory $collectionFactory
     * @param Session $customerSession
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Session $customerSession
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * Get order collection
     *
     * @param int|null $scheduleId
     * @return Collection
     */
    public function execute(?int $scheduleId): Collection
    {
        $customerId = $this->customerSession->getCustomerId();
        $collection = $this->collectionFactory->create();

        $collection->addFieldToFilter('list.customer_id', $customerId);
        $collection->setOrder('main_table.created_at', 'DESC');

        if ($scheduleId) {
            $collection->addFieldToFilter('main_table.schedule_id', ['eq' => $scheduleId]);
        }

        return $collection;
    }
}
