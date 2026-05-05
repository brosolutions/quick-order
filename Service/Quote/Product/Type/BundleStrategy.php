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
use Magento\Bundle\Model\Product\Type;

/**
 * Handles adding bundle products to the quote.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class BundleStrategy implements TypeStrategyInterface
{
    /**
     * @inheritDoc
     */
    public function addToQuote(Quote $quote, ProductInterface $product, array $itemData, int $storeId): void
    {
        $buyRequest = new DataObject(['qty' => (float)($itemData['qty'] ?? 1)]);

        if (empty($itemData['active_selections']) || !is_array($itemData['active_selections'])) {
            $quote->addProduct($product, $buyRequest);
            return;
        }

        $bundleOptions = [];
        $bundleQty = [];

        foreach ($itemData['active_selections'] as $sel) {
            if (empty($sel['id']) || empty($sel['selection_value'])) {
                continue;
            }
            $optId = $sel['id'];
            $selectionValues = $sel['selection_value'];

            if (count($selectionValues) === 1) {
                $bundleOptions[$optId] = $selectionValues[0]['value_id'] ?? null;
                $bundleQty[$optId] = $selectionValues[0]['qty'] ?? 1;
                continue;
            }

            $bundleOptions[$optId] = [];
            foreach ($selectionValues as $val) {
                if (empty($val['value_id'])) {
                    continue;
                }
                $bundleOptions[$optId][] = $val['value_id'];
                $bundleQty[$optId] = $val['qty'] ?? 1;
            }
        }

        if (!empty($bundleOptions)) {
            $buyRequest->setData('bundle_option', $bundleOptions);
            $buyRequest->setData('bundle_option_qty', $bundleQty);
        }

        $quote->addProduct($product, $buyRequest);
    }

    /**
     * @inheritDoc
     */
    public function calculatePrice(ProductInterface $product, array $itemData, ?int $customerGroupId = null): float
    {
        $price = 0.0;

        /** @var Product $productModel */
        $productModel = $product;

        if ($customerGroupId !== null) {
            $productModel->setCustomerGroupId($customerGroupId);
        }

        if (empty($itemData['active_selections'])) {
            return $price;
        }

        /** @var Type $typeInstance */
        $typeInstance = $productModel->getTypeInstance();
        $selections = $typeInstance->getSelectionsCollection(
            $typeInstance->getOptionsIds($productModel),
            $productModel
        );

        foreach ($itemData['active_selections'] as $option) {
            if (empty($option['selection_value'])) {
                continue;
            }

            foreach ($option['selection_value'] as $sel) {
                /** @var Product $selectionModel */
                $selectionModel = $selections->getItemById($sel['value_id']);

                if (!$selectionModel) {
                    continue;
                }

                if ($customerGroupId !== null) {
                    $selectionModel->setCustomerGroupId($customerGroupId);
                }

                $price += (float)$selectionModel->getFinalPrice() * (float)($sel['qty'] ?? 1);
            }
        }

        return $price;
    }
}
