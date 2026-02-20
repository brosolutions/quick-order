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
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class AutomatedOrders extends AbstractAccount implements HttpGetActionInterface
{
    /**
     * @var GetScheduledAutomatedOrdersEnabled
     */
    private $getScheduledAutomatedOrdersEnabled;

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @param GetScheduledAutomatedOrdersEnabled $getScheduledAutomatedOrdersEnabled
     * @param PageFactory $pageFactory
     * @param Context $context
     */
    public function __construct(
        GetScheduledAutomatedOrdersEnabled $getScheduledAutomatedOrdersEnabled,
        PageFactory $pageFactory,
        Context $context,
    ) {
        $this->getScheduledAutomatedOrdersEnabled = $getScheduledAutomatedOrdersEnabled;
        $this->pageFactory = $pageFactory;
        parent::__construct($context);
    }

    /**
     * Get automated orders
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

        $resultPage = $this->pageFactory->create();
        $resultPage->getConfig()
            ->getTitle()
            ->set(__('Scheduled automated orders'));

        return $resultPage;
    }
}
