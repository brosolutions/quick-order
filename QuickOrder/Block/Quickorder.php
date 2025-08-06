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

namespace BroSolutions\QuickOrder\Block;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class Quickorder extends Template
{

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @param Context $context
     * @param StoreManager $storeManager
     * @param FormKey $formKey
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreManager $storeManager,
        FormKey $formKey,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeManager = $storeManager;
        $this->formKey = $formKey;
    }

    /**
     * @inheritdoc
     */
    protected function _addBreadcrumbs()
    {
        $breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs');

        if ($breadcrumbsBlock) {
            $breadcrumbsBlock->addCrumb(
                'home',
                [
                    'label' => __('Home'),
                    'title' => __('Go to Home Page'),
                    'link'  => $this->storeManager->getStore()->getBaseUrl()
                ]
            );

            $breadcrumbsBlock->addCrumb(
                'quickorder',
                [
                    'label' =>__('Quick Order'),
                    'title' => __('Quick Order'),
                    'link'  => ''
                ]
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
        $this->_addBreadcrumbs();
        $this->pageConfig->addBodyClass('quick-order-page');

        return parent::_prepareLayout();
    }

    /**
     * Get store code
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }

    /**
     * Get quick order search product url
     *
     * @return string
     */
    public function getQuickOrderSearchProductUrl()
    {
        return $this->getUrl('rest/'. $this->storeManager->getStore()->getCode() .'/V1/bro-solutions-quick-search/search-product');
    }

    /**
     * Get quick order get product url
     *
     * @return string
     */
    public function getQuickOrderGetProductUrl()
    {
        return $this->getUrl('rest/'. $this->storeManager->getStore()->getCode() .'/V1/bro-solutions-quick-search/get-product');
    }

    /**
     * Get media product path
     *
     * @return string
     */
    public function getMediaProductPath()
    {
        return $this->getMediaUrl() . '/catalog/product/';
    }

    /**
     * Get add to cart url
     *
     * @return string
     */
    public function getAddToCartUrl()
    {
        return $this->getUrl('quickorder/index/addtocart');
    }

    /**
     * Get upload file url
     *
     * @return string
     */
    public function getUploadFileUrl()
    {
        return $this->getUrl('quickorder/index/upload');
    }

    /**
     * Get form key
     *
     * @return string
     * @throws LocalizedException
     */
    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Get media url
     *
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Get url to cart redirect
     *
     * @return string
     */
    public function getUrlToCartRedirect()
    {
        return $this->getUrl('checkout/cart');
    }
}
