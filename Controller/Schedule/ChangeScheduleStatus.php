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

namespace BroSolutions\QuickOrder\Controller\Schedule;

use BroSolutions\QuickOrder\Model\AutomaticSchedule;
use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule as ScheduleResource;
use BroSolutions\QuickOrder\Service\GetOrders;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Controller to change schedule status securely.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ChangeScheduleStatus implements HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var GetOrders
     */
    private $getOrders;

    /**
     * @var ScheduleResource
     */
    private $scheduleResource;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     * @param RedirectFactory $redirectFactory
     * @param GetOrders $getOrders
     * @param ScheduleResource $scheduleResource
     * @param CustomerSession $customerSession
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     * @param FormKeyValidator $formKeyValidator
     */
    public function __construct(
        RequestInterface $request,
        RedirectFactory $redirectFactory,
        GetOrders $getOrders,
        ScheduleResource $scheduleResource,
        CustomerSession $customerSession,
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        FormKeyValidator $formKeyValidator
    ) {
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->getOrders = $getOrders;
        $this->scheduleResource = $scheduleResource;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->formKeyValidator = $formKeyValidator;
    }

    /**
     * Execute action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->redirectFactory->create();
        $scheduleId = (int)$this->request->getParam('schedule_id');

        try {
            if (!$this->customerSession->isLoggedIn()) {
                return $resultRedirect->setPath('customer/account/login');
            }

            if (!$this->formKeyValidator->validate($this->request)) {
                throw new LocalizedException(__('Invalid Form Key. Please refresh the page.'));
            }

            $status = trim((string)$this->request->getParam('status'));

            $allowedCustomerStatuses = [
                ScheduleResource::STATUS_ACTIVE,
                ScheduleResource::STATUS_PAUSED
            ];

            if (!$scheduleId || !in_array($status, $allowedCustomerStatuses, true)) {
                throw new LocalizedException(__('Invalid schedule or status.'));
            }

            $collection = $this->getOrders->execute($scheduleId);

            /** @var AutomaticSchedule $schedule */
            $schedule = $collection->getFirstItem();

            if (!$schedule->getId()) {
                throw new LocalizedException(__('The schedule no longer exists or you do not have permission.'));
            }

            if ($schedule->getStatus() === ScheduleResource::STATUS_DISABLED) {
                throw new LocalizedException(
                    __('This schedule has been disabled by the administrator and cannot be modified.')
                );
            }

            $schedule->setStatus($status);
            $this->scheduleResource->save($schedule);

            $this->messageManager->addSuccessMessage(__('The schedule status has been updated.'));

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf('Error updating schedule status: %s', $e->getMessage()),
                $e->getTrace()
            );
            $this->messageManager->addErrorMessage(
                __('Something went wrong while updating the schedule status.')
            );
        }

        return $resultRedirect->setPath(
            'customer/account/scheduleview',
            ['schedule_id' => $scheduleId]
        );
    }
}
