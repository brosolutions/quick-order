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

namespace BroSolutions\QuickOrder\Service\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

/**
 * Loads multiple products in a single database query to prevent N+1 issues.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class BulkLoader
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Loads products by SKUs for a specific store.
     *
     * @param array $skus
     * @param int $storeId
     * @return array<string, ProductInterface> Associative array of [SKU => Product]
     */
    public function loadBySkus(array $skus, int $storeId): array
    {
        if (empty($skus)) {
            return [];
        }

        $collection = $this->collectionFactory->create();
        $collection->addStoreFilter($storeId)
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('sku', ['in' => array_unique($skus)]);

        $products = [];
        foreach ($collection as $product) {
            $products[$product->getSku()] = $product;
        }

        return $products;
    }
}
