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

use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Random\RandomException;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class FilterProducts
{
    /**
     * @param array $products
     * @param array $options
     * @return array
     * @throws RandomException
     */
    public function execute(array $products, array $options): array
    {
        $data = [];
        foreach ($products as $product) {
            $product['pid'] = random_int(1000000000000, 9999999999999);

            switch ($product['type_id']) {
                case Configurable::TYPE_CODE:
                    $data[] = $this->processConfigurable($product, $options);
                    break;
                case Type::TYPE_BUNDLE:
                    $data[] = $this->processBundle($product, $options);
                    break;
                case 'grouped':
                    $data[] = $this->processGrouped($product, $options);
                    break;
                case 'simple':
                    $data[] = $this->processSimple($product, $options);
                    break;
            }
        }

        return $data;
    }

    /**
     * @param array $product
     * @param array $options
     * @return array
     */
    private function processConfigurable(array $product, array $options): array
    {
        if (empty($option = $this->getOptionFromProduct($product, $options))) {
            return $product;
        }
        foreach ($product['used_products'] as $usedProduct) {
            if ($this->hasExactOptions($usedProduct, $option[0]['options'])) {
                $product['active_product'] =  $usedProduct;
                $product['qty'] = $product['qty'];
                break;
            }
        }
        $qty = $this->getQtyFromOptions($product['sku'], $options);
        $product['qty'] = $qty;
        return $product;
    }

    /**
     * @param array $product
     * @param array $options
     * @return array
     */
    private function processBundle(array $product, array $options)
    {
        if (empty($option = $this->getOptionFromProduct($product, $options))) {
            return $product;
        }
        if (empty($product['qty'])) {
            $product['qty'] = 1;
            return $product;
        }
        $data = [];
        foreach ($product['active_selections'] as $activeSelection) {
            $selectionValueArr = [];
            foreach ($activeSelection['selection_value'] as $selectionValue) {
                $selectionValue['value'] = false;
                $qty = $this->getQtyByLabelValue($option[0], $selectionValue['title'], $selectionValue['product_name']);
                if ($qty) {
                    $selectionValue['value'] = true;
                    $selectionValue['qty'] = $qty;
                }
                $selectionValueArr[] = $selectionValue;
            }
            $activeSelection['selection_value'] = $selectionValueArr;
            $data[] = $activeSelection;
        }
        $product['active_selections'] = $data;
        $product['qty'] = $this->getQtyFromOptions($product['sku'], $options);
        return $product;
    }

    /**
     * @param array $product
     * @param array $options
     * @return array
     */
    private function processGrouped(array $product, array $options)
    {
        if (empty($option = $this->getOptionFromProduct($product, $options))) {
            return $product;
        }

        $data = [];
        foreach ($product['active_selections'] as $activeSelection) {
            $qty = $this->getQtyByActiveSelection($option[0], $activeSelection['name']);
            if ($qty) {
                $activeSelection['qty'] = $qty;
            }
            $data[] = $activeSelection;

        }
        $product['active_selections'] = $data;
        $product['qty'] = $this->getQtyFromOptions($product['sku'], $options);

        return $product;
    }

    /**
     * @param array $product
     * @param array $options
     * @return array
     */
    private function processSimple(array $product, array $options): array
    {
        $product['qty'] = $product['qty'];
        return $product;
    }


    /**
     * @param array $product
     * @param array $expectedOptions
     * @return bool
     */
    public function hasExactOptions(array $product, array $expectedOptions): bool
    {
        if (!isset($product['options']) || !is_array($product['options'])) {
            return false;
        }

        $actualOptions = [];
        foreach ($product['options'] as $opt) {
            $actualOptions[$opt['option_name']] = $opt['option_value'];
        }

        $normalizedExpected = [];
        foreach ($expectedOptions as $opt) {
            $normalizedExpected[$opt['label']] = $opt['value'];
        }

        if (count($actualOptions) !== count($normalizedExpected)) {
            return false;
        }

        foreach ($normalizedExpected as $name => $value) {
            if (!isset($actualOptions[$name]) || $actualOptions[$name] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $product
     * @param string $label
     * @param string $value
     * @return int
     */
    public function getQtyByLabelValue(array $product, string $label, string $value): int
    {
        $totalQty = 0;
        foreach ($product['options'] as $opt) {
            if (isset($opt['label'], $opt['value'], $opt['qty']) && $opt['label'] === $label && $opt['value'] === $value) {
                return (int)$opt['qty'];
            }
        }

        return $totalQty;
    }

    /**
     * @param array $product
     * @param string $name
     * @return int
     */
    public function getQtyByActiveSelection(array $product, string $name): int
    {
        $totalQty = 0;
        foreach ($product['options'] as $opt) {
            if (isset($opt['label']) && $opt['label'] === $name) {
                return (int)$opt['value'];
            }
        }

        return $totalQty;
    }

    /**
     * @param $product
     * @param $options
     * @return array|null
     */
    private function getOptionFromProduct($product, $options): ?array
    {
        $skuToFind = $product['sku'];

        $option = array_values(array_filter($options, function ($item) use ($skuToFind) {
            return $item['sku'] === $skuToFind;
        }));

        if (empty($option[0]['options'])) {
            return null;
        }

        return $option;
    }

    /**
     * @param string $sku
     * @param array $options
     * @return int
     */
    private function getQtyFromOptions(string $sku, array $options): int
    {
        foreach ($options as $opt) {
            if ($opt['sku'] === $sku) {
                return $opt['qty'];
            }
        }
        return 0;
    }
}
