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

namespace BroSolutions\QuickOrder\Model;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface;
use BroSolutions\QuickOrder\Api\SearchProductsManagementInterface;
use BroSolutions\QuickOrder\Service\GetQuickOrderEnable;
use BroSolutions\QuickOrder\Service\GetSearchResultsLimit;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Exception\NoSuchEntityException;
use BroSolutions\QuickOrder\Service\GetStoreCurrency;
use BroSolutions\QuickOrder\Service\GetStoreId;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class SearchProductsManagement implements SearchProductsManagementInterface
{
    /** @var PriceCurrencyInterface $priceCurrency */
    private $priceCurrency;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GetQuickOrderEnable
     */
    private $getQuickOrderEnable;

    /**
     * @var GetSearchResultsLimit
     */
    private $getSearchResultsLimit;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var GetStoreCurrency
     */
    private $getStoreCurrency;

    /**
     * @var GetStoreId
     */
    private $getStoreId;

    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * @param PriceCurrencyInterface $priceCurrency
     * @param StoreManager $storeManager
     * @param GetQuickOrderEnable $getQuickOrderEnable
     * @param GetSearchResultsLimit $getSearchResultsLimit
     * @param ProductCollectionFactory $productCollectionFactory
     * @param LoggerInterface $logger
     * @param GetStoreCurrency $getStoreCurrency
     * @param GetStoreId $getStoreId
     * @param ImageHelper $imageHelper
     */
    public function __construct(
        PriceCurrencyInterface   $priceCurrency,
        StoreManager             $storeManager,
        GetQuickOrderEnable      $getQuickOrderEnable,
        GetSearchResultsLimit    $getSearchResultsLimit,
        ProductCollectionFactory $productCollectionFactory,
        LoggerInterface          $logger,
        GetStoreCurrency $getStoreCurrency,
        GetStoreId $getStoreId,
        ImageHelper $imageHelper
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->storeManager = $storeManager;
        $this->getQuickOrderEnable = $getQuickOrderEnable;
        $this->getSearchResultsLimit = $getSearchResultsLimit;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
        $this->getStoreCurrency = $getStoreCurrency;
        $this->getStoreId = $getStoreId;
        $this->imageHelper = $imageHelper;
    }

    /**
     * Get products
     *
     * @param string $term
     * @param string $storeCode
     * @return array
     */
    public function getProducts(string $term, string $storeCode)
    {
        $productList = [];

        try {
            if (!$this->getQuickOrderEnable->execute()) {
                return $productList;
            }
            $stores = $this->storeManager->getStores();
            $storeId = null;
            foreach ($stores as $store) {
                if ($store->getCode() == $storeCode) {
                    $storeId = $store->getId();
                }
            }
            $this->storeManager->setCurrentStore($storeId);
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(["name", "sku", "price", "thumbnail_image"]);
            $collection->addAttributeToFilter([
                ['attribute' => 'name', 'like' => '%' . $term . '%'],
                ['attribute' => 'sku', 'like' => '%' . $term . '%']

            ]);

            $collection->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addStoreFilter($this->getStoreId->execute($storeCode));

            $collection->setVisibility(['in' => [Visibility::VISIBILITY_IN_SEARCH, Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_BOTH]]);
            $collection->getSelect()->joinLeft(
                'catalog_product_super_link',
                'e.entity_id = catalog_product_super_link.product_id'
            )->where("catalog_product_super_link.product_id IS NULL")
                ->limit($this->getSearchResultsLimit->execute());

            foreach ($collection as $product) {
                $productData = $product->getData();
                if (!empty($productData['price'])) {
                    $productData['price'] = $this->priceCurrency->convertAndFormat(
                        $productData['price'],
                        false,
                        2,
                        null,
                        $this->getStoreCurrency->execute($storeCode)
                    );
                }
                $productData['thumbnail'] = $this->imageHelper
                    ->init($product, 'product_thumbnail_image')->getUrl();
                $productList[] = $productData;
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->error(
                sprintf(
                    'It is not possible to get products. Original error: %s',
                    $e->getMessage()
                ),
                $e->getTrace()
            );
        }

        return $productList;
    }
}
