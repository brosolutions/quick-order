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

use BroSolutions\QuickOrder\Model\ProductList;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class GetListNameById
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        LoggerInterface            $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
    }

    /**
     * Get list name by id
     *
     * @param int $listId
     * @return string
     */
    public function execute(int $listId): string
    {
        try {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('id', $listId);

            /** @var ProductList $sourceList */
            $sourceList = $collection->getFirstItem();

            if (!$sourceList->getId()) {
                throw new LocalizedException(__('The list no longer exists.'));
            }

        } catch (NoSuchEntityException | LocalizedException $e) {
            $this->logger->error(sprintf(
                'Error getting list name ny id list: %s',
                $e->getMessage()
            ), $e->getTrace());

            return '';
        }

        return $sourceList->getListName();
    }
}
