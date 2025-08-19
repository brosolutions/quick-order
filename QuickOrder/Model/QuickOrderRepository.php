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
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class QuickOrderRepository implements QuickOrderRepositoryInterface
{
    /**
     * @var QuickOrderResource
     */
    protected $resource;

    /**
     * @var QuickOrderFactory
     */
    protected $quickOrderFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var SearchResultsFactory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @param QuickOrderResource $resource
     * @param QuickOrderFactory $quickOrderFactory
     * @param CollectionFactory $collectionFactory
     * @param SearchResultsFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
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

    /**
     * @param QuickOrderInterface $quickOrder
     * @return QuickOrderInterface
     * @throws CouldNotSaveException
     */
    public function save(QuickOrderInterface $quickOrder): QuickOrderInterface
    {
        try {
            $this->resource->save($quickOrder);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        return $quickOrder;
    }

    /**
     * @param $id
     * @return QuickOrderInterface
     * @throws NoSuchEntityException
     */
    public function getById($id): QuickOrderInterface
    {
        $quickOrder = $this->quickOrderFactory->create();
        $this->resource->load($quickOrder, $id);
        if (!$quickOrder->getId()) {
            throw new NoSuchEntityException(__('quickOrder with id "%1" does not exist.', $id));
        }
        return $quickOrder;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setquickOrders($collection->getquickOrders());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @param QuickOrderInterface $quickOrder
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(QuickOrderInterface $quickOrder): bool
    {
        try {
            $this->resource->delete($quickOrder);
        } catch (Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }
        return true;
    }

    /**
     * @param $id
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($id): bool
    {
        return $this->delete($this->getById($id));
    }
}
