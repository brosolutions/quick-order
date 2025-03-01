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

namespace BroSolutions\QuickOrder\Api;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
interface SearchProductsManagementInterface
{
    /**
     * Get products
     *
     * @param string $term
     * @param string $storeCode
     * @return mixed
     */
    public function getProducts(string $term, string $storeCode);
}
