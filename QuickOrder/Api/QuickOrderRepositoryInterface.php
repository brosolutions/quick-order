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

namespace BroSolutions\QuickOrder\Api;

use BroSolutions\QuickOrder\Api\Data\QuickOrderInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface QuickOrderRepositoryInterface
{
    public function save(QuickOrderInterface $item): QuickOrderInterface;

    public function getById($id): QuickOrderInterface;

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    public function delete(QuickOrderInterface $item): bool;

    public function deleteById($id): bool;
}
