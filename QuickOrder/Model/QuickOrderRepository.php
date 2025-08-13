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

namespace BroSolutions\QuickOrder\Model;

use BroSolutions\QuickOrder\Api\QuickOrderRepositoryInterface;
use BroSolutions\QuickOrder\Api\Data\QuickOrderInterface;
use BroSolutions\QuickOrder\Model\ResourceModel\QuickOrder as QuickOrderResource;
use BroSolutions\QuickOrder\Model\ResourceModel\QuickOrder\CollectionFactory;
use Exception;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class QuickOrderRepository
{

    protected $resource;
    protected $quickOrderFactory;
    protected $collectionFactory;
    protected $searchResultsFactory;
    protected $collectionProcessor;

    public function __construct(
        QuickOrderResource           $resource,
        QuickOrderFactory            $quickOrderFactory,
        CollectionFactory            $collectionFactory,
        SearchResultsFactory         $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->quickOrderFactory = $quickOrderFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    public function save(QuickOrderInterface $quickOrder): QuickOrderInterface
    {
        try {
            $this->resource->save($quickOrder);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        return $quickOrder;
    }

    public function getById($id): QuickOrderInterface
    {
        $quickOrder = $this->quickOrderFactory->create();
        $this->resource->load($quickOrder, $id);
        if (!$quickOrder->getId()) {
            throw new NoSuchEntityException(__('quickOrder with id "%1" does not exist.', $id));
        }
        return $quickOrder;
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setquickOrders($collection->getquickOrders());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    public function delete(QuickOrderInterface $quickOrder): bool
    {
        try {
            $this->resource->delete($quickOrder);
        } catch (Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }
        return true;
    }

    public function deleteById($id): bool
    {
        return $this->delete($this->getById($id));
    }
}
