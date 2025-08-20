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

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class GetProductsData
{
    /**
     * @param $productsData
     * @return array
     */
    public function execute($productsData): array
    {
        $productData = [];
        $optionsTotal = 0;

        foreach ($productsData as $productDataItem) {
            $options = [];

            switch ($productDataItem['type_id']) {
                case Configurable::TYPE_CODE:
                    foreach ($productDataItem['active_product']['options'] as $option) {
                        $options[] = $option['option_name'] . ':' . $option['option_value'];
                    }

                    break;
                case Type::TYPE_BUNDLE:
                    foreach ($productDataItem['active_selections'] as $activeSelections) {
                        foreach ($activeSelections['selection_value'] as $activeSelection) {
                            if ($activeSelection['value']) {
                                $options[] = $activeSelection['title'] . ':' .
                                    $activeSelection['product_name'] . ':' .
                                    $activeSelection['qty'];
                            }
                        }
                    }

                    break;
                case 'grouped':
                    foreach ($productDataItem['active_selections'] as $activeSelection) {
                        $options[] = $activeSelection['name'] . ':' . (int)$activeSelection['qty'];
                    }

                    break;
                default:
                    break;
            }
            $productData[] = array_merge([
                $productDataItem['sku'],
                $productDataItem['qty'],

            ], $options);

            if (count($options) > $optionsTotal) {
                $optionsTotal = count($options);
            }
        }

        $data['productData'] = $productData;
        $data['total'] = $optionsTotal;

        return $data;
    }
}
