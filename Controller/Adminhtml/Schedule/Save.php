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
use Magento\Backend\App\Action;
use Exception;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class Save extends Action
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
     * Save action
     *
     * @inerhitDoc
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $id = $this->getRequest()->getParam('schedule_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $model = $this->scheduleFactory->create();
            if ($id) {
                $this->scheduleResource->load($model, $id);
            }
            $model->addData($data);
            try {
                $this->scheduleResource->save($model);
                $this->messageManager->addSuccessMessage(__('Schedule saved.'));
                return $resultRedirect->setPath('*/*/');
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
