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

namespace BroSolutions\QuickOrder\Block\Item\Renderer;

use BroSolutions\QuickOrder\Model\Item\ProductListItem;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class SimpleRenderer extends Template
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
    protected $_template = 'BroSolutions_QuickOrder::item/simple.phtml';

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
     * Get product
     *
     * @return null|Product
     */
    public function getProduct(): ?Product
    {
        return $this->getItem()->getProduct();
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
     * Get quantity
     *
     * @return string
     */
    public function getQty(): string
    {
        return $this->getItem()->getQty();
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
     * @return string
     */
    public function prepareSku(): string
    {
        return $this->string->splitInjection($this->getSku());
    }

    /**
     * Get item price html
     *
     * @return string
     */
    public function getItemPriceHtml(): string
    {
        return $this->pricingHelper->currency($this->getProduct()->getFinalPrice(), true, false);
    }

    /**
     * Get item quantity
     *
     * @return float
     */
    public function getItemQuantity(): float
    {
        return (float)$this->getQty();
    }

    /**
     * Get item row total html
     *
     * @return string
     */
    public function getItemRowTotalHtml(): string
    {
        $product = $this->getProduct();

        if (!$product) {
            return '';
        }

        $quantity = $this->getItemQuantity();

        $rowTotal = $product->getFinalPrice() * $quantity;

        return $this->pricingHelper->currency($rowTotal, true, false);
    }

    /**
     * Get item options
     *
     * @return array
     */
    public function getItemOptions(): array
    {
        $options = [];
        try {
            $product = $this->getProduct();

            $allowedAttributes = $this->getItem()->getData('allowed_attributes');
            if (empty($allowedAttributes)) {
                return [];
            }

            foreach ($product->getAttributes() as $attribute) {
                if (!in_array($attribute->getAttributeCode(), $allowedAttributes)) {
                    continue;
                }

                if ($attribute->getIsVisible()) {
                    $value = $product->getData($attribute->getAttributeCode());

                    if ($value === null || $value === '') {
                        continue;
                    }

                    $formattedValue = $this->formatAttributeValue($value, $attribute);

                    if ($formattedValue instanceof Phrase) {
                        $formattedValue = (string)$formattedValue;
                    }

                    $options[] = [
                        'label' => $attribute->getFrontendLabel(),
                        'value' => $formattedValue,
                        'print_value' => $formattedValue,
                        'option_id' => $attribute->getAttributeId(),
                        'option_type' => $attribute->getFrontendInput()
                    ];
                }
            }
        } catch (LocalizedException $e) {
            return [];
        }
        return $options;
    }

    /**
     * Format attribute value
     *
     * @param string|array $value
     * @param AbstractAttribute $attribute
     * @return string|array
     * @throws LocalizedException
     */
    private function formatAttributeValue(string|array $value, AbstractAttribute $attribute): string|array
    {
        if ($attribute->usesSource()) {
            $text = $attribute->getSource()->getOptionText($value);
            return $text ?: $value;
        }

        if (is_array($value)) {
            return array_map(function ($v) use ($attribute) {
                $result = $attribute->usesSource()
                    ? ($attribute->getSource()->getOptionText($v) ?: $v)
                    : $v;
                return $result instanceof Phrase ? (string)$result : $result;
            }, $value);
        }

        return $value;
    }
}
