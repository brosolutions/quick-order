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

namespace BroSolutions\QuickOrder\Controller\Adminhtml\Schedule;

use BroSolutions\QuickOrder\Model\AutomaticScheduleFactory;
use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule as ScheduleResource;
use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultInterface;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class Delete extends Action
{
    /**
     * @var string
     */
    public const ADMIN_RESOURCE = 'BroSolutions_QuickOrder::automated_orders';

    /**
     * @var AutomaticScheduleFactory
     */
    private $scheduleFactory;

    /**
     * @var ScheduleResource
     */
    private $scheduleResource;

    /**
     * @param Action\Context $context
     * @param AutomaticScheduleFactory $scheduleFactory
     * @param ScheduleResource $scheduleResource
     */
    public function __construct(
        Action\Context $context,
        AutomaticScheduleFactory $scheduleFactory,
        ScheduleResource $scheduleResource
    ) {
        parent::__construct($context);
        $this->scheduleFactory = $scheduleFactory;
        $this->scheduleResource = $scheduleResource;
    }

    /**
     * Delete action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('schedule_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $model = $this->scheduleFactory->create();
                $this->scheduleResource->load($model, $id);

                if ($model->getId()) {
                    $this->scheduleResource->delete($model);
                    $this->messageManager->addSuccessMessage(__('You have successfully deleted the schedule.'));
                    return $resultRedirect->setPath('*/*/');
                }
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['schedule_id' => $id]);
            }
        }

        $this->messageManager->addErrorMessage(__('We can\'t find a schedule to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}
