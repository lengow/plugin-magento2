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
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Lengow\Connector\Model\Import\Action as LengowAction;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\Ordererror as LengowOrderError;
use Lengow\Connector\Model\Import\Orderline as LengowOrderLine;
use Lengow\Connector\Model\Log as LengowLog;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup Magento schema setup instance
     * @param ModuleContextInterface $context Magento module context instance
     *
     * @throws \Exception
     *
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        // create table lengow_order
        $tableName = $installer->getTable(LengowOrder::TABLE_ORDER);
        if (!$installer->getConnection()->isTableExists($tableName)) {
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    LengowOrder::FIELD_ID,
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
                    LengowOrder::FIELD_ORDER_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => true,
                        'unsigned' => true,
                        'default' => null,
                    ],
                    'Order Id'
                )->addColumn(
                    LengowOrder::FIELD_ORDER_SKU,
                    Table::TYPE_TEXT,
                    40,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Order sku'
                )->addColumn(
                    LengowOrder::FIELD_STORE_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Store Id'
                )->addColumn(
                    LengowOrder::FIELD_DELIVERY_ADDRESS_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => true,
                        'unsigned' => true,
                        'default' => null,
                    ],
                    'Delivery Address Id'
                )->addColumn(
                    LengowOrder::FIELD_DELIVERY_COUNTRY_ISO,
                    Table::TYPE_TEXT,
                    3,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Delivery Country Iso'
                )->addColumn(
                    LengowOrder::FIELD_MARKETPLACE_SKU,
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => false,
                    ],
                    'Marketplace Sku'
                )->addColumn(
                    LengowOrder::FIELD_MARKETPLACE_NAME,
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => false,
                    ],
                    'Marketplace Name'
                )->addColumn(
                    LengowOrder::FIELD_MARKETPLACE_LABEL,
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Marketplace Label'
                )->addColumn(
                    LengowOrder::FIELD_ORDER_LENGOW_STATE,
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => false,
                    ],
                    'Order Lengow State'
                )->addColumn(
                    LengowOrder::FIELD_ORDER_PROCESS_STATE,
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Order Process State'
                )->addColumn(
                    LengowOrder::FIELD_ORDER_DATE,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Order Date'
                )->addColumn(
                    LengowOrder::FIELD_ORDER_ITEM,
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => true,
                        'unsigned' => true,
                        'default' => null,
                    ],
                    'Order Item'
                )->addColumn(
                    LengowOrder::FIELD_ORDER_TYPES,
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Order Types'
                )->addColumn(
                    LengowOrder::FIELD_CURRENCY,
                    Table::TYPE_TEXT,
                    3,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Currency'
                )->addColumn(
                    LengowOrder::FIELD_TOTAL_PAID,
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
                    LengowOrder::FIELD_CUSTOMER_VAT_NUMBER,
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Customer Vat Number'
                )->addColumn(
                    LengowOrder::FIELD_COMMISSION,
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
                    LengowOrder::FIELD_CUSTOMER_NAME,
                    Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Customer Name'
                )->addColumn(
                    LengowOrder::FIELD_CUSTOMER_EMAIL,
                    Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Customer Email'
                )->addColumn(
                    LengowOrder::FIELD_CARRIER,
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Carrier'
                )->addColumn(
                    LengowOrder::FIELD_CARRIER_METHOD,
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Carrier Method'
                )->addColumn(
                    LengowOrder::FIELD_CARRIER_TRACKING,
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Carrier Tracking'
                )->addColumn(
                    LengowOrder::FIELD_CARRIER_RELAY_ID,
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Carrier Id Relay'
                )->addColumn(
                    LengowOrder::FIELD_SENT_MARKETPLACE,
                    Table::TYPE_BOOLEAN,
                    null,
                    [
                        'nullable' => false,
                        'default' => 0,
                    ],
                    'Sent Marketplace'
                )->addColumn(
                    LengowOrder::FIELD_IS_IN_ERROR,
                    Table::TYPE_BOOLEAN,
                    null,
                    [
                        'nullable' => false,
                        'default' => 0,
                    ],
                    'Is In Error'
                )->addColumn(
                    LengowOrder::FIELD_IS_REIMPORTED,
                    Table::TYPE_BOOLEAN,
                    null,
                    [
                        'unsigned' => true,
                        'default' => 0,
                    ],
                    'Is importable again'
                )->addColumn(
                    LengowOrder::FIELD_MESSAGE,
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Message'
                )->addColumn(
                    LengowOrder::FIELD_CREATED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Created At'
                )->addColumn(
                    LengowOrder::FIELD_UPDATED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Updated At'
                )->addColumn(
                    LengowOrder::FIELD_EXTRA,
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Extra'
                )->addIndex(
                    $installer->getIdxName(LengowOrder::TABLE_ORDER, [LengowOrder::FIELD_STORE_ID]),
                    [LengowOrder::FIELD_STORE_ID]
                )->addIndex(
                    $installer->getIdxName(LengowOrder::TABLE_ORDER, [LengowOrder::FIELD_MARKETPLACE_SKU]),
                    [LengowOrder::FIELD_MARKETPLACE_SKU]
                )->addIndex(
                    $installer->getIdxName(LengowOrder::TABLE_ORDER, [LengowOrder::FIELD_MARKETPLACE_NAME]),
                    [LengowOrder::FIELD_MARKETPLACE_NAME]
                )->addIndex(
                    $installer->getIdxName(LengowOrder::TABLE_ORDER, [LengowOrder::FIELD_ORDER_LENGOW_STATE]),
                    [LengowOrder::FIELD_ORDER_LENGOW_STATE]
                )->addIndex(
                    $installer->getIdxName(LengowOrder::TABLE_ORDER, [LengowOrder::FIELD_TOTAL_PAID]),
                    [LengowOrder::FIELD_TOTAL_PAID]
                )->addIndex(
                    $installer->getIdxName(
                        LengowOrder::TABLE_ORDER,
                        [
                            LengowOrder::FIELD_ORDER_SKU,
                            LengowOrder::FIELD_MARKETPLACE_SKU,
                            LengowOrder::FIELD_MARKETPLACE_NAME,
                            LengowOrder::FIELD_MARKETPLACE_LABEL,
                            LengowOrder::FIELD_CUSTOMER_NAME,
                            LengowOrder::FIELD_CUSTOMER_EMAIL,
                        ],
                        AdapterInterface::INDEX_TYPE_FULLTEXT
                    ),
                    [
                        LengowOrder::FIELD_ORDER_SKU,
                        LengowOrder::FIELD_MARKETPLACE_SKU,
                        LengowOrder::FIELD_MARKETPLACE_NAME,
                        LengowOrder::FIELD_MARKETPLACE_LABEL,
                        LengowOrder::FIELD_CUSTOMER_NAME,
                        LengowOrder::FIELD_CUSTOMER_EMAIL,
                    ],
                    ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
                )
                ->setComment('Lengow orders table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $installer->getConnection()->createTable($table);
        }

        // create table lengow_order_line
        $tableName = $installer->getTable(LengowOrderLine::TABLE_ORDER_LINE);
        if (!$installer->getConnection()->isTableExists($tableName)) {
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    LengowOrderLine::FIELD_ID,
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
                    LengowOrderLine::FIELD_ORDER_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Order Id'
                )->addColumn(
                    LengowOrderLine::FIELD_PRODUCT_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Product Id'
                )->addColumn(
                    LengowOrderLine::FIELD_ORDER_LINE_ID,
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
        $tableName = $installer->getTable(LengowOrderError::TABLE_ORDER_ERROR);
        if (!$installer->getConnection()->isTableExists($tableName)) {
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    LengowOrderError::FIELD_ID,
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
                    LengowOrderError::FIELD_ORDER_LENGOW_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Order Lengow Id'
                )->addColumn(
                    LengowOrderError::FIELD_MESSAGE,
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Message'
                )->addColumn(
                    LengowOrderError::FIELD_TYPE,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Type'
                )->addColumn(
                    LengowOrderError::FIELD_IS_FINISHED,
                    Table::TYPE_BOOLEAN,
                    null,
                    [
                        'nullable' => false,
                        'default' => 0,
                    ],
                    'Is Finished'
                )->addColumn(
                    LengowOrderError::FIELD_MAIL,
                    Table::TYPE_BOOLEAN,
                    null,
                    [
                        'nullable' => false,
                        'default' => 0,
                    ],
                    'Mail'
                )->addColumn(
                    LengowOrderError::FIELD_CREATED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Created At'
                )->addColumn(
                    LengowOrderError::FIELD_UPDATED_AT,
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
        $tableName = $installer->getTable(LengowAction::TABLE_ACTION);
        if (!$installer->getConnection()->isTableExists($tableName)) {
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    LengowAction::FIELD_ID,
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
                    LengowAction::FIELD_ORDER_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Order Id'
                )->addColumn(
                    LengowAction::FIELD_ACTION_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Action Id'
                )->addColumn(
                    LengowAction::FIELD_ORDER_LINE_SKU,
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Order Line Sku'
                )->addColumn(
                    LengowAction::FIELD_ACTION_TYPE,
                    Table::TYPE_TEXT,
                    32,
                    [
                        'nullable' => false,
                    ],
                    'Action Type'
                )->addColumn(
                    LengowAction::FIELD_RETRY,
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                        'default' => 0,
                    ],
                    'Retry'
                )->addColumn(
                    LengowAction::FIELD_PARAMETERS,
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => false,
                    ],
                    'Parameters'
                )->addColumn(
                    LengowAction::FIELD_STATE,
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'State'
                )->addColumn(
                    LengowAction::FIELD_CREATED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Created At'
                )->addColumn(
                    LengowAction::FIELD_UPDATED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Updated At'
                )->addIndex(
                    $installer->getIdxName(
                        LengowAction::TABLE_ACTION,
                        [
                            LengowAction::FIELD_ORDER_LINE_SKU,
                            LengowAction::FIELD_PARAMETERS,
                        ],
                        AdapterInterface::INDEX_TYPE_FULLTEXT
                    ),
                    [
                        LengowAction::FIELD_ORDER_LINE_SKU,
                        LengowAction::FIELD_PARAMETERS,
                    ],
                    ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
                );
            $installer->getConnection()->createTable($table);
        }

        // create table lengow_log
        $tableName = $installer->getTable(LengowLog::TABLE_LOG);
        if (!$installer->getConnection()->isTableExists($tableName)) {
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    LengowLog::FIELD_ID,
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
                    LengowLog::FIELD_DATE,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => true,
                        'default' => null,
                    ],
                    'Date'
                )->addColumn(
                    LengowLog::FIELD_CATEGORY,
                    Table::TYPE_TEXT,
                    100,
                    [
                        'nullable' => false,
                    ],
                    'Category'
                )->addColumn(
                    LengowLog::FIELD_MESSAGE,
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => false,
                    ],
                    'Message'
                )->addIndex(
                    $installer->getIdxName(LengowLog::TABLE_LOG, [LengowLog::FIELD_CATEGORY]),
                    [LengowLog::FIELD_CATEGORY]
                )->addIndex(
                    $installer->getIdxName(
                        LengowLog::TABLE_LOG,
                        [
                            LengowLog::FIELD_MESSAGE,
                        ],
                        AdapterInterface::INDEX_TYPE_FULLTEXT
                    ),
                    [
                        LengowLog::FIELD_MESSAGE,
                    ],
                    ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
                );
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
