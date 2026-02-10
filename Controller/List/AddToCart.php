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

use BroSolutions\QuickOrder\Model\CartManagement;
use BroSolutions\QuickOrder\Model\ProductList;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\CollectionFactory;
use BroSolutions\QuickOrder\Service\CreateDataAddListToCart;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class AddToCart implements HttpPostActionInterface
{

    /**
     * @var Http
     */
    private $request;

    /**
     * @var CartManagement
     */
    private $cartManagement;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

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
     * @var CreateDataAddListToCart
     */
    private $createDataAddListToCart;

    /**
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param FormKeyValidator $formKeyValidator
     * @param CartManagement $cartManagement
     * @param RedirectFactory $redirectFactory
     * @param CollectionFactory $collectionFactory
     * @param CustomerSession $customerSession
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     * @param CreateDataAddListToCart $createDataAddListToCart
     */
    public function __construct(
        Http             $request,
        JsonFactory      $resultJsonFactory,
        FormKeyValidator $formKeyValidator,
        CartManagement   $cartManagement,
        RedirectFactory $redirectFactory,
        CollectionFactory $collectionFactory,
        CustomerSession $customerSession,
        ManagerInterface $messageManager,
        LoggerInterface            $logger,
        CreateDataAddListToCart $createDataAddListToCart
    ) {
        $this->request = $request;
        $this->cartManagement = $cartManagement;
        $this->redirectFactory = $redirectFactory;
        $this->collectionFactory = $collectionFactory;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->createDataAddListToCart = $createDataAddListToCart;
    }

    /**
     * Add to cart
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->redirectFactory->create();
        $listId = (int)$this->request->getParam('list_id');
        try {
            if (!$this->customerSession->isLoggedIn()) {
                return $resultRedirect->setPath('customer/account/login');
            }

            if (!$listId) {
                throw new LocalizedException(__('Invalid list.'));
            }

            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('id', $listId);

            /** @var ProductList $list */
            $list = $collection->getFirstItem();

            if ((int)$list->getCustomerId() !== (int)$this->customerSession->getCustomerId()) {
                throw new LocalizedException(__('You are not allowed to delete this list.'));
            }

            if (!$listId = $list->getId()) {
                throw new LocalizedException(__('The list no longer exists.'));
            }

            $productsData = $this->createDataAddListToCart->execute((int)$listId);

            $this->cartManagement->addToCart($productsData);

            $this->messageManager->addSuccessMessage(__('The list has been added to the cart.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath(
                'customer/account/productlistview',
                ['list_id' => $listId]
            );
        } catch (Throwable $e) {
            $this->logger->error(sprintf('Error deleting list: %s', $e->getMessage()), $e->getTrace());
            $this->messageManager->addErrorMessage(
                __('Something went wrong while adding to the cart.')
            );
            return $resultRedirect->setPath(
                'customer/account/productlistview',
                ['list_id' => $listId]
            );
        }

        return $resultRedirect->setPath('checkout/cart/');
    }
}
