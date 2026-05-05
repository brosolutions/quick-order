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

namespace BroSolutions\QuickOrder\Service\Quote\Product\Type;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;

/**
 * Handles adding simple products to the quote.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class SimpleStrategy implements TypeStrategyInterface
{
    /**
     * @inheritDoc
     */
    public function addToQuote(Quote $quote, ProductInterface $product, array $itemData, int $storeId): void
    {
        $buyRequest = new DataObject(['qty' => (float)($itemData['qty'] ?? 1)]);
        $quote->addProduct($product, $buyRequest);
    }

    /**
     * @inheritDoc
     */
    public function calculatePrice(ProductInterface $product, array $itemData, ?int $customerGroupId = null): float
    {
        /** @var Product $productModel */
        $productModel = $product;

        if ($customerGroupId !== null) {
            $productModel->setCustomerGroupId($customerGroupId);
        }

        return (float)$productModel->getFinalPrice();
    }
}
