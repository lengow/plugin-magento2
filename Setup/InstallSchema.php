<?php

/**
 * Copyright 2017 Lengow SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @subpackage  Setup
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup Magento schema setup instance
     * @param \Magento\Framework\Setup\ModuleContextInterface $context Magento module context instance
     *
     * @throws \Exception
     *
     * @return void
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        // create table lengow_order
        $tableName = $installer->getTable('lengow_order');
        if (!$installer->getConnection()->isTableExists($tableName)) {
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true,
                    ],
                    'Id'
                )->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => true,
                        'unsigned' => true,
                        'default' => null,
                    ],
                    'Order Id'
                )->addColumn(
                    'order_sku',
                    Table::TYPE_TEXT,
                    40,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Order sku'
                )->addColumn(
                    'store_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Store Id'
                )->addColumn(
                    'delivery_address_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => true,
                        'unsigned' => true,
                        'default' => null,
                    ],
                    'Delivery Address Id'
                )->addColumn(
                    'delivery_country_iso',
                    Table::TYPE_TEXT,
                    3,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Delivery Country Iso'
                )->addColumn(
                    'marketplace_sku',
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => false,
                    ],
                    'Marketplace Sku'
                )->addColumn(
                    'marketplace_name',
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => false,
                    ],
                    'Marketplace Name'
                )->addColumn(
                    'marketplace_label',
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Marketplace Label'
                )->addColumn(
                    'order_lengow_state',
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => false,
                    ],
                    'Order Lengow State'
                )->addColumn(
                    'order_process_state',
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Order Process State'
                )->addColumn(
                    'order_date',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Order Date'
                )->addColumn(
                    'order_item',
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => true,
                        'unsigned' => true,
                        'default' => null,
                    ],
                    'Order Item'
                )->addColumn(
                    'currency',
                    Table::TYPE_TEXT,
                    3,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Currency'
                )->addColumn(
                    'total_paid',
                    Table::TYPE_DECIMAL,
                    null,
                    [
                        'nullable' => true,
                        'unsigned' => true,
                        'precision' => 17,
                        'scale' => 2,
                        'default' => null,
                    ],
                    'Total Paid'
                )->addColumn(
                    'commission',
                    Table::TYPE_DECIMAL,
                    null,
                    [
                        'nullable' => true,
                        'unsigned' => true,
                        'precision' => 17,
                        'scale' => 2,
                        'default' => null,
                    ],
                    'Commission'
                )->addColumn(
                    'customer_name',
                    Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Customer Name'
                )->addColumn(
                    'customer_email',
                    Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Customer Email'
                )->addColumn(
                    'carrier',
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Carrier'
                )->addColumn(
                    'carrier_method',
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Carrier Method'
                )->addColumn(
                    'carrier_tracking',
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Carrier Tracking'
                )->addColumn(
                    'carrier_id_relay',
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Carrier Id Relay'
                )->addColumn(
                    'sent_marketplace',
                    Table::TYPE_BOOLEAN,
                    null,
                    [
                        'nullable' => false,
                        'default' => 0,
                    ],
                    'Sent Marketplace'
                )->addColumn(
                    'is_in_error',
                    Table::TYPE_BOOLEAN,
                    null,
                    [
                        'nullable' => false,
                        'default' => 0,
                    ],
                    'Is In Error'
                )->addColumn(
                    'is_reimported',
                    Table::TYPE_BOOLEAN,
                    null,
                    [
                        'unsigned' => true,
                        'default' => 0,
                    ],
                    'Is importable again'
                )->addColumn(
                    'message',
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Message'
                )->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Created At'
                )->addColumn(
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Updated At'
                )->addColumn(
                    'extra',
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Extra'
                )->addIndex(
                    $installer->getIdxName('lengow_order', ['store_id']),
                    ['store_id']
                )->addIndex(
                    $installer->getIdxName('lengow_order', ['marketplace_sku']),
                    ['marketplace_sku']
                )->addIndex(
                    $installer->getIdxName('lengow_order', ['marketplace_name']),
                    ['marketplace_name']
                )->addIndex(
                    $installer->getIdxName('lengow_order', ['order_lengow_state']),
                    ['order_lengow_state']
                )->addIndex(
                    $installer->getIdxName('lengow_order', ['total_paid']),
                    ['total_paid']
                )->addIndex(
                    $installer->getIdxName(
                        'lengow_order',
                        [
                            'order_sku',
                            'marketplace_sku',
                            'marketplace_name',
                            'marketplace_label',
                            'customer_name',
                            'customer_email',
                        ],
                        AdapterInterface::INDEX_TYPE_FULLTEXT
                    ),
                    [
                        'order_sku',
                        'marketplace_sku',
                        'marketplace_name',
                        'marketplace_label',
                        'customer_name',
                        'customer_email',
                    ],
                    ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
                )
                ->setComment('Lengow orders table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $installer->getConnection()->createTable($table);
        }

        // create table lengow_order_line
        $tableName = $installer->getTable('lengow_order_line');
        if (!$installer->getConnection()->isTableExists($tableName)) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('lengow_order_line'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true,
                    ],
                    'Id'
                )->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Order Id'
                )->addColumn(
                    'product_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Product Id'
                )->addColumn(
                    'order_line_id',
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => false,
                    ],
                    'Order Line Id'
                );
            $installer->getConnection()->createTable($table);
        }

        // create table lengow_order_error
        $tableName = $installer->getTable('lengow_order_error');
        if (!$installer->getConnection()->isTableExists($tableName)) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('lengow_order_error'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true,
                    ],
                    'Id'
                )->addColumn(
                    'order_lengow_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Order Lengow Id'
                )->addColumn(
                    'message',
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Message'
                )->addColumn(
                    'type',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Type'
                )->addColumn(
                    'is_finished',
                    Table::TYPE_BOOLEAN,
                    null,
                    [
                        'nullable' => false,
                        'default' => 0,
                    ],
                    'Is Finished'
                )->addColumn(
                    'mail',
                    Table::TYPE_BOOLEAN,
                    null,
                    [
                        'nullable' => false,
                        'default' => 0,
                    ],
                    'Mail'
                )->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Created At'
                )->addColumn(
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Updated At'
                );
            $installer->getConnection()->createTable($table);
        }

        // create table lengow_action
        $tableName = $installer->getTable('lengow_action');
        if (!$installer->getConnection()->isTableExists($tableName)) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('lengow_action'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true,
                    ],
                    'Id'
                )->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Order Id'
                )->addColumn(
                    'action_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Action Id'
                )->addColumn(
                    'order_line_sku',
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Order Line Sku'
                )->addColumn(
                    'action_type',
                    Table::TYPE_TEXT,
                    32,
                    [
                        'nullable' => false,
                    ],
                    'Action Type'
                )->addColumn(
                    'retry',
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                        'default' => 0,
                    ],
                    'Retry'
                )->addColumn(
                    'parameters',
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => false,
                    ],
                    'Parameters'
                )->addColumn(
                    'state',
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'State'
                )->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Created At'
                )->addColumn(
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Updated At'
                )->addIndex(
                    $installer->getIdxName(
                        'lengow_action',
                        [
                            'order_line_sku',
                            'parameters',
                        ],
                        AdapterInterface::INDEX_TYPE_FULLTEXT
                    ),
                    [
                        'order_line_sku',
                        'parameters',
                    ],
                    ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
                );
            $installer->getConnection()->createTable($table);
        }

        // create table lengow_log
        $tableName = $installer->getTable('lengow_log');
        if (!$installer->getConnection()->isTableExists($tableName)) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('lengow_log'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true,
                    ],
                    'Id'
                )->addColumn(
                    'date',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Date'
                )->addColumn(
                    'category',
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => false,
                    ],
                    'Category'
                )->addColumn(
                    'message',
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => false,
                    ],
                    'Message'
                )->addIndex(
                    $installer->getIdxName('lengow_log', ['category']),
                    ['category']
                )->addIndex(
                    $installer->getIdxName(
                        'lengow_log',
                        [
                            'message',
                        ],
                        AdapterInterface::INDEX_TYPE_FULLTEXT
                    ),
                    [
                        'message',
                    ],
                    ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
                );
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
