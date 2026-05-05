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

namespace BroSolutions\QuickOrder\Block\Item\Renderer;

use BroSolutions\QuickOrder\Model\Item\ProductListItem;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class BundleRenderer extends Template
{
    /**
     * @var StringUtils
     */
    private $string;

    /**
     * @var PricingHelper
     */
    private $pricingHelper;

    /**
     * @param Context $context
     * @param StringUtils $string
     * @param PricingHelper $pricingHelper
     * @param array $data
     */
    public function __construct(
        Context       $context,
        StringUtils   $string,
        PricingHelper $pricingHelper,
        array         $data = []
    ) {
        $this->pricingHelper = $pricingHelper;
        $this->string = $string;
        parent::__construct($context, $data);
    }

    /**
     * @var string
     */
    protected $_template = 'BroSolutions_QuickOrder::item/bundle.phtml';

    /**
     * Set item
     *
     * @param ProductListItem $item
     * @return $this
     */
    public function setItem(ProductListItem $item): self
    {
        $this->setData('item', $item);
        return $this;
    }

    /**
     * Get item
     *
     * @return ProductListItem
     */
    public function getItem(): ProductListItem
    {
        return $this->getData('item');
    }

    /**
     * Get SKU
     *
     * @return string
     */
    public function getSku(): string
    {
        return $this->getItem()->getSku();
    }

    /**
     * Get product name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getItem()->getName();
    }

    /**
     * Prepare SKU
     *
     * @param string $sku
     * @return string
     */
    public function prepareSku(string $sku): string
    {
        return $this->string->splitInjection($sku);
    }

    /**
     * Get item price html
     *
     * @param array $_childItemsOptions
     * @param array $_childItemsQty
     * @return string
     */
    public function getItemPriceHtml(array $_childItemsOptions, array $_childItemsQty): string
    {
        return $this->pricingHelper->currency($this->getItemPrice(
            $_childItemsOptions,
            $_childItemsQty
        ), true, false);
    }

    /**
     * Get item price
     *
     * @param array $_childItemsOptions
     * @param array $_childItemsQty
     * @return float
     */
    public function getItemPrice(array $_childItemsOptions, array $_childItemsQty): float
    {
        $price = 0;
        foreach ($_childItemsOptions as $productId => $_option) {
            $qty = $_childItemsQty[$productId] ?? 1;
            $price += ($_option['final_price'] ?? 0) * (float)$qty;
        }

        return (float)$price;
    }

    /**
     * Get child item price html
     *
     * @param ProductListItem $item
     * @return string
     */
    public function getChildItemPriceHtml(ProductListItem $item): string
    {
        return $this->pricingHelper->currency($item->getProduct()->getFinalPrice(), true, false);
    }

    /**
     * Get item quantity
     *
     * @return float
     */
    public function getItemQuantity(): float
    {
        return (float)$this->getItem()->getQty();
    }

    /**
     * Get child item row total html
     *
     * @param ProductListItem $item
     * @param array $_childItemsQty
     * @return string
     */
    public function getChildItemRowTotalHtml(ProductListItem $item, array $_childItemsQty): string
    {
        $productId = $item->getProduct()->getEntityId();
        $qty = $_childItemsQty[$productId] ?? 1;

        return $this->pricingHelper->currency($item->getProduct()->getFinalPrice() *
            (float)$qty, true, false);
    }

    /**
     * Get item row total html
     *
     * @param array $_childItemsOptions
     * @param array $_childItemsQty
     * @return string
     */
    public function getItemRowTotalHtml(array $_childItemsOptions, array $_childItemsQty): string
    {
        $price = $this->getItemQuantity() * $this->getItemPrice($_childItemsOptions, $_childItemsQty);
        return $this->pricingHelper->currency($price, true, false);
    }

    /**
     * Get option title
     *
     * @param ProductListItem $childItem
     * @param array $_childItemsOptions
     * @return string
     */
    public function getOptionTitle(ProductListItem $childItem, array $_childItemsOptions): string
    {
        $productId = $childItem->getProduct()->getEntityId();
        return $_childItemsOptions[$productId]['option_title'] ?? '';
    }
}
