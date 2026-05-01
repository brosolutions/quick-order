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

namespace BroSolutions\QuickOrder\Controller\Account;

use BroSolutions\QuickOrder\Service\GetOrders;
use BroSolutions\QuickOrder\Service\GetScheduledAutomatedOrdersEnabled;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ScheduleView extends AbstractAccount implements HttpGetActionInterface
{
    /**
     * @var GetScheduledAutomatedOrdersEnabled
     */
    private $getScheduledAutomatedOrdersEnabled;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var GetOrders
     */
    private $getOrders;

    /**
     * @param GetScheduledAutomatedOrdersEnabled $getScheduledAutomatedOrdersEnabled
     * @param PageFactory $resultPageFactory
     * @param Context $context
     * @param RequestInterface $request
     * @param GetOrders $getOrders
     */
    public function __construct(
        GetScheduledAutomatedOrdersEnabled $getScheduledAutomatedOrdersEnabled,
        PageFactory $resultPageFactory,
        Context         $context,
        RequestInterface $request,
        GetOrders $getOrders,
    ) {
        $this->getScheduledAutomatedOrdersEnabled = $getScheduledAutomatedOrdersEnabled;
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->getOrders = $getOrders;
        parent::__construct($context);
    }

    /**
     * Get schedule page
     *
     * @ingeritdoc
     */
    public function execute(): ?Page
    {
        if (!$this->getScheduledAutomatedOrdersEnabled->execute()) {
            $this->messageManager->addErrorMessage(
                __('Scheduled automated orders module is not enabled.')
            );
            $this->_redirect('*/*/index');
        }

        $scheduleId = (int)$this->request->getParam('schedule_id');
        $collection = $this->getOrders->execute($scheduleId);
        $schedule = $collection->getLastItem();

        if (!$scheduleId || empty($schedule)) {
            $this->_redirect('*/*/index');
        }

        $resultPage = $this->resultPageFactory->create();

        $resultPage->getConfig()
            ->getTitle()
            ->set(__(
                'Schedule for: %1',
                $schedule['list_name']
            ));

        return $resultPage;
    }
}
