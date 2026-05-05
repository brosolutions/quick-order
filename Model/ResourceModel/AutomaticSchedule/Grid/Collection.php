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

namespace BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;
use Zend_Db_Expr;
use Magento\Framework\Exception\LocalizedException;
use BroSolutions\QuickOrder\Model\ResourceModel\AutomaticSchedule;

/**
 * Class Collection
 *
 * Grid collection for Automatic Schedule to include joined fields.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class Collection extends SearchResult
{
    /**
     * @var string
     */
    protected $_idFieldName = 'schedule_id';

    /**
     * Constructor
     *
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @param string $identifierName
     * @param string|null $connectionName
     * @throws LocalizedException
     */
    // phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = 'brosolutions_quickorder_schedule',
        $resourceModel = AutomaticSchedule::class,
        $identifierName = 'schedule_id',
        $connectionName = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }
    // phpcs:enable Generic.CodeAnalysis.UselessOverridingMethod

    /**
     * Initialize select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->columns(['id' => 'main_table.schedule_id']);

        $this->getSelect()->joinLeft(
            ['list_table' => $this->getTable('brosolutions_quickorder_list')],
            'main_table.list_id = list_table.id',
            ['list_name']
        );

        $this->getSelect()->joinLeft(
            ['customer_table' => $this->getTable('customer_entity')],
            'list_table.customer_id = customer_table.entity_id',
            [
                'customer_name' => new Zend_Db_Expr(
                    "CONCAT_WS(' ', customer_table.firstname, customer_table.lastname)"
                )
            ]
        );

        $this->addFilterToMap('list_name', 'list_table.list_name');
        $this->addFilterToMap('schedule_id', 'main_table.schedule_id');
        $this->addFilterToMap('id', 'main_table.schedule_id');
        $this->addFilterToMap(
            'customer_name',
            new Zend_Db_Expr("CONCAT_WS(' ', customer_table.firstname, customer_table.lastname)")
        );

        $this->getSelect()->group('main_table.schedule_id');

        return $this;
    }
}
