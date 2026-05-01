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

namespace BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule;

use BroSolutions\QuickOrder\Model\AutomaticSchedule as Model;
use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule as ResourceModel;
use BroSolutions\QuickOrder\Model\ResourceModel\ProductList;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'schedule_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }

    /**
     * Join the product list table before filters are applied.
     *
     * @return void
     */
    protected function _renderFiltersBefore()
    {
        $listTable = $this->getTable(ProductList::QUICK_ORDER_LIST_TABLE);

        $this->getSelect()->joinInner(
            ['list' => $listTable],
            'main_table.list_id = list.id',
            ['list_name', 'customer_id', 'store_id']
        );

        parent::_renderFiltersBefore();
    }
}
