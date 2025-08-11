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

use BroSolutions\QuickOrder\Service\GetStoreCurrency;
use BroSolutions\QuickOrder\Service\GetStoreId;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Framework\App\Area;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use BroSolutions\QuickOrder\Service\GetQuickOrderEnable;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use BroSolutions\QuickOrder\Api\ProductManagementInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use BroSolutions\QuickOrder\Service\GetCurrencySymbol;
use BroSolutions\QuickOrder\Service\ConvertCurrency;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ProductManagement implements ProductManagementInterface
{

    /** @var PriceCurrencyInterface $priceCurrency */
    private $priceCurrency;

    /**
     * @var StoreManagerInterface
     */
    private $storeManagerInterface;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GetQuickOrderEnable
     */
    private $getQuickOrderEnable;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockItemRepository
     */
    private $stockItemRepository;

    /**
     * @var CatalogHelper
     */
    private $catalogData;

    /**
     * @var GetStoreCurrency
     */
    private $getStoreCurrency;

    /**
     * @var GetStoreId
     */
    private $getStoreId;

    /**
     * @var GetCurrencySymbol
     */
    private $getCurrencySymbol;

    /**
     * @var ConvertCurrency
     */
    private $convertCurrency;

    /**
     * @var string
     */
    private $currencyCode;

    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * @var Emulation
     */
    private $emulation;

    private $productAttributeRepository;

    /**
     * @param CatalogHelper $catalogData
     * @param GetQuickOrderEnable $getQuickOrderEnable
     * @param LoggerInterface $logger
     * @param PriceCurrencyInterface $priceCurrency
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param StockItemRepository $stockItemRepository
     * @param StoreManagerInterface $storeManagerInterface
     * @param GetStoreCurrency $getStoreCurrency
     * @param GetStoreId $getStoreId
     * @param GetCurrencySymbol $getCurrencySymbol
     * @param ConvertCurrency $convertCurrency
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param ImageFactory $imageFactory
     * @param Emulation $emulation
     */
    public function __construct(
        CatalogHelper                       $catalogData,
        GetQuickOrderEnable                 $getQuickOrderEnable,
        LoggerInterface                     $logger,
        PriceCurrencyInterface              $priceCurrency,
        ProductCollectionFactory            $productCollectionFactory,
        ProductRepositoryInterface          $productRepository,
        StockItemRepository                 $stockItemRepository,
        StoreManagerInterface               $storeManagerInterface,
        GetStoreCurrency                    $getStoreCurrency,
        GetStoreId                          $getStoreId,
        GetCurrencySymbol                   $getCurrencySymbol,
        ConvertCurrency                     $convertCurrency,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        ImageFactory                        $imageFactory,
        Emulation                           $emulation
    )
    {
        $this->catalogData = $catalogData;
        $this->getQuickOrderEnable = $getQuickOrderEnable;
        $this->logger = $logger;
        $this->priceCurrency = $priceCurrency;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->stockItemRepository = $stockItemRepository;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->getStoreCurrency = $getStoreCurrency;
        $this->getStoreId = $getStoreId;
        $this->getCurrencySymbol = $getCurrencySymbol;
        $this->convertCurrency = $convertCurrency;
        $this->imageFactory = $imageFactory;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->emulation = $emulation;
    }

    /**
     * Get product
     *
     * @param string $sku
     * @param string $storeCode
     * @return array
     */
    public function getProduct(string $sku, string $storeCode): array
    {
        $productList = [];
        $this->currencyCode = $this->getStoreCurrency->execute($storeCode);
        try {

            if (!$this->getQuickOrderEnable->execute()) {
                return $productList;
            }
            $this->emulation->startEnvironmentEmulation(
                $this->getStoreId->execute($storeCode),
                Area::AREA_FRONTEND,
                true
            );
            $collection = $this->productCollectionFactory->create();

            $product = $collection->addAttributeToSelect(["*"])
                ->addStoreFilter($this->storeManagerInterface->getStore()->getId())
                ->addFieldToFilter('sku', $sku)
                ->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addStoreFilter($this->getStoreId->execute($storeCode))
                ->setVisibility(['in' => [Visibility::VISIBILITY_IN_SEARCH, Visibility::VISIBILITY_IN_CATALOG,
                    Visibility::VISIBILITY_BOTH]])
                ->getLastItem();
            $product->getTierPrices();
            $productData = $product->getData();

            $productData['currency_code'] = $this->currencyCode;
            $productData['currency_symbol'] = $this->getCurrencySymbol->execute($this->currencyCode);
            $productData['product_url'] = $product->getProductUrl();
            $productData['thumbnail'] = $product->getProductUrl();

            $productData['thumbnail'] = $this->imageFactory->create($product, 'cart_page_product_thumbnail', [])
                ->getImageUrl();

            if (!empty($productData['price'])) {
                $price = $this->convertCurrency->execute($productData['price'], $this->currencyCode);
                $productData['price'] = $price;
                $productData['default_price'] = $price;
            }

            $stock = $this->stockItemRepository->get($productData['entity_id'])->getQty();

            switch ($productData['type_id']) {
                case Configurable::TYPE_CODE:
                    $productAttributes = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
                    $activeAttributes = $this->getActiveAttributes($productAttributes);
                    $usedProducts = $this->getUsedProducts($product);

                    $productData['attributes'] = array_values($productAttributes);
                    $productData['active_product'] = $this->getActiveProduct($usedProducts, $activeAttributes);
                    $productData['used_products'] = $usedProducts;
                    break;
                case Type::TYPE_BUNDLE:
                    $optionsCollection = $product->getTypeInstance()->getOptionsCollection($product);
                    $optionSelectionMapping = [];

                    $optionData = [];
                    foreach ($optionsCollection as $option) {
                        $optionSelectionMapping[$option->getOptionId()] = $option->getDefaultTitle();
                        $optionData[$option->getOptionId()]['option_id'] = $option->getOptionId();
                        $optionData[$option->getOptionId()]['option_title'] = $option->getDefaultTitle();
                        $optionData[$option->getOptionId()]['option_type'] = $option->getType();
                        $optionData[$option->getOptionId()]['require'] = $option->getRequired();
                        $optionData[$option->getOptionId()]['position'] = $option->getPosition();
                        $optionData[$option->getOptionId()]['fast_option_selected'] = [];
                    }
                    $productData['quick_option_label'] = $optionData;

                    /* @var \Magento\Bundle\Model\ResourceModel\Selection\Collection $collection */
                    $collection = $product->getTypeInstance()
                        ->getSelectionsCollection($product->getTypeInstance()->getOptionsIds($product), $product);

                    $selectionArr = [];
                    $selectionDefaultPrice = 0;

                    foreach ($collection as $selection) {
                        $selectionData = [];
                        $selectionBasePriceValue = $this->convertCurrency->execute(
                            $selection->getPrice(),
                            $this->currencyCode
                        );

                        $selectionData['selection_id'] = $selection->getSelectionId();
                        $selectionData['selection_title'] = $optionSelectionMapping[$selection->getOptionId()];
                        $selectionData['option_id'] = $selection->getOptionId();
                        $selectionData['selection_product_name'] = $selection->getName();
                        $selectionData['selection_base_price_value'] = $selectionBasePriceValue;
                        $selectionData['selection_product_price_format'] =
                            $this->priceCurrency->convertAndFormat($selection->getPrice(), false);
                        $selectionData['selection_product_qty'] = (int)$selection->getSelectionQty();
                        $selectionData['selection_product_id'] = $selection->getProductId();
                        $selectionData['selection_is_default'] = $selection->getIsDefault();
                        $selectionData['selection_price_type'] = $selection->getSelectionPriceType();
                        $selectionData['selection_can_change_qty'] = $selection->getSelectionCanChangeQty();
                        $selectionData['selection_position'] = $selection->getPosition();
                        $selectionData['selection_type'] = $optionData[$selection->getOptionId()]['option_type'];
                        $selectionData['selection_require'] = $optionData[$selection->getOptionId()]['require'];

                        if ($selection->getIsDefault() === '1') {
                            $selectionDefaultPrice = $selectionDefaultPrice +
                                round($selectionBasePriceValue, 2) * (int)$selection->getSelectionQty();
                        }

                        $selectionArr[$selection->getOptionId()][] = $selectionData;
                    }

                    $productData['active_selections'] = $this->getActiveSelections($selectionArr);
                    $productData['price'] = $selectionDefaultPrice;
                    $productData['default_price'] = $selectionDefaultPrice;
                    $productData['quick_selection_array'] = $selectionArr;
                    break;
                case 'grouped':
                    $associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);

                    $listProduct = [];
                    $totalPrice = 0;
                    $activeSelections = [];
                    foreach ($associatedProducts as $associatedProduct) {
                        $associatedProduct->getTierPrices();
                        $activeSelectionsTmp = [];
                        $associatedProductData = $associatedProduct->getData();
                        if (!empty($associatedProductData['price'])) {
                            $productTmpPrice = $associatedProductData['price'];
                            $newTmpPrice = $this->convertCurrency->execute($productTmpPrice, $this->currencyCode);
                            $associatedProductData['base_price_value'] = $this->
                            convertCurrency->execute($associatedProductData['price'], $this->currencyCode);
                            $associatedProductData['converted_new_price_value'] = $newTmpPrice;
                            $associatedProductData['price'] = $this->
                            convertCurrency->execute($associatedProductData['price'], $this->currencyCode);

                            $totalPrice =
                                $totalPrice + round($newTmpPrice, 2) * (int)$associatedProductData['qty'];
                        }
                        $activeSelectionsTmp['id'] = $associatedProduct['entity_id'];
                        $activeSelectionsTmp['qty'] = $associatedProduct['qty'];
                        $activeSelections[] = $activeSelectionsTmp;
                        $associatedProductData['qty'] = (int)$associatedProductData['qty'];
                        $associatedProductData['image'] = $associatedProductData['thumbnail'] = $this->imageFactory
                            ->create($associatedProduct, 'product_base_image', [])
                            ->getImageUrl();
                        $listProduct[] = $associatedProductData;
                    }

                    usort($listProduct, function ($a, $b) {
                        return $a['position'] <=> $b['position'];
                    });

                    $productData['active_selections'] = $activeSelections;
                    $productData['price'] = $totalPrice;
                    $productData['default_price'] = $totalPrice;
                    $productData['quick_grouped_products'] = $listProduct;
                    break;
                default:
                    break;
            }

            if (!empty($productData['has_options']) && !empty($productData['required_options'])) {
                $product = $this->productRepository->getById($product->getId());
                $options = $product->getOptions();
                $optionsArray = [];
                foreach ($options as $option) {
                    if (!empty($option->getValues())) {
                        $tmpPriceValues = array_map(function ($value) {
                            return $this->getPriceConfiguration($value);
                        }, $option->getValues());
                        $priceValue = $tmpPriceValues;
                    } else {
                        $priceValue = $this->getPriceConfiguration($option);
                    }
                    $optionData = $option->getData();
                    $optionData['prices'] = $priceValue;
                    $optionsArray[] = $optionData;
                }
                $productData['custom_options'] = $optionsArray;
            }
            $productData['active_custom_options'] = [];
            $productData['qty'] = 1;
            $productData['stock'] = $stock;
            $productList[] = $productData;

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

    /**
     * Get active attributes
     *
     * @param array $productAttOptions
     * @return array
     */
    private function getActiveAttributes(array $productAttOptions)
    {
        $attributeArray = [];

        foreach ($productAttOptions as $productAttribute) {
            foreach ($productAttribute['values'] as $attribute) {
                $attributeArray[$productAttribute['attribute_code']] = $attribute['value_index'];
                break;
            }
        }

        return $attributeArray;
    }

    /**
     * Get active selections
     *
     * @param array $selectionArr
     * @return array
     */
    private function getActiveSelections(array $selectionArr): array
    {
        $selectionArray = [];
        foreach ($selectionArr as $key => $selection) {
            $selectionArrayTemp = [];
            $selectionArrayTemp['id'] = $key;
            foreach ($selection as $selectionItem) {
                $selectionArrayInnerTemp = [];
                $selectionArrayInnerTemp['qty'] = $selectionItem['selection_product_qty'];
                $selectionArrayInnerTemp['value_id'] = $selectionItem['selection_id'];
                $selectionArrayInnerTemp['change_qty'] = $selectionItem['selection_can_change_qty'];
                if ($selectionItem['selection_is_default']) {
                    $selectionArrayInnerTemp['value'] = true;
                } else {
                    $selectionArrayInnerTemp['value'] = false;
                }
                $selectionArrayTemp['selection_value'][] = $selectionArrayInnerTemp;
            }

            $selectionArray[] = $selectionArrayTemp;
        }

        return $selectionArray;
    }

    /**
     * Get active product
     *
     * @param array $usedProducts
     * @param array $activeAttributes
     * @return Product|null
     */
    private function getActiveProduct(array $usedProducts, array $activeAttributes): ?array
    {
        $activeProduct = null;

        foreach ($activeAttributes as $activeAttributeCode => $activeAttributeValue) {
            foreach ($usedProducts as $usedProduct) {
                if (!empty($usedProduct[$activeAttributeCode] &&
                    $usedProduct[$activeAttributeCode] === $activeAttributeValue)) {
                    $activeProduct = $usedProduct;
                }
                break 2;
            }
        }

        return $activeProduct;
    }

    /**
     * Get used products
     *
     * @param Product $product
     * @return array
     * @throws NoSuchEntityException
     */
    private function getUsedProducts(Product $product): array
    {
        $data = [];
        $imageAttribute = $this->productAttributeRepository->get('image');
        $usedProducts = $product->getTypeInstance()->getUsedProducts($product, [$imageAttribute->getAttributeId()]);

        if (!empty($usedProducts)) {
            foreach ($usedProducts as $product) {
                $productArray = $product->toArray();
                $productArray['stock'] = $this->stockItemRepository->get($product->getId())->getQty();
                $productArray['price'] = $this->convertCurrency->execute($productArray['price'], $this->currencyCode);
                $productArray['thumbnail'] = $this->imageFactory->create($product, 'product_base_image', [])
                    ->getImageUrl();

                $data[] = $productArray;
            }
        }

        return $data;
    }

    /**
     * Get price configuration
     *
     * @param Value|Option $option
     * @return array
     */
    protected function getPriceConfiguration(Value|Option $option)
    {
        $optionPrice = (string)$option->getPrice();
        if ($option->getPriceType() !== Value::TYPE_PERCENT) {
            $optionPrice = $this->convertCurrency->execute($optionPrice, $this->currencyCode);
        }

        return [
            'prices' => [
                'oldPrice' => [
                    'amount' => $this->convertCurrency->execute((string)$option->getRegularPrice(), $this->currencyCode)
                ],
                'basePrice' => [
                    'amount' => $this->catalogData->getTaxPrice(
                        $option->getProduct(),
                        $optionPrice,
                        false,
                        null,
                        null,
                        null,
                        null,
                        null,
                        false
                    ),
                ],
                'finalPrice' => [
                    'amount' => $this->catalogData->getTaxPrice(
                        $option->getProduct(),
                        $optionPrice,
                        true,
                        null,
                        null,
                        null,
                        null,
                        null,
                        false
                    ),
                ],
            ],
            'type' => $option->getPriceType(),
            'name' => $option->getTitle(),
        ];
    }
}
