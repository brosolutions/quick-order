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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Handles adding grouped products to the quote.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class GroupedStrategy implements TypeStrategyInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

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
        if (empty($itemData['active_selections'])) {
            return;
        }

        foreach ($itemData['active_selections'] as $sel) {
            if (empty($sel['id']) || empty($sel['qty'])) {
                continue;
            }
            try {
                $childProduct = $this->productRepository->getById($sel['id'], false, $storeId, true);

                if ($childProduct->getStatus() != Status::STATUS_ENABLED || !$childProduct->isSalable()) {
                    continue;
                }

                $childBuyRequest = new DataObject(['qty' => (float)$sel['qty']]);

                $quote->addProduct($childProduct, $childBuyRequest);
            } catch (Exception $e) {
                $this->logger->warning(
                    sprintf(
                        'Failed to add grouped child product ID %s to quote: %s',
                        $sel['id'],
                        $e->getMessage()
                    )
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function calculatePrice(ProductInterface $product, array $itemData): float
    {
        $price = 0.0;
        if (!empty($itemData['active_selections'])) {
            foreach ($itemData['active_selections'] as $sel) {
                try {
                    $childProduct = $this->productRepository->getById($sel['id']);
                    $price += (float)$childProduct->getFinalPrice() * (float)($sel['qty'] ?? 1);
                } catch (Exception $e) {
                    $this->logger->warning(
                        sprintf(
                            'Could not load grouped child product ID %s for price calculation: %s',
                            $sel['id'],
                            $e->getMessage()
                        )
                    );
                }
            }
        }
        return $price;
    }
}
