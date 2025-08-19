<?php
/**
 * Copyright (c) 2024 BroSolutions
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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class GetChildBySuperAttributes
{
    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Attribute
     */
    private $attribute;

    /**
     * @param Configurable $configurableType
     * @param ProductRepositoryInterface $productRepository
     * @param Attribute $attribute
     */
    public function __construct(
        Configurable $configurableType,
        ProductRepositoryInterface $productRepository,
        Attribute $attribute
    ) {
        $this->configurableType   = $configurableType;
        $this->productRepository  = $productRepository;
        $this->attribute        = $attribute;
    }

    /**
     * @param string $parentSku SKU конфигурируемого продукта
     * @param array $superAttributes ['attribute_id' => value_id]
     * @return ProductInterface|null
     * @throws NoSuchEntityException
     */
    public function execute($parentSku, array $superAttributes): ProductInterface|null
    {
        $parentProduct = $this->productRepository->get($parentSku);
        $childProducts = $this->configurableType->getUsedProducts($parentProduct);
        foreach ($childProducts as $child) {
            $match = true;
            foreach ($superAttributes as $attributeId => $optionId) {
                $value = $child->getData($this->getAttributeCodeById($attributeId));
                if ((int)$value !== (int)$optionId) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return $child;
            }
        }

        return null;
    }

    /**
     * @param $attributeId
     * @return mixed
     */
    private function getAttributeCodeById($attributeId)
    {
        return $this->attribute->load($attributeId)->getAttributeCode();
    }
}
