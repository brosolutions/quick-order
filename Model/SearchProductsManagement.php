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

use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Framework\App\Area;
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
use Magento\Store\Model\App\Emulation;

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
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * @var Emulation
     */
    private $emulation;


    /**
     * @param PriceCurrencyInterface $priceCurrency
     * @param StoreManager $storeManager
     * @param GetQuickOrderEnable $getQuickOrderEnable
     * @param GetSearchResultsLimit $getSearchResultsLimit
     * @param ProductCollectionFactory $productCollectionFactory
     * @param LoggerInterface $logger
     * @param GetStoreCurrency $getStoreCurrency
     * @param GetStoreId $getStoreId
     * @param ImageFactory $imageFactory
     * @param Emulation $emulation
     */
    public function __construct(
        PriceCurrencyInterface   $priceCurrency,
        StoreManager             $storeManager,
        GetQuickOrderEnable      $getQuickOrderEnable,
        GetSearchResultsLimit    $getSearchResultsLimit,
        ProductCollectionFactory $productCollectionFactory,
        LoggerInterface          $logger,
        GetStoreCurrency         $getStoreCurrency,
        GetStoreId               $getStoreId,
        ImageFactory             $imageFactory,
        Emulation                $emulation
    )
    {
        $this->priceCurrency = $priceCurrency;
        $this->storeManager = $storeManager;
        $this->getQuickOrderEnable = $getQuickOrderEnable;
        $this->getSearchResultsLimit = $getSearchResultsLimit;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
        $this->getStoreCurrency = $getStoreCurrency;
        $this->getStoreId = $getStoreId;
        $this->imageFactory = $imageFactory;
        $this->emulation = $emulation;
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

            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(["name", "sku", "price", "thumbnail_image", "small_image"]);
            $collection->addAttributeToFilter([
                ['attribute' => 'name', 'like' => '%' . trim($term) . '%'],
                ['attribute' => 'sku', 'like' => '%' . trim($term) . '%']

            ]);

            $storeId = null;
            $stores = $this->storeManager->getStores();
            foreach ($stores as $store) {
                if ($store->getCode() == $storeCode) {
                    $storeId = $store->getId();
                }
            }

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
                $this->emulation->startEnvironmentEmulation(
                    $storeId,
                    Area::AREA_FRONTEND,
                    true
                );
                $productData['thumbnail'] = $this->imageFactory
                    ->create($product, 'cart_page_product_thumbnail', [])->getImageUrl();
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
        $this->emulation->stopEnvironmentEmulation();
        return $productList;
    }
}
