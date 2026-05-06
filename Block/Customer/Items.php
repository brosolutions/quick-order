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

namespace BroSolutions\QuickOrder\Block\Customer;

use BroSolutions\QuickOrder\Model\Item\ProductListItem;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\CollectionFactory as ProductListCollectionFactory;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductListItem as ProductListItemResource;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class Items extends Template
{
    /**
     * @var int|null
     */
    private ?int $listId = null;

    /**
     * @var ProductListCollectionFactory
     */
    private $productListCollectionFactory;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var Session
     */
    private $_customerSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Template\Context $context
     * @param ProductListCollectionFactory $productListCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Template\Context             $context,
        ProductListCollectionFactory $productListCollectionFactory,
        ProductCollectionFactory     $productCollectionFactory,
        Session                      $customerSession,
        StoreManagerInterface        $storeManager,
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        LoggerInterface              $logger,
        array                        $data = []
    ) {
        parent::__construct($context, $data);
        $this->productListCollectionFactory = $productListCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->_customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->logger = $logger;
    }

    /**
     * Product list item renderer
     *
     * @return array
     */
    public function getItems(): array
    {
        $items = [];
        try {
            $flatRows = $this->getFlatRows();
            if (empty($flatRows)) {
                return [];
            }

            // Load and index data
            $rowsByItemId = $this->indexRowsByItemId($flatRows);
            $products = $this->loadProducts($flatRows);
            $parentItemIds = $this->extractParentItemIds($flatRows);

            // Process rows
            $bundleChildren = [];
            $renderedParents = [];

            foreach ($flatRows as $row) {
                $this->processRow(
                    $row,
                    $products,
                    $rowsByItemId,
                    $parentItemIds,
                    $items,
                    $bundleChildren,
                    $renderedParents
                );
            }

            // === FINAL ASSEMBLY OF BUNDLE/GROUPED ===
            $this->assembleBundleItems($bundleChildren, $flatRows, $products, $items);

        } catch (NoSuchEntityException $e) {
            $this->logger->error(sprintf('Error to get list: %s', $e->getMessage()), $e->getTrace());
        }

        return $items;
    }

    /**
     * Index rows by item_id for quick lookup
     *
     * @param array $flatRows
     * @return array
     */
    private function indexRowsByItemId(array $flatRows): array
    {
        $rowsByItemId = [];
        foreach ($flatRows as $row) {
            $rowsByItemId[(int)$row['item_id']] = $row;
        }
        return $rowsByItemId;
    }

    /**
     * Load products from collection, filter by store and status
     *
     * @param array $flatRows
     * @return array
     * @throws NoSuchEntityException
     */
    private function loadProducts(array $flatRows): array
    {
        $productIds = array_unique(array_column($flatRows, 'product_id'));
        $storeId = $this->storeManager->getStore()->getId();

        $productCollection = $this->productCollectionFactory->create()
            ->addStoreFilter($storeId)
            ->addAttributeToSelect('*')
            ->addFieldToFilter('entity_id', ['in' => $productIds])
            ->setFlag('has_stock_status_filter', true)
            ->addAttributeToFilter(
                'status',
                Status::STATUS_ENABLED
            );

        $products = [];
        foreach ($productCollection as $product) {
            $products[(int)$product->getId()] = $product;
        }

        return $products;
    }

    /**
     * Extract parent item IDs from flat rows
     *
     * @param array $flatRows
     * @return array
     */
    private function extractParentItemIds(array $flatRows): array
    {
        $parentItemIds = [];
        foreach ($flatRows as $row) {
            $parentId = (int)($row['parent_id'] ?? 0);
            if ($parentId) {
                $parentItemIds[$parentId] = true;
            }
        }
        return $parentItemIds;
    }

    /**
     * Process single row and categorize it as deleted product, bundle parent, or simple item
     *
     * @param array $row
     * @param array $products
     * @param array $rowsByItemId
     * @param array $parentItemIds
     * @param array $items
     * @param array $bundleChildren
     * @param array $renderedParents
     * @return void
     */
    private function processRow(
        array $row,
        array $products,
        array $rowsByItemId,
        array $parentItemIds,
        array &$items,
        array &$bundleChildren,
        array &$renderedParents
    ): void {
        $productId = (int)$row['product_id'];
        $itemId = (int)$row['item_id'];
        $parentId = (int)($row['parent_id'] ?? 0);

        $product = $products[$productId] ?? null;
        $isMissing = $product === null;
        $typeId = $product ? $product->getTypeId() : null;

        // === DELETED SIMPLE PRODUCT ===
        if ($isMissing && !$parentId && !isset($parentItemIds[$itemId])) {
            $items[] = new ProductListItem([
                'product' => null,
                'qty' => (float)$row['qty'],
                'is_available' => false,
                'is_in_stock' => false,
                'sku' => $row['sku'],
                'type' => 'simple',
            ]);
            return;
        }

        $isAvailable = $this->isProductAvailable($product);
        $isInStock = $this->checkProductInStock($product);

        // === BUNDLE / GROUPED PARENT (EXISTS) ===
        if ($product && in_array($typeId, ['bundle', 'grouped'], true)) {
            $bundleChildren[$itemId] = [
                'child_ids' => [],
                'parent_product' => $product,
                'parent_qty' => (float)$row['qty'],
                'parent_available' => $isAvailable,
                'parent_in_stock' => $isInStock,
                'parent_sku' => $row['sku'],
                'parent_type' => $typeId,
            ];
            $renderedParents[$itemId] = $itemId;
            return;
        }

        // === BUNDLE / GROUPED PARENT (DELETED) ===
        if ($isMissing && !$parentId && isset($parentItemIds[$itemId])) {
            $bundleChildren[$itemId] = [
                'child_ids' => [],
                'parent_product' => null,
                'parent_qty' => (float)$row['qty'],
                'parent_available' => false,
                'parent_in_stock' => false,
                'parent_sku' => $row['sku'],
                'parent_type' => 'bundle',
            ];
            $renderedParents[$itemId] = $itemId;
            return;
        }

        // === CHILD VIA parent_id ===
        if ($parentId && isset($bundleChildren[$parentId])) {
            $bundleChildren[$parentId]['child_ids'][] = $productId;
            return;
        }

        // === SIMPLE WITHOUT PARENT ===
        if ($product && $typeId === 'simple') {
            $configurableAttributes = $this->getConfigurableAttributesForProduct(
                $parentId,
                $rowsByItemId,
                $products
            );

            $items[] = new ProductListItem([
                'product' => $product,
                'qty' => (float)$row['qty'],
                'is_available' => $isAvailable,
                'is_in_stock' => $isInStock,
                'sku' => $row['sku'],
                'type' => $typeId,
                'allowed_attributes' => $configurableAttributes,
            ]);
        }
    }

    /**
     * Check if product is in stock by checking source items
     *
     * @param Product|null $product
     * @return bool
     */
    private function checkProductInStock(?Product $product): bool
    {
        if (!$product) {
            return false;
        }
        foreach ($this->getSourceItemsBySku->execute($product->getSku()) as $sourceItem) {
            if ($sourceItem->getStatus() === SourceItemInterface::STATUS_IN_STOCK
                && $sourceItem->getQuantity() > 0
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get configurable attributes if parent is configurable product
     *
     * @param int $parentId
     * @param array $rowsByItemId
     * @param array $products
     * @return array
     */
    private function getConfigurableAttributesForProduct(
        int   $parentId,
        array $rowsByItemId,
        array $products
    ): array {
        if (!$parentId || !isset($rowsByItemId[$parentId])) {
            return [];
        }

        $parentProductId = (int)($rowsByItemId[$parentId]['product_id'] ?? 0);

        if (!$parentProductId || !isset($products[$parentProductId])) {
            return [];
        }

        $parentProduct = $products[$parentProductId];

        if ($parentProduct->getTypeId() !== 'configurable') {
            return [];
        }

        return $this->getConfigurableAttributes($parentProduct);
    }

    /**
     * Assemble final bundle/grouped items with children
     *
     * @param array $bundleChildren
     * @param array $flatRows
     * @param array $products
     * @param array $items
     * @return void
     */
    private function assembleBundleItems(
        array $bundleChildren,
        array $flatRows,
        array $products,
        array &$items
    ): void {
        foreach ($bundleChildren as $parentItemId => $data) {
            $parentProduct = $data['parent_product'];
            $parentType = $data['parent_type'];
            $childIds = $data['child_ids'];

            $bundleOptionsMap = [];
            if ($parentProduct && $parentProduct->getTypeId() === 'bundle') {
                $bundleOptionsMap = $this->getBundleOptions($parentProduct, $childIds);
            }

            $childItems = [];
            $childItemsQty = [];
            $childItemsOptions = [];

            foreach ($childIds as $childProductId) {
                $this->processChildProduct(
                    $childProductId,
                    $parentItemId,
                    $flatRows,
                    $products,
                    $bundleOptionsMap,
                    $childItems,
                    $childItemsQty,
                    $childItemsOptions
                );
            }

            $items[] = new ProductListItem([
                'product' => $parentProduct,
                'qty' => $data['parent_qty'],
                'is_available' => $data['parent_available'],
                'is_in_stock' => $data['parent_in_stock'],
                'sku' => $data['parent_sku'],
                'type' => $parentType,
                'child_items' => $childItems,
                'child_items_qty' => $childItemsQty,
                'child_items_options' => $childItemsOptions,
            ]);
        }
    }

    /**
     * Process single child product and collect its data
     *
     * @param int $childProductId
     * @param int $parentItemId
     * @param array $flatRows
     * @param array $products
     * @param array $bundleOptionsMap
     * @param array $childItems
     * @param array $childItemsQty
     * @param array $childItemsOptions
     * @return void
     */
    private function processChildProduct(
        int   $childProductId,
        int   $parentItemId,
        array $flatRows,
        array $products,
        array $bundleOptionsMap,
        array &$childItems,
        array &$childItemsQty,
        array &$childItemsOptions
    ): void {
        $childProduct = $products[$childProductId] ?? null;

        // Find child quantity from flat rows
        $qty = $this->findChildQuantityInRows($childProductId, $parentItemId, $flatRows);

        // === Child product missing ===
        if (!$childProduct) {
            $childRow = $this->findChildRowByProductId($childProductId, $flatRows);

            $childItems[] = new ProductListItem([
                'product' => null,
                'qty' => $qty,
                'is_available' => false,
                'is_in_stock' => false,
                'sku' => $childRow['sku'] ?? '',
                'type' => null,
            ]);
            return;
        }

        $childInStock = $this->checkProductInStock($childProduct);

        $childItems[] = new ProductListItem([
            'product' => $childProduct,
            'qty' => $qty,
            'is_available' => $this->isProductAvailable($childProduct),
            'is_in_stock' => $childInStock,
            'sku' => $childProduct->getSku(),
            'type' => $childProduct->getTypeId(),
        ]);

        $childItemsQty[$childProductId] = $qty;

        if (isset($bundleOptionsMap[$childProductId])) {
            $childItemsOptions[$childProductId] = $bundleOptionsMap[$childProductId];
        }
    }

    /**
     * Find child product quantity by matching product_id and parent_id in rows
     *
     * @param int $childProductId
     * @param int $parentItemId
     * @param array $flatRows
     * @return float
     */
    private function findChildQuantityInRows(
        int   $childProductId,
        int   $parentItemId,
        array $flatRows
    ): float {
        foreach ($flatRows as $row) {
            if ((int)$row['product_id'] === $childProductId &&
                (int)($row['parent_id'] ?? 0) === $parentItemId) {
                return (float)$row['qty'];
            }
        }
        return 0;
    }

    /**
     * Find child row by product_id
     *
     * @param int $childProductId
     * @param array $flatRows
     * @return array|null
     */
    private function findChildRowByProductId(int $childProductId, array $flatRows): ?array
    {
        foreach ($flatRows as $row) {
            if ((int)$row['product_id'] === $childProductId) {
                return $row;
            }
        }
        return null;
    }

    /**
     * Check if product available
     *
     * @param Product|null $product
     * @return bool
     */
    private function isProductAvailable(?Product $product): bool
    {
        return $product !== null
            && (int)$product->getStatus() === Status::STATUS_ENABLED;
    }

    /**
     * Get super attribute values for a child configurable product
     *
     * @param Product $parentProduct
     * @return array
     */
    private function getConfigurableAttributes(Product $parentProduct): array
    {
        $attributeCodes = [];

        if ($parentProduct->getTypeId() !== 'configurable') {
            return $attributeCodes;
        }

        /** @var Configurable $typeInstance */
        $typeInstance = $parentProduct->getTypeInstance();
        $superAttributes = $typeInstance->getConfigurableAttributes($parentProduct);

        foreach ($superAttributes as $attribute) {
            $productAttribute = $attribute->getProductAttribute();
            if ($productAttribute) {
                $attributeCodes[] = $productAttribute->getAttributeCode();
            }
        }

        return $attributeCodes;
    }

    /**
     * Get flat rows
     *
     * @return array
     */
    private function getFlatRows(): array
    {
        try {
            $listId = (int)$this->getRequest()->getParam('list_id');
            if (!$listId) {
                return [];
            }

            $collection = $this->productListCollectionFactory->create();

            $collection->getSelect()
                ->joinLeft(
                    ['items' => $collection->getTable(ProductListItemResource::QUICK_ORDER_LIST_ITEM_TABLE)],
                    'main_table.id = items.list_id',
                    [
                        'item_id' => 'items.id',
                        'product_id',
                        'sku',
                        'parent_id',
                        'qty',
                        'item_created_at' => 'items.created_at'
                    ]
                );

            $collection
                ->addFieldToSelect('*')
                ->addFieldToFilter('main_table.store_id', $this->storeManager->getStore()->getId())
                ->addFieldToFilter('main_table.customer_id', $this->_customerSession->getCustomerId())
                ->addFieldToFilter('main_table.id', $listId);

            $collection->getSelect()->order('items.parent_id ASC');
            $collection->getSelect()->order('items.id ASC');
        } catch (NoSuchEntityException $e) {
            return [];
        }

        return $collection->getData();
    }

    /**
     * Get options for bundles simple products
     *
     * @param Product $product
     * @param array $childItemsIds
     * @return array
     */
    private function getBundleOptions(Product $product, array $childItemsIds = []): array
    {
        if (empty($childItemsIds)) {
            return [];
        }

        $mapping = [];
        $childItemsIds = array_map('intval', $childItemsIds);
        $childItemsIdsIndex = array_flip($childItemsIds);

        $typeInstance = $product->getTypeInstance();

        $options = $typeInstance->getOptionsCollection($product);
        if (!$options || !$options->getSize()) {
            return [];
        }

        $optionIds = $typeInstance->getOptionsIds($product);
        if (empty($optionIds)) {
            return [];
        }

        $selections = $typeInstance->getSelectionsCollection($optionIds, $product);
        if ($selections) {
            $options->appendSelections($selections);
        }

        foreach ($options as $option) {
            $optionTitle = (string)$option->getDefaultTitle();
            $optionSelections = $option->getSelections();

            if (!$optionSelections) {
                continue;
            }

            foreach ($optionSelections as $selection) {
                $simpleProductId = (int)$selection->getProductId();

                if (!isset($childItemsIdsIndex[$simpleProductId])) {
                    continue;
                }

                $mapping[$simpleProductId] = [
                    'bundle_id' => (int)$product->getId(),
                    'option_id' => (int)$option->getId(),
                    'option_title' => $optionTitle,
                    'selection_title' => (string)$selection->getName(),
                    'final_price' => (float)$selection->getFinalPrice(),
                ];
            }
        }

        uksort($mapping, static function ($a, $b) use ($childItemsIdsIndex) {
            return $childItemsIdsIndex[$a] <=> $childItemsIdsIndex[$b];
        });

        return $mapping;
    }

    /**
     * Render Item
     *
     * @param ProductListItem $item
     * @return string
     */
    public function renderItem(ProductListItem $item): string
    {
        $rendererList = $this->getChildBlock('product.list.items.renderers');
        $type = $item->getProductType();

        if (!$rendererList) {
            throw new \LogicException('Renderer list block not found');
        }
        if ($type === 'configurable') {
            $type = 'simple';
        }

        $renderer = $rendererList->getChildBlock($type);
        if (!$renderer) {
            throw new \LogicException(
                sprintf('No renderer found for product type "%s"', $type)
            );
        }
        $renderer->setItem($item);

        return $renderer->toHtml();
    }

    /**
     * Delete list url
     *
     * @return string
     */
    public function getDeleteListUrl(): string
    {
        return $this->getUrl('quickorder/list/deletelist', [
            'list_id' => $this->getListId()
        ]);
    }

    /**
     * Get clone list url
     *
     * @return string
     */
    public function getCloneListUrl(): string
    {
        return $this->getUrl('quickorder/list/clonelist', [
            'list_id' => $this->getListId()
        ]);
    }

    /**
     * Get list name url
     *
     * @return string
     */
    public function getChangeListNameUrl(): string
    {
        return $this->getUrl('quickorder/list/changelistname', [
            'list_id' => $this->getListId()
        ]);
    }

    /**
     * Get add to cart url
     *
     * @return string
     */
    public function getAddToCartUrl(): string
    {
        return $this->getUrl('quickorder/list/addtocart', [
            'list_id' => $this->getListId()
        ]);
    }

    /**
     * Get list id
     *
     * @return int
     */
    private function getListId(): int
    {
        if ($this->listId === null) {
            $this->listId = (int)$this->getRequest()->getParam('list_id');
        }

        return $this->listId;
    }

    /**
     * Get list name
     *
     * @return string
     */
    public function getListName(): string
    {
        try {
            $listId = $this->getListId();
            if (!$listId) {
                return '';
            }

            $collection = $this->productListCollectionFactory->create();
            $collection->addFieldToSelect('list_name')
                ->addFieldToFilter('id', $listId);

            $list = $collection->getFirstItem();

            return (string)$list->getData('list_name');
        } catch (Exception $e) {
            return '';
        }
    }
}
