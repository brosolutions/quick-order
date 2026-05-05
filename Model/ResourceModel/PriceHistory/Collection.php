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

namespace BroSolutions\QuickOrder\Model\ResourceModel\PriceHistory;

use BroSolutions\QuickOrder\Model\PriceHistory as Model;
use BroSolutions\QuickOrder\Model\ResourceModel\PriceHistory as ResourceModel;
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
    protected $_idFieldName = 'history_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
