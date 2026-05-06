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

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class DeleteButton implements ButtonProviderInterface
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Retrieve button configuration data
     *
     * @return array
     */
    public function getButtonData(): array
    {
        $data = [];
        $scheduleId = $this->getScheduleId();
        if ($scheduleId) {
            $data = [
                'label' => __('Delete Schedule'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to delete this schedule?'
                ) . '\', \'' . $this->getDeleteUrl() . '\', {"data": {}})',
                'sort_order' => 20,
            ];
        }
        return $data;
    }

    /**
     * Get URL for delete action
     *
     * @return string
     */
    public function getDeleteUrl(): string
    {
        return $this->context->getUrlBuilder()
            ->getUrl('*/*/delete', ['schedule_id' => $this->getScheduleId()]);
    }

    /**
     * Get schedule ID
     *
     * @return int|null
     */
    public function getScheduleId(): ?int
    {
        $scheduleId = $this->context->getRequest()->getParam('schedule_id');
        return $scheduleId ? (int)$scheduleId : null;
    }
}
