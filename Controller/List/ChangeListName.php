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

namespace BroSolutions\QuickOrder\Controller\List;

use BroSolutions\QuickOrder\Model\ProductList;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList as ProductListResource;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\CollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ChangeListName implements HttpPostActionInterface
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
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ProductListResource
     */
    private $resource;

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
     * @param RequestInterface $request
     * @param RedirectFactory $redirectFactory
     * @param CollectionFactory $collectionFactory
     * @param ProductListResource $resource
     * @param CustomerSession $customerSession
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        RedirectFactory $redirectFactory,
        CollectionFactory $collectionFactory,
        ProductListResource $resource,
        CustomerSession $customerSession,
        ManagerInterface $messageManager,
        LoggerInterface            $logger
    ) {
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->redirectFactory->create();
        $listId = (int)$this->request->getParam('list_id');

        try {
            if (!$this->customerSession->isLoggedIn()) {
                return $resultRedirect->setPath('customer/account/login');
            }

            $newName = trim((string)$this->request->getParam('list_name'));

            if (!$listId || $newName === '') {
                $this->messageManager->addErrorMessage(__('Invalid list name.'));
            }

            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('id', $listId);

            /** @var ProductList $list */
            $list = $collection->getFirstItem();

            if ((int)$list->getCustomerId() !== (int)$this->customerSession->getCustomerId()) {
                throw new LocalizedException(__('You are not allowed to edit this list.'));
            }

            if (!$list->getId()) {
                throw new LocalizedException(__('The list no longer exists.'));
            }

            $list->setListName($newName);
            $this->resource->save($list);

            $this->messageManager->addSuccessMessage(__('The list name has been updated.'));

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf('Error updating list name: %s', $e->getMessage()),
                $e->getTrace()
            );
            $this->messageManager->addErrorMessage(
                __('Something went wrong while updating the list name.')
            );
        }

        return $resultRedirect->setPath(
            'customer/account/productlistview',
            ['list_id' => $listId]
        );
    }
}
