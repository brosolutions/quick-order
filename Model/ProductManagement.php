<?php
/**
 * Copyright (c) 2025 BroSolutions
 * All rights reserved
 *
 * Proprietary software developed at BroSolutions, Ukraine
 * More info: https://www.brosolutions.net/
 * Contact: contact@brosolutions.net
 */
declare(strict_types=1);

namespace BroSolutions\QuickOrder\Model;

use BroSolutions\QuickOrder\Api\ProductManagementInterface;
use BroSolutions\QuickOrder\Service\ConvertCurrency;
use BroSolutions\QuickOrder\Service\GetCurrencySymbol;
use BroSolutions\QuickOrder\Service\GetQuickOrderEnable;
use BroSolutions\QuickOrder\Service\GetStoreCurrency;
use BroSolutions\QuickOrder\Service\GetStoreId;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ProductManagement implements ProductManagementInterface
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var GetQuickOrderEnable
     */
    private $quickOrderChecker;

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
    private $stockRepository;

    /**
     * @var CatalogHelper
     */
    private $catalogHelper;

    /**
     * @var GetStoreCurrency
     */
    private $storeCurrencyService;

    /**
     * @var GetStoreId
     */
    private $storeIdService;

    /**
     * @var GetCurrencySymbol
     */
    private $currencySymbolService;

    /**
     * @var ConvertCurrency
     */
    private $currencyConverter;

    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * @var Emulation
     */
    private $storeEmulation;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var string
     */
    private string $currencyCode;

    /**
     * @param CatalogHelper $catalogHelper
     * @param GetQuickOrderEnable $quickOrderChecker
     * @param LoggerInterface $logger
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param StockItemRepository $stockRepository
     * @param StoreManagerInterface $storeManager
     * @param GetStoreCurrency $storeCurrencyService
     * @param GetStoreId $storeIdService
     * @param GetCurrencySymbol $currencySymbolService
     * @param ConvertCurrency $currencyConverter
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param ImageFactory $imageFactory
     * @param Emulation $storeEmulation
     */

    public function __construct(
        CatalogHelper                       $catalogHelper,
        GetQuickOrderEnable                 $quickOrderChecker,
        LoggerInterface                     $logger,
        ProductCollectionFactory            $productCollectionFactory,
        ProductRepositoryInterface          $productRepository,
        StockItemRepository                 $stockRepository,
        StoreManagerInterface               $storeManager,
        GetStoreCurrency                    $storeCurrencyService,
        GetStoreId                          $storeIdService,
        GetCurrencySymbol                   $currencySymbolService,
        ConvertCurrency                     $currencyConverter,
        ProductAttributeRepositoryInterface $attributeRepository,
        ImageFactory                        $imageFactory,
        Emulation                           $storeEmulation
    ) {
        $this->catalogHelper = $catalogHelper;
        $this->quickOrderChecker = $quickOrderChecker;
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->stockRepository = $stockRepository;
        $this->storeManager = $storeManager;
        $this->storeCurrencyService = $storeCurrencyService;
        $this->storeIdService = $storeIdService;
        $this->currencySymbolService = $currencySymbolService;
        $this->currencyConverter = $currencyConverter;
        $this->attributeRepository = $attributeRepository;
        $this->imageFactory = $imageFactory;
        $this->storeEmulation = $storeEmulation;
    }

    /**
     * @param string $sku
     * @param string $storeCode
     * @return array
     */
    public function getProduct(string $sku, string $storeCode): array
    {
        $products = [];
        $this->currencyCode = $this->storeCurrencyService->execute($storeCode);

        try {
            if (!$this->quickOrderChecker->execute()) {
                return $products;
            }

            $this->storeEmulation->startEnvironmentEmulation(
                $this->storeIdService->execute($storeCode),
                Area::AREA_FRONTEND,
                true
            );

            $collection = $this->productCollectionFactory->create();
            $product = $collection->addAttributeToSelect('*')
                ->addStoreFilter($this->storeManager->getStore()->getId())
                ->addFieldToFilter('sku', $sku)
                ->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addStoreFilter($this->storeIdService->execute($storeCode))
                ->setVisibility([
                    'in' => [
                        Visibility::VISIBILITY_IN_SEARCH,
                        Visibility::VISIBILITY_IN_CATALOG,
                        Visibility::VISIBILITY_BOTH
                    ]
                ])
                ->getLastItem();

            $product->getTierPrices();
            $data = $product->getData();
            $data['currency_code'] = $this->currencyCode;
            $data['currency_symbol'] = $this->currencySymbolService->execute($this->currencyCode);
            $data['product_url'] = $product->getProductUrl();
            $data['thumbnail'] = $this->imageFactory->create($product, 'cart_page_product_thumbnail', [])->getImageUrl();

            if (!empty($data['price'])) {
                $converted = $this->currencyConverter->execute($data['price'], $this->currencyCode);
                $data['price'] = $data['default_price'] = $converted;
            }

            $data['stock'] = $this->stockRepository->get($data['entity_id'])->getQty();
            $data['qty'] = 1;

            switch ($data['type_id']) {
                case Configurable::TYPE_CODE:
                    $data = $this->processConfigurable($product, $data);
                    break;
                case Type::TYPE_BUNDLE:
                    $data = $this->processBundle($product, $data);
                    break;
                case 'grouped':
                    $data = $this->processGrouped($product, $data);
                    break;
            }

            $data['custom_options'] = $this->processCustomOptions($product);
            $data['active_custom_options'] = [];

            $products[] = $data;

        } catch (NoSuchEntityException $e) {
            $this->logger->error(sprintf('Error fetching product: %s', $e->getMessage()), $e->getTrace());
        }

        $this->storeEmulation->stopEnvironmentEmulation();
        return $products;
    }

    /**
     * @param Product $product
     * @param array $data
     * @return array
     * @throws NoSuchEntityException
     */
    private function processConfigurable(Product $product, array $data): array
    {
        $attrs = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
        $activeAttrs = $this->getActiveAttributes($attrs);
        $associated = $this->getAssociatedProducts($product);

        $data['attributes'] = array_values($attrs);
        $data['active_product'] = $this->matchVariant($associated, $activeAttrs);
        $data['used_products'] = $associated;

        return $data;
    }

    /**
     * @param Product $product
     * @param array $data
     * @return array
     */
    private function processBundle(Product $product, array $data): array
    {
        $options = $product->getTypeInstance()->getOptionsCollection($product);
        $optionInfo = $optionTitles = [];

        foreach ($options as $opt) {
            $optionTitles[$opt->getOptionId()] = $opt->getDefaultTitle();
            $optionInfo[$opt->getOptionId()] = [
                'option_id' => $opt->getOptionId(),
                'option_type' => $opt->getType(),
                'position' => $opt->getPosition(),
                'require' => $opt->getRequired(),
            ];
        }

        $data['quick_option_label'] = $optionInfo;

        $selections = $product->getTypeInstance()
            ->getSelectionsCollection($product->getTypeInstance()->getOptionsIds($product), $product);

        $selectionArr = [];
        $bundleDefaultPrice = 0;

        foreach ($selections as $sel) {
            $convertedPrice = $this->currencyConverter->execute($sel->getPrice(), $this->currencyCode);
            $selData = [
                'base_price' => $convertedPrice,
                'is_default' => $sel->getIsDefault(),
                'option_id' => $sel->getOptionId(),
                'product_name' => $sel->getName(),
                'qty' => (int)$sel->getSelectionQty(),
                'require' => $optionInfo[$sel->getOptionId()]['require'],
                'selection_id' => $sel->getSelectionId(),
                'title' => $optionTitles[$sel->getOptionId()],
                'type' => $optionInfo[$sel->getOptionId()]['option_type']
            ];

            if ($sel->getIsDefault() === '1') {
                $bundleDefaultPrice += round($convertedPrice, 2) * (int)$sel->getSelectionQty();
            }

            $selectionArr[$sel->getOptionId()][] = $selData;
        }

        $data['active_selections'] = $this->buildSelectionStructure($selectionArr);
        $data['quick_selection_array'] = $selectionArr;
        $data['price'] = $data['default_price'] = $bundleDefaultPrice;

        return $data;
    }

    /**
     * @param Product $product
     * @param array $data
     * @return array
     */
    private function processGrouped(Product $product, array $data): array
    {
        $associated = $product->getTypeInstance()->getAssociatedProducts($product);
        $groupedProducts = [];
        $activeSelections = [];
        $totalPrice = 0;

        foreach ($associated as $child) {
            $child->getTierPrices();
            $childData = $child->getData();
            if (!empty($childData['price'])) {
                $converted = $this->currencyConverter->execute($childData['price'], $this->currencyCode);
                $childData['price'] = $childData['converted_new_price_value'] = $childData['base_price_value'] = $converted;
                $totalPrice += round($converted, 2) * ((int)$childData['qty'] ?? 1);
            }

            $childData['thumbnail'] = $this->imageFactory->create($child, 'product_base_image', [])->getImageUrl();
            $groupedProducts[] = $childData;
            $activeSelections[] = ['id' => $childData['entity_id'], 'qty' => $childData['qty'] ?? 1];
        }

        usort($groupedProducts, fn ($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

        $data['active_selections'] = $activeSelections;
        $data['price'] = $data['default_price'] = $totalPrice;
        $data['quick_grouped_products'] = $groupedProducts;

        return $data;
    }

    /**
     * @param Product $product
     * @return array
     * @throws NoSuchEntityException
     */
    private function processCustomOptions(Product $product): array
    {
        $product = $this->productRepository->getById($product->getId());
        $options = $product->getOptions() ?? [];

        $result = [];

        foreach ($options as $opt) {
            $prices = !empty($opt->getValues())
                ? array_map(fn ($v) => $this->getOptionPriceData($v), $opt->getValues())
                : $this->getOptionPriceData($opt);

            $data = $opt->getData();
            $data['prices'] = $prices;
            $result[] = $data;
        }

        return $result;
    }

    /**
     * @param array $attributes
     * @return array
     */
    private function getActiveAttributes(array $attributes): array
    {
        return array_reduce($attributes, function ($carry, $attr) {
            $val = $attr['values'][0]['value_index'] ?? null;
            if ($val !== null) {
                $carry[$attr['attribute_code']] = $val;
            }
            return $carry;
        }, []);
    }

    /**
     * @param array $dataSet
     * @return array
     */
    private function buildSelectionStructure(array $dataSet): array
    {
        return array_map(function ($key) use ($dataSet) {
            return [
                'id' => $key,
                'selection_value' => array_map(fn ($item) => [
                    'value_id' => $item['selection_id'] ?? null,
                    'value' => (bool)($item['is_default'] ?? false),
                    'change_qty' => $item['can_change_qty'] ?? false,
                    'qty' => $item['qty'] ?? 0
                ], $dataSet[$key] ?? [])
            ];
        }, array_keys($dataSet));
    }

    /**
     * @param array $variants
     * @param array $criteria
     * @return array|null
     */
    private function matchVariant(array $variants, array $criteria): ?array
    {
        foreach ($variants as $variant) {
            if (!empty(array_intersect_assoc($criteria, $variant))) {
                return $variant;
            }
        }
        return null;
    }

    /**
     * @param Product $parent
     * @return array
     * @throws NoSuchEntityException
     */
    private function getAssociatedProducts(Product $parent): array
    {
        $result = [];
        $imgAttrId = (int)$this->attributeRepository->get('image')->getAttributeId();
        $children = $parent->getTypeInstance()->getUsedProducts($parent, [$imgAttrId]);

        if (!$children) {
            return $result;
        }

        foreach ($children as $child) {
            $result[] = $this->extractChildData($child);
        }

        return $result;
    }

    /**
     * @param Product $child
     * @return array
     * @throws NoSuchEntityException
     */
    private function extractChildData(Product $child): array
    {
        $data = $child->toArray();
        $data['stock'] = $this->getStockQty((int)$child->getId());
        $data['price'] = $this->currencyConverter->execute($data['price'], $this->currencyCode);
        $data['thumbnail'] = $this->imageFactory->create($child, 'product_base_image', [])->getImageUrl();
        return $data;
    }

    /**
     * @param int $productId
     * @return float
     * @throws NoSuchEntityException
     */
    private function getStockQty(int $productId): float
    {
        return $this->stockRepository->get($productId)->getQty();
    }

    /**
     * @param Value|Option $opt
     * @return array
     */
    protected function getOptionPriceData(Value|Option $opt): array
    {
        return [
            'name' => $opt->getTitle(),
            'type' => $opt->getPriceType(),
            'prices' => $this->buildPriceSegments($opt)
        ];
    }

    /**
     * @param Value|Option $opt
     * @return array[]
     */
    private function buildPriceSegments(Value|Option $opt): array
    {
        return [
            'finalPrice' => ['amount' => $this->calculateOptionPrice($opt, true)],
            'basePrice' => ['amount' => $this->calculateOptionPrice($opt, false)],
            'oldPrice' => ['amount' => $this->getPreviousOptionPrice($opt)]
        ];
    }

    /**
     * @param Value|Option $opt
     * @param bool $includeTax
     * @return float
     */
    private function calculateOptionPrice(Value|Option $opt, bool $includeTax): float
    {
        return $this->catalogHelper->getTaxPrice($opt->getProduct(), $this->resolveOptionPrice($opt), $includeTax);
    }

    /**
     * @param Value|Option $opt
     * @return float
     */
    private function getPreviousOptionPrice(Value|Option $opt): float
    {
        return $this->currencyConverter->execute((string)$opt->getRegularPrice(), $this->currencyCode);
    }

    /**
     * @param Value|Option $opt
     * @return float|string
     */
    private function resolveOptionPrice(Value|Option $opt)
    {
        $price = (string)$opt->getPrice();
        if ($opt->getPriceType() !== 'percent') {
            $price = $this->currencyConverter->execute($price, $this->currencyCode);
        }
        return $price;
    }
}