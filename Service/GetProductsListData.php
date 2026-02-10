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

namespace BroSolutions\QuickOrder\Service;

use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class GetProductsListData
{
    /**
     * Get product list data
     *
     * @param array $productsData
     * @return array
     */
    public function execute(array $productsData): array
    {
        $allProductsData = [];

        foreach ($productsData as $productDataItem) {
            switch ($productDataItem['type_id']) {
                case 'simple':
                case 'downloadable':
                    $allProductsData[] = [[
                            'product_id' => $productDataItem['entity_id'],
                            'sku' => $productDataItem['sku'],
                            'qty' => $productDataItem['qty'],
                            'parent_id' => null
                        ]];

                    break;

                case Configurable::TYPE_CODE:
                    $parentId = (int)$productDataItem['entity_id'];
                    $data = [];
                    $data[] = [
                        'product_id' => $productDataItem['entity_id'],
                        'sku' => $productDataItem['sku'],
                        'qty' => $productDataItem['qty'],
                        'parent_id' => null
                    ];
                    $data[] = [
                            'product_id' => $productDataItem['active_product']['product_id'],
                            'sku' => $productDataItem['active_product']['sku'],
                            'qty' => $productDataItem['qty'],
                            'parent_id' => $parentId,
                    ];
                    $allProductsData[] = $data;

                    break;
                case Type::TYPE_BUNDLE:
                    $allProductsData[] = $this->getBundleProductData($productDataItem);

                    break;
                case 'grouped':
                    $allProductsData[] = $this->getGroupedProductData($productDataItem);

                    break;
                default:
                    break;
            }
        }
        if (empty($allProductsData)) {
            return [];
        }

        return $this->prepareProductListData($allProductsData);
    }

    /**
     * Prepare product list data
     *
     * @param array $allProductsData
     * @return array
     */
    private function prepareProductListData(array $allProductsData): array
    {
        $data = [];

        foreach ($allProductsData as $productDataItem) {
            foreach ($productDataItem as $productData) {
                $data[] = [
                    'parent_id' => $productData['parent_id'],
                    'product_id' => $productData['product_id'],
                    'qty' => (float)$productData['qty'],
                    'sku' => $productData['sku'],
                ];
            }
        }

        return $data;
    }

    /**
     * Get bundle product data
     *
     * @param array $items
     * @return array
     */
    private function getBundleProductData(array $items): array
    {
        $parentId = $items['entity_id'];

        /** PARENT */
        $data[] = [
            'product_id' => $parentId,
            'sku' => $items['sku'],
            'parent_id' => null,
            'qty' => ($items['qty'] ?? 1),
        ];

        /** CHILDREN */
        foreach ($items['active_selections'] as $selectionGroup) {
            if (empty($selectionGroup['selection_value'])) {
                continue;
            }

            foreach ($selectionGroup['selection_value'] as $selection) {
                if (empty($selection['value'])) {
                    continue;
                }

                $childQty = $selection['qty'] ?? 1;
                $canChangeQty = (bool)($selection['change_qty'] ?? false);

                $childQty = $canChangeQty
                    ? $childQty
                    : 1;

                $data[] = [
                    'product_id' => $selection['product_id'],
                    'sku' => $selection['sku'],
                    'parent_id' => $parentId,
                    'qty' => $childQty,
                ];
            }
        }

        return $data;
    }

    /**
     * Get grouped product data
     *
     * @param array $items
     * @return array
     */
    private function getGroupedProductData(array $items): array
    {
        $parentId = $items['entity_id'];

        /** PARENT (grouped) */
        $data[] = [
            'product_id' => $parentId,
            'sku' => $items['sku'],
            'parent_id' => null,
            'qty' => $items['qty']
        ];

        if (empty($items['active_selections'])) {
            return [];
        }

        $childData = [];

        /** CHILDREN */
        foreach ($items['active_selections'] as $selection) {
            if (empty($childQty = (float)$selection['qty'])) {
                continue;
            }

            $childData[] = [
                'product_id' => $selection['id'],
                'sku' => $selection['sku'],
                'parent_id' => $parentId,
                'qty' => $childQty,
            ];
        }
        if (empty($childData)) {
            return [];
        }

        return array_merge($data, $childData);
    }
}
