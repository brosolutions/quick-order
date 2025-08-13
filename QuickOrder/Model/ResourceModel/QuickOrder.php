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

namespace BroSolutions\QuickOrder\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class QuickOrder extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('bro_quick_orders', 'entity_id');
    }
}
