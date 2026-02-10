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

        $tableList = $this->resource->getTableName('brosolutions_list');
        $tableProductList = $this->resource->getTableName('brosolutions_product_list');

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

            $parentIdMap = [];
            $lastInsertId = null;

            foreach ($productsData as $productDataItem) {

                $productDataItem['list_id'] = $lastInsertListId;
                $parentId = $productDataItem['parent_id'];

                if (empty($parentId)) {
                    //insert parent
                    $connection->insert(
                        $tableProductList,
                        $productDataItem
                    );

                    $lastInsertId = (int)$connection->lastInsertId();
                } else {
                    $parentIdMap[$lastInsertId][] = $productDataItem;
                }
            }

            foreach ($parentIdMap as $parentId => $productList) {
                foreach ($productList as $productDataItem) {
                    $productDataItem['parent_id'] = $parentId;
                    if (!empty($parentId)) {
                        $connection->insertMultiple(
                            $tableProductList,
                            $productDataItem
                        );
                    }
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
