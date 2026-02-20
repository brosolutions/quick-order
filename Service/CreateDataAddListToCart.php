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

namespace BroSolutions\QuickOrder\Service;

use BroSolutions\QuickOrder\Model\ResourceModel\ProductListItem\CollectionFactory as ProductListItemCollectionFactory;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class CreateDataAddListToCart
{

    /** @var CollectionFactory */
    private $productCollectionFactory;

    /** @var Json */
    private $json;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /**
     * @var ProductListItemCollectionFactory
     */
    private $productListCollectionFactory;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param Json $json
     * @param ProductRepositoryInterface $productRepository
     * @param ProductListItemCollectionFactory $productListCollectionFactory
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        Json $json,
        ProductRepositoryInterface $productRepository,
        ProductListItemCollectionFactory $productListCollectionFactory
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->json = $json;
        $this->productRepository = $productRepository;
        $this->productListCollectionFactory = $productListCollectionFactory;
    }

    /**
     * Create data for cart
     *
     * @param int $listId
     * @return string|null
     */
    public function execute(int $listId)
    {
        $rows = $this->loadRows($listId);
        if (!$rows) {
            return null;
        }

        $tree = $this->buildTree($rows);
        $skus = $this->extractAllSkus($tree);
        $products = $this->loadProductsBySkus($skus);

        $result = [];
        foreach ($tree as $item) {
            $mapped = $this->mapItem($item, $products);
            if ($mapped) {
                $result[] = $mapped;
            }
        }

        return $result ? $this->json->serialize($result) : null;
    }

    /**
     * Load rows
     *
     * @param int $listId
     * @return array
     */
    private function loadRows(int $listId): array
    {
        $collection = $this->productListCollectionFactory->create();

        $collection->addFieldToFilter('list_id', $listId)
            ->setOrder('parent_id', 'ASC')
            ->setOrder('id', 'ASC');

        return $collection->getData();
    }

    /**
     * Build tree
     *
     * @param array $rows
     * @return array
     */
    private function buildTree(array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $row['children'] = [];
            $indexed[$row['id']] = $row;
        }

        $tree = [];
        foreach ($indexed as $id => $row) {
            if (!empty($row['parent_id']) && isset($indexed[$row['parent_id']])) {
                $indexed[$row['parent_id']]['children'][] = $id;
            } else {
                $tree[] = $id;
            }
        }

        return $this->hydrateTree($tree, $indexed);
    }

    /**
     * Hydrate tree
     *
     * @param array $ids
     * @param array $indexed
     * @return array
     */
    private function hydrateTree(array $ids, array $indexed): array
    {
        $result = [];

        foreach ($ids as $id) {
            $item = $indexed[$id];
            if (!empty($item['children'])) {
                $item['children'] = $this->hydrateTree($item['children'], $indexed);
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * Extract all skus
     *
     * @param array $tree
     * @return array
     */
    private function extractAllSkus(array $tree): array
    {
        $skus = $this->collectSkus($tree);
        return array_values(array_unique($skus));
    }

    /**
     * Collect skus efficiently
     *
     * @param array $items
     * @return array
     */
    private function collectSkus(array $items): array
    {
        $skus = [];
        foreach ($items as $item) {
            if (!empty($item['sku'])) {
                $skus[] = $item['sku'];
            }
            if (!empty($item['children'])) {
                foreach ($this->collectSkus($item['children']) as $childSku) {
                    $skus[] = $childSku;
                }
            }
        }
        return $skus;
    }

    /**
     * Load products by skus
     *
     * @param array $skus
     * @return array
     */
    private function loadProductsBySkus(array $skus): array
    {
        if (!$skus) {
            return [];
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['sku', 'type_id'])
            ->addAttributeToFilter('sku', ['in' => $skus]);

        $products = [];
        foreach ($collection as $product) {
            $products[$product->getSku()] = $product;
        }

        return $products;
    }

    /**
     * Map item
     *
     * @param array $item
     * @param array $products
     * @return array
     */
    private function mapItem(array $item, array $products): array
    {
        if (empty($item['sku']) || !isset($products[$item['sku']])) {
            return [];
        }

        $product = $products[$item['sku']];

        switch ($product->getTypeId()) {
            case 'simple':
            case 'downloadable':
                return [
                    'type_id' => 'simple',
                    'sku' => $item['sku'],
                    'qty' => (float)$item['qty'],
                    'active_custom_options' => []
                ];

            case 'configurable':
                return $this->mapConfigurable($item, $products);

            case 'grouped':
                return $this->mapGrouped($item);

            case 'bundle':
                return $this->mapBundle($item);
        }

        return [];
    }

    /**
     * Map configurable
     *
     * @param array $item
     * @param array $products
     * @return array
     */
    private function mapConfigurable(array $item, array $products): array
    {
        if (empty($item['children'])) {
            return [];
        }

        $child = reset($item['children']);
        $childProductId = (int)$child['product_id'];

        try {
            $configurableProduct = $this->productRepository->get($item['sku']);
            $simpleProduct = $this->productRepository->getById($childProductId);

            /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $typeInstance */
            $typeInstance = $configurableProduct->getTypeInstance();
            $configurableAttributes = $typeInstance->getConfigurableAttributes($configurableProduct);

            $activeProduct = [
                'entity_id' => (int)$simpleProduct->getId()
            ];

            $attributes = [];
            foreach ($configurableAttributes as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $code = $productAttribute->getAttributeCode();
                $value = $simpleProduct->getData($code);

                if ($value !== null) {
                    $activeProduct[$code] = $value;
                    $attributes[] = [
                        'attribute_id' => (int)$productAttribute->getId(),
                        'attribute_code' => $code
                    ];
                }
            }

            return [
                'type_id' => 'configurable',
                'sku' => $item['sku'],
                'qty' => (float)$child['qty'],
                'active_product' => $activeProduct,
                'attributes' => $attributes,
                'active_custom_options' => []
            ];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Map grouped products
     *
     * @param array $item
     * @return array
     */
    private function mapGrouped(array $item): array
    {
        if (empty($item['children'])) {
            return [];
        }

        $quickGroupedProducts = [];
        $activeSelections = [];

        foreach ($item['children'] as $child) {
            $quickGroupedProducts[] = [
                'sku' => $child['sku'],
                'entity_id' => (int)$child['product_id']
            ];
            $activeSelections[] = [
                'id' => (int)$child['product_id'],
                'qty' => (float)$child['qty']
            ];
        }

        return [
            'type_id' => 'grouped',
            'sku' => $item['sku'],
            'qty' => 1,
            'quick_grouped_products' => $quickGroupedProducts,
            'active_selections' => $activeSelections
        ];
    }

    /**
     * Map bundle products
     *
     * @param array $item
     * @return array
     */
    private function mapBundle(array $item): array
    {
        if (empty($item['children'])) {
            return [];
        }

        try {
            $bundleProduct = $this->productRepository->get($item['sku']);
            $bundleProduct->setStoreId(0);

            /** @var \Magento\Bundle\Model\Product\Type $typeInstance */
            $typeInstance = $bundleProduct->getTypeInstance();

            $options = $typeInstance->getOptionsCollection($bundleProduct);
            $selections = $typeInstance->getSelectionsCollection(
                $options->getAllIds(),
                $bundleProduct
            );

            $selectionMap = [];
            foreach ($selections as $selection) {
                $selectionMap[(int)$selection->getProductId()] = [
                    'option_id' => (int)$selection->getOptionId(),
                    'selection_id' => (int)$selection->getSelectionId()
                ];
            }

            $activeSelections = [];
            foreach ($item['children'] as $child) {
                $productId = (int)$child['product_id'];
                if (!isset($selectionMap[$productId])) {
                    continue;
                }

                $optionId = $selectionMap[$productId]['option_id'];
                $selectionId = $selectionMap[$productId]['selection_id'];

                if (!isset($activeSelections[$optionId])) {
                    $activeSelections[$optionId] = [
                        'id' => $optionId,
                        'selection_value' => []
                    ];
                }

                $activeSelections[$optionId]['selection_value'][] = [
                    'value_id' => $selectionId,
                    'value' => true,
                    'change_qty' => false,
                    'qty' => (float)$child['qty']
                ];
            }

            return [
                'type_id' => 'bundle',
                'sku' => $item['sku'],
                'qty' => (float)$item['qty'],
                'active_selections' => array_values($activeSelections)
            ];
        } catch (Exception $e) {
            return [];
        }
    }
}
