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

use BroSolutions\QuickOrder\Service\GetListNameById;
use BroSolutions\QuickOrder\Service\GetScheduledAutomatedOrdersEnabled;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ProductListView extends AbstractAccount implements HttpGetActionInterface
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
     * @var GetListNameById
     */
    private $getListNameById;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param GetScheduledAutomatedOrdersEnabled $getScheduledAutomatedOrdersEnabled
     * @param PageFactory $resultPageFactory
     * @param Context $context
     * @param GetListNameById $getListNameById
     * @param RequestInterface $request
     */
    public function __construct(
        GetScheduledAutomatedOrdersEnabled $getScheduledAutomatedOrdersEnabled,
        PageFactory $resultPageFactory,
        Context         $context,
        GetListNameById $getListNameById,
        RequestInterface $request,
    ) {
        $this->getScheduledAutomatedOrdersEnabled = $getScheduledAutomatedOrdersEnabled;
        $this->resultPageFactory = $resultPageFactory;
        $this->getListNameById = $getListNameById;
        $this->request = $request;
        parent::__construct($context);
    }

    /**
     * My lists page
     *
     * @ingeritdoc
     */
    public function execute()
    {
        if (!$this->getScheduledAutomatedOrdersEnabled->execute()) {
            $this->messageManager->addErrorMessage(
                __('Scheduled automated orders module is not enabled.')
            );
            $this->_redirect('*/*/index');
        }

        $listId = (int)$this->request->getParam('list_id');
        $listName = $this->getListNameById->execute($listId);

        if (!$listId || !$listName) {
            $this->_redirect('*/*/index');
        }

        $resultPage = $this->resultPageFactory->create();

        $resultPage->getConfig()
            ->getTitle()
            ->set(__(
                'Product list view: %1',
                $listName
            ));

        return $resultPage;
    }
}
