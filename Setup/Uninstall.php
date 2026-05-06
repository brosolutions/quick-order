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

namespace BroSolutions\QuickOrder\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

/**
 * Class Uninstall
 * Removes the QuickOrder module's custom tables and configurations when uninstalled.
 *
 * @copyright  Copyright (c) 2026 BroSolutions
 * @link       https://www.brosolutions.net/
 */
class Uninstall implements UninstallInterface
{
    /**
     * Array of tables to drop (ordered from child to parent to avoid foreign key constraint errors)
     *
     * @var array
     */
    private const TABLES_TO_DROP = [
        'brosolutions_quickorder_price_history',
        'brosolutions_quickorder_schedule_log',
        'brosolutions_quickorder_schedule',
        'brosolutions_quickorder_list_item',
        'brosolutions_quickorder_list'
    ];

    /**
     * Specific configuration paths to delete from core_config_data
     *
     * @var array
     */
    private const CONFIG_PATHS_TO_DELETE = [
        'brosolution_quick_order/general/enable',
        'brosolution_quick_order/general/search_results_limit',
        'brosolution_quick_order/scheduled_automated_orders/enable',
        'brosolution_quick_order/scheduled_automated_orders/check_previous_order',
        'brosolution_quick_order/scheduled_automated_orders/offline_payment_methods',
        'brosolution_quick_order/scheduled_automated_orders/offline_shipping_methods',
        'brosolution_quick_order/scheduled_automated_orders/price_alert_enable',
        'brosolution_quick_order/scheduled_automated_orders/price_alert_cron_expr'
    ];

    /**
     * Uninstall
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();

        $connection = $setup->getConnection();

        // 1. Drop ONLY the tables belonging to the QuickOrder module
        foreach (self::TABLES_TO_DROP as $table) {
            $tableName = $setup->getTable($table);
            if ($connection->isTableExists($tableName)) {
                $connection->dropTable($tableName);
            }
        }

        // 2. Delete ONLY the configs belonging to the QuickOrder module
        $configTable = $setup->getTable('core_config_data');
        if ($connection->isTableExists($configTable)) {
            $connection->delete(
                $configTable,
                ['path IN (?)' => self::CONFIG_PATHS_TO_DELETE]
            );
        }

        $setup->getConnection()->delete(
            $setup->getTable('setup_module'),
            ['module = ?' => 'BroSolutions_QuickOrder']
        );

        $setup->endSetup();
    }
}
