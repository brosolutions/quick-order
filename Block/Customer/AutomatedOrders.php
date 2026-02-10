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

namespace BroSolutions\QuickOrder\Block\Customer;

use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\CollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList\Collection;

/**
 * @copyright  Copyright (c) 2025 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class AutomatedOrders extends Template
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @inheritdoc
     */
    public function __construct(
        Template\Context $context,
        CollectionFactory $collectionFactory,
        Session $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->collectionFactory = $collectionFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * Get orders
     *
     * @return Collection
     */
    public function getOrders(): Collection
    {
        $customerId = $this->customerSession->getCustomerId();
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->setOrder('created_at', 'DESC');

        return $collection;
    }
}
