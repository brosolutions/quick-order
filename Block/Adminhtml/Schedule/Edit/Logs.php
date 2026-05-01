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

namespace BroSolutions\QuickOrder\Block\Adminhtml\Schedule\Edit;

use BroSolutions\QuickOrder\Model\ResourceModel\History\Collection;
use BroSolutions\QuickOrder\Model\ResourceModel\History\CollectionFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Block for displaying schedule execution logs in the admin panel.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class Logs extends Template
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve logs for the current schedule.
     *
     * @return Collection
     */
    public function getLogs(): Collection
    {
        $scheduleId = (int)$this->getRequest()->getParam('schedule_id');
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('schedule_id', $scheduleId);
        $collection->setOrder('created_at', 'DESC');

        return $collection;
    }
}
