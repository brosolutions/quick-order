<?php
/**
 * Copyright (c) 2025 BroSolutions
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

use BroSolutions\QuickOrder\Service\GetScheduledAutomatedOrdersEnabled;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\View\Result\Page;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class AutomatedOrders implements HttpGetActionInterface
{
    /**
     * @var GetScheduledAutomatedOrdersEnabled
     */
    private $getScheduledAutomatedOrdersEnabled;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @param GetScheduledAutomatedOrdersEnabled $getScheduledAutomatedOrdersEnabled
     * @param ResultFactory $resultFactory
     * @param PageFactory $pageFactory
     */
    public function __construct(
        GetScheduledAutomatedOrdersEnabled $getScheduledAutomatedOrdersEnabled,
        ResultFactory $resultFactory,
        PageFactory $pageFactory
    ) {
        $this->getScheduledAutomatedOrdersEnabled = $getScheduledAutomatedOrdersEnabled;
        $this->resultFactory = $resultFactory;
        $this->pageFactory = $pageFactory;
    }

    /**
     * Get automated orders
     *
     * @return Redirect|Page
     */
    public function execute():Redirect|Page
    {
        if (!$this->getScheduledAutomatedOrdersEnabled->execute()) {
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('customer/account');
            return $resultRedirect;
        }

        $resultPage = $this->pageFactory->create();
        $resultPage->getConfig()
            ->getTitle()
            ->set(__('Scheduled automated orders'));

        return $resultPage;
    }
}
