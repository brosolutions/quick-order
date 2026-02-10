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

namespace BroSolutions\QuickOrder\Block\Customer;

use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\Collection;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\CollectionFactory as ProductListCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Framework\Phrase;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ProductLists extends Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     * @var array
     */
    protected $lists;

    /**
     * @var ProductListCollectionFactory
     */
    private $productListCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param ProductListCollectionFactory $productListCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context                      $context,
        Session                      $customerSession,
        ProductListCollectionFactory $productListCollectionFactory,
        StoreManagerInterface $storeManager,
        array                        $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->productListCollectionFactory = $productListCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('My Orders'));
    }

    /**
     * Get lists
     *
     * @return Collection|bool
     * @throws NoSuchEntityException
     */
    public function getLists() : Collection|bool
    {
        if (!($customerId = $this->_customerSession->getCustomerId())) {
            return false;
        }
        if (!$this->lists) {
            $collection = $this->productListCollectionFactory->create();

            $collection
                ->addFieldToSelect('list_name')
                ->addFieldToSelect('id')
                ->addFieldToSelect('created_at')
                ->addFieldToFilter('store_id', (int)$this->storeManager->getStore()->getId())
                ->addFieldToFilter('customer_id', $customerId);
            $collection->setOrder('created_at', 'DESC');

            $this->lists = $collection;
        }

        return $this->lists;
    }

    /**
     * Get Pager child block output
     *
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * Get order view URL
     *
     * @param object $list
     * @return string
     */
    public function getViewUrl($list)
    {
        return $this->getUrl('customer/account/productlistview', ['list_id' => $list->getId()]);
    }

    /**
     * Get reorder URL
     *
     * @param object $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        return $this->getUrl('sales/order/reorder', ['order_id' => $order->getId()]);
    }

    /**
     * Get customer account URL
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('customer/account/');
    }

    /**
     * Get message for no orders.
     *
     * @return Phrase
     */
    public function getEmptyListMessage()
    {
        return __('You have placed no product lists.');
    }
}
