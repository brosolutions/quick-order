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

use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class GroupedRenderer extends Template
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
    protected $_template = 'BroSolutions_QuickOrder::item/grouped.phtml';

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
     * Get child item price html
     *
     * @param Product $product
     * @return string
     */
    public function getChildItemPriceHtml(Product $product): string
    {
        return $this->pricingHelper->currency($product->getFinalPrice(), true, false);
    }

    /**
     * Get child item row total html
     *
     * @param Product $product
     * @param array $_childItemsQty
     * @return string
     */
    public function getChildItemRowTotalHtml(Product $product, array $_childItemsQty): string
    {
        $productId = $product->getEntityId();
        $qty = $_childItemsQty[$productId] ?? 1;

        return $this->pricingHelper->currency($product->getFinalPrice() *
            (float)$qty, true, false);
    }
}
