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

namespace BroSolutions\QuickOrder\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class AutomaticSchedule extends AbstractDb
{
    /**
     * @var string
     */
    public const STATUS_ACTIVE     = 'active';

    /**
     * @var string
     */
    public const STATUS_PAUSED     = 'paused';

    /**
     * @var string
     */
    public const STATUS_ERROR      = 'error';

    /**
     * @var string
     */
    public const STATUS_DISABLED   = 'disabled';

    /**
     * @var string
     */
    public const STATUS_PROCESSING = 'processing';

    /**
     * @var string
     */
    public const QUICK_ORDER_AUTOMATIC_SCHEDULE_TABLE = 'brosolutions_quickorder_schedule';

    /**
     * @var string
     */
    public const ACTION_ERROR = 'error';

    /**
     * @var string
     */
    public const ACTION_SKIP  = 'skip';

    /**
     * @var string
     */
    public const ACTION_PAUSE = 'pause';

    /**
     * @var string
     */
    public const ACTION_ALLOW = 'allow';

    /**
     * @var string
     */
    public const ACTION_BLOCK = 'block';

    /**
     * @var array
     */
    public const ALL_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_DISABLED,
        self::STATUS_PAUSED,
        self::STATUS_ERROR,
        self::STATUS_PROCESSING
    ];

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(
            self::QUICK_ORDER_AUTOMATIC_SCHEDULE_TABLE,
            'schedule_id'
        );
    }
}
