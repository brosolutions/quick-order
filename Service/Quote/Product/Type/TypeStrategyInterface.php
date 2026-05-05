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
use Magento\Quote\Model\Quote;

/**
 * Strategy interface for adding different product types to the quote.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
interface TypeStrategyInterface
{
    /**
     * Adds a specific product type to the quote.
     *
     * @param Quote $quote
     * @param ProductInterface $product
     * @param array $itemData
     * @param int $storeId
     * @return void
     */
    public function addToQuote(Quote $quote, ProductInterface $product, array $itemData, int $storeId): void;

    /**
     * Calculates the configured price based on selected options and customer group.
     *
     * @param ProductInterface $product
     * @param array $itemData
     * @param int|null $customerGroupId
     * @return float
     */
    public function calculatePrice(ProductInterface $product, array $itemData, ?int $customerGroupId = null): float;
}
