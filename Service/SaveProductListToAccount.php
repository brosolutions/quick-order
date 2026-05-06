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

use BroSolutions\QuickOrder\Model\ResourceModel\ProductList;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductListItem;
use Magento\Customer\Model\CustomerFactory as CustomerModelFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerModelResource;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManager;
use Throwable;

/**
 * Class SaveProductListToAccount
 * Handles saving products from quote to a customer's list.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
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
     * @var CustomerModelFactory
     */
    private $customerModelFactory;

    /**
     * @var CustomerModelResource
     */
    private $customerModelResource;

    /**
     * SaveProductListToAccount constructor.
     *
     * @param GetProductsListData $getProductsListData
     * @param Json $json
     * @param ResourceConnection $resource
     * @param StoreManager $storeManager
     * @param CustomerSession $customerSession
     * @param CustomerModelFactory $customerModelFactory
     * @param CustomerModelResource $customerModelResource
     */
    public function __construct(
        GetProductsListData $getProductsListData,
        Json $json,
        ResourceConnection $resource,
        StoreManager $storeManager,
        CustomerSession $customerSession,
        CustomerModelFactory $customerModelFactory,
        CustomerModelResource $customerModelResource
    ) {
        $this->getProductsListData = $getProductsListData;
        $this->json = $json;
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->customerModelFactory = $customerModelFactory;
        $this->customerModelResource = $customerModelResource;
    }

    /**
     * Save product list to account.
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
        $customerId = (int)$this->customerSession->getCustomerId();

        $customerModel = $this->customerModelFactory->create();
        $this->customerModelResource->load($customerModel, $customerId);

        $companyId = $customerModel->getData('company_id');
        $role = $customerModel->getData('company_role');

        $approvalStatus = ($companyId && $role === 'company_user') ? 'draft' : 'approved';

        try {
            $connection->insert(
                $tableList,
                [
                    'list_name' => $listName,
                    'customer_id' => $customerId,
                    'store_id' => $this->storeManager->getStore()->getId(),
                    'approval_status' => $approvalStatus
                ]
            );

            $lastInsertListId = (int)$connection->lastInsertId();

            $parentItems = [];
            $childItemsGroups = [];
            $currentParentIndex = -1;

            foreach ($productsData as $productDataItem) {
                $productDataItem['list_id'] = $lastInsertListId;

                if (empty($productDataItem['parent_id'])) {
                    unset($productDataItem['parent_id']);
                    $parentItems[] = $productDataItem;
                    $currentParentIndex++;
                } else {
                    if (!isset($childItemsGroups[$currentParentIndex])) {
                        $childItemsGroups[$currentParentIndex] = [];
                    }
                    unset($productDataItem['parent_id']);
                    $childItemsGroups[$currentParentIndex][] = $productDataItem;
                }
            }

            if (!empty($parentItems)) {
                $connection->insertMultiple($tableProductList, $parentItems);
                $firstParentId = (int)$connection->lastInsertId();
                $allChildItems = [];

                foreach ($childItemsGroups as $parentIndex => $childItems) {
                    $parentId = $firstParentId + $parentIndex;
                    foreach ($childItems as $childItem) {
                        $childItem['parent_id'] = $parentId;
                        $allChildItems[] = $childItem;
                    }
                }

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
