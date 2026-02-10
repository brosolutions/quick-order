<?php
/**
 * Copyright (c) 2025 BroSolutions
 * All rights reserved
 *
 * Proprietary software developed at BroSolutions, Ukraine
 * More info: https://www.brosolutions.net/
 * Contact: contact@brosolutions.net
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ProductListItem extends DataObject
{
    /**
     * @var bool
     */
    protected bool $isAvailable = true;

    /**
     * Is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return (bool)$this->getData('is_available');
    }

    /**
     * Get is in stock
     *
     * @return bool
     */
    public function isInStockStock(): bool
    {
        return (bool)$this->getData('is_in_stock');
    }

    /**
     * Get product
     *
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->getData('product');
    }

    /**
     * Get product id
     *
     * @return int
     */
    public function getProductId(): int
    {
        return (int)$this->getProduct()->getId();
    }

    /**
     * Get sku
     *
     * @return string
     */
    public function getSku(): string
    {
        return (string)$this->getData('sku');
    }

    /**
     * Get product quantity
     *
     * @return string
     */
    public function getQty(): string
    {
        return (string)$this->getData('qty');
    }

    /**
     * Get product name
     *
     * @return string
     */
    public function getName(): string
    {
        return (string)$this->getProduct()->getName();
    }

    /**
     * Get product type
     *
     * @return string|null
     */
    public function getProductType(): ?string
    {
        return $this->getData('type');
    }

    /**
     * Get product options
     *
     * @return array
     */
    public function getProductOptions(): array
    {
        return $this->getData('product_options') ?? [];
    }

    /**
     * Get is available item
     *
     * @return bool
     */
    public function getIsNotAvailable(): bool
    {
        if (!$this->isInStockStock() || !$this->getProduct()) {
            return true;
        }
        return false;
    }

    /**
     * Get error message
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        $errorMessage = '';
        if (!$this->isInStockStock()) {
            $errorMessage = $this->isOutOfStockMessage();
        }
        if (!$this->isAvailable()) {
            $errorMessage = $this->isAvailableMessage();
        }

        return $errorMessage;
    }

    /**
     * Get is out of stock message
     *
     * @return string
     */
    private function isOutOfStockMessage(): string
    {
        return  'This product is out of stock';
    }

    /**
     * Get is out of stock message
     *
     * @return string
     */
    private function isAvailableMessage(): string
    {
        return 'This product no longer exists';
    }
}
