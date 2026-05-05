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

namespace BroSolutions\QuickOrder\Service\Quote\Product\Type;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

/**
 * Handles adding configurable products to the quote.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class ConfigurableStrategy implements TypeStrategyInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function addToQuote(Quote $quote, ProductInterface $product, array $itemData, int $storeId): void
    {
        $buyRequest = new DataObject(['qty' => (float)($itemData['qty'] ?? 1)]);
        $superAttributes = [];

        if (!empty($itemData['attributes']) && is_array($itemData['attributes'])) {
            foreach ($itemData['attributes'] as $attr) {
                if (!empty($attr['attribute_id']) && !empty($attr['attribute_code'])) {
                    $code = $attr['attribute_code'];
                    if (!empty($itemData['active_product'][$code])) {
                        $superAttributes[$attr['attribute_id']] = $itemData['active_product'][$code];
                    }
                }
            }
        }

        if (!empty($superAttributes)) {
            $buyRequest->setData('super_attribute', $superAttributes);
        }

        $quote->addProduct($product, $buyRequest);
    }

    /**
     * @inheritDoc
     */
    public function calculatePrice(ProductInterface $product, array $itemData, ?int $customerGroupId = null): float
    {
        if (!empty($itemData['active_product']['entity_id'])) {
            try {
                /** @var Product $childProduct */
                $childProduct = $this->productRepository->getById($itemData['active_product']['entity_id']);
                if ($customerGroupId !== null) {
                    $childProduct->setCustomerGroupId($customerGroupId);
                }
                return (float)$childProduct->getFinalPrice();
            } catch (Exception $e) {
                $this->logger->warning('Could not load configurable child product: ' . $e->getMessage());
            }
        }

        /** @var Product $productModel */
        $productModel = $product;

        if ($customerGroupId !== null) {
            $productModel->setCustomerGroupId($customerGroupId);
        }

        return (float)$productModel->getFinalPrice();
    }
}
