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

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManager;
use Throwable;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductListItem;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class SaveProductListToAccount
{
    /**
     * @var GetProductsListData
     */
    private $getProductsListData;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @param GetProductsListData $getProductsListData
     * @param Json $json
     * @param ResourceConnection $resource
     * @param StoreManager $storeManager
     * @param CustomerSession $customerSession
     */
    public function __construct(
        GetProductsListData $getProductsListData,
        Json                 $json,
        ResourceConnection $resource,
        StoreManager $storeManager,
        CustomerSession $customerSession
    ) {
        $this->getProductsListData = $getProductsListData;
        $this->json = $json;
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
    }

    /**
     * Save product list to account
     *
     * @param string $params
     * @param string $listName
     * @return bool
     * @throws NoSuchEntityException
     * @throws Throwable
     */
    public function execute(string $params, string $listName): bool
    {
        $params = $this->json->unserialize($params);
        $productsData = $this->getProductsListData->execute($params);
        if (empty($productsData)) {
            return false;
        }
        $connection = $this->resource->getConnection();

        $tableList = $this->resource->getTableName(ProductList::QUICK_ORDER_LIST_TABLE);
        $tableProductList = $this->resource->getTableName(ProductListItem::QUICK_ORDER_LIST_ITEM_TABLE);

        $connection->beginTransaction();

        try {
            $connection->insert(
                $tableList,
                [
                    'list_name' => $listName,
                    'customer_id' => $this->customerSession->getCustomerId(),
                    'store_id' => $this->storeManager->getStore()->getId()
                ]
            );

            $lastInsertListId = (int)$connection->lastInsertId();

            // Separate parent and child items
            $parentItems = [];
            $childItemsGroups = [];
            $currentParentIndex = -1;

            foreach ($productsData as $productDataItem) {
                $productDataItem['list_id'] = $lastInsertListId;

                if (empty($productDataItem['parent_id'])) {
                    // Parent item
                    unset($productDataItem['parent_id']);
                    $parentItems[] = $productDataItem;
                    $currentParentIndex++;
                } else {
                    // Child item - group by parent
                    if (!isset($childItemsGroups[$currentParentIndex])) {
                        $childItemsGroups[$currentParentIndex] = [];
                    }
                    unset($productDataItem['parent_id']);
                    $childItemsGroups[$currentParentIndex][] = $productDataItem;
                }
            }

            // Insert all parent items at once
            if (!empty($parentItems)) {
                $connection->insertMultiple($tableProductList, $parentItems);
                $firstParentId = (int)$connection->lastInsertId();

                // Prepare all child items with correct parent_id
                $allChildItems = [];
                foreach ($childItemsGroups as $parentIndex => $childItems) {
                    $parentId = $firstParentId + $parentIndex;
                    foreach ($childItems as $childItem) {
                        $childItem['parent_id'] = $parentId;
                        $allChildItems[] = $childItem;
                    }
                }

                // Insert all child items at once
                if (!empty($allChildItems)) {
                    $connection->insertMultiple($tableProductList, $allChildItems);
                }
            }

            $connection->commit();
        } catch (Throwable $e) {
            $connection->rollBack();
            throw $e;
        }

        return true;
    }
}
