<?php
/**
 * Copyright (c) 2024 BroSolutions
 * All rights reserved
 *
 * This product includes proprietary software developed at BroSolutions, Ukraine
 * For more information see https://www.brosolutions.net/
 *
 * To obtain a valid license for using this software please contact us at
 * contact@brosolutions.net
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Controller\Adminhtml\Index;

use BroSolutions\QuickOrder\Service\GetChildBySuperAttributes;
use BroSolutions\QuickOrder\Model\QuickOrderRepository;
use BroSolutions\QuickOrder\Service\SetAddressTelephoneByQuoteId;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Backend\Model\Session\Quote as BackendQuoteSession;
use Magento\Framework\DataObject;
use Magento\Quote\Api\CartManagementInterface;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class Save extends Action
{
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var QuickOrderRepository
     */
    private $quickOrderRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var BackendQuoteSession
     */
    private $backendQuoteSession;

    /**
     * @var GetChildBySuperAttributes
     */
    private $getChildBySuperAttributes;

    /**
     * @var SetAddressTelephoneByQuoteId
     */
    private $setAddressTelephoneByQuoteId;

    /**
     * @param Context $context
     * @param RedirectFactory $resultRedirectFactory
     * @param QuickOrderRepository $quickOrderRepository
     * @param CartRepositoryInterface $cartRepository
     * @param ProductRepositoryInterface $productRepository
     * @param Json $json
     * @param BackendQuoteSession $backendQuoteSession
     * @param GetChildBySuperAttributes $getChildBySuperAttributes
     * @param CartManagementInterface $cartManagement
     */
    public function __construct(
        Context $context,
        RedirectFactory $resultRedirectFactory,
        QuickOrderRepository $quickOrderRepository,
        CartRepositoryInterface $cartRepository,
        ProductRepositoryInterface $productRepository,
        Json $json,
        BackendQuoteSession $backendQuoteSession,
        GetChildBySuperAttributes $getChildBySuperAttributes,
        SetAddressTelephoneByQuoteId $setAddressTelephoneByQuoteId
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->quickOrderRepository = $quickOrderRepository;
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->json = $json;
        $this->backendQuoteSession = $backendQuoteSession;
        $this->getChildBySuperAttributes = $getChildBySuperAttributes;
        $this->setAddressTelephoneByQuoteId = $setAddressTelephoneByQuoteId;
        parent::__construct($context);
    }

    /**
     * @return Redirect
     * @throws NoSuchEntityException|LocalizedException
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $quickOrderEntityId = $this->_request->getParam('entity_id');
        $telephone = $this->_request->getParam('telephone');
        $quickOrderEntity = $this->quickOrderRepository->getById($quickOrderEntityId);
        $requestParamsStr = $quickOrderEntity->getRequestParams();
        $quickOrderEntity->setTelephone($telephone);
        $this->quickOrderRepository->save($quickOrderEntity);
        $requestParams = $this->json->unserialize($requestParamsStr);
        $productId = $quickOrderEntity->getProductId();
        $quote = $this->backendQuoteSession->getQuote();
        $product = $this->productRepository->getById($productId);

        // Configurable Product
        if (isset($requestParams['super_attribute'])) {

            $params = [
                'product' => $productId,
                'super_attribute' => $requestParams['super_attribute'],
                'qty' => $requestParams['qty']
            ];
            $quote->addProduct($product, new DataObject($params));
        //Grouped product
        } elseif (isset($requestParams['super_group'])) {
            $params = [
                'product' => $productId,
                'super_group' => $requestParams['super_group']
            ];
            $quote->addProduct($product, new DataObject($params));

        } elseif (isset($requestParams['bundle_option'])) {
            $params = [
                'product' => $productId,
                'bundle_option' => $requestParams['bundle_option'],
                'bundle_option_qty' => $requestParams['bundle_option_qty'],
            ];
            $quote->addProduct($product, new DataObject($params));

        } else {
            $quote->addProduct($product, new DataObject(['qty' => $requestParams['qty']]));
        }
        $this->cartRepository->save($quote);
        $this->setAddressTelephoneByQuoteId->execute((int)$quote->getId(), $telephone);

        $resultRedirect->setPath('sales/order_create/index', ['quick_order_id' => $quickOrderEntityId]);
        return $resultRedirect;
    }
}
