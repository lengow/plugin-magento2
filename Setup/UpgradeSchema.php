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

use Magento\Eav\Model\Entity\Attribute\Source\Boolean as EavBool;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Lengow\Connector\Model\Import\Action as LengowAction;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\Ordererror as LengowOrderError;
use Lengow\Connector\Model\Log as LengowLog;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @inheritdoc
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        // order table
        $setup->getConnection()
            ->addColumn(
                $setup->getTable('sales_order'),
                'from_lengow',
                [
                    'label' => 'From Lengow',
                    'type' => Table::TYPE_INTEGER,
                    'visible' => true,
                    'required' => false,
                    'unique' => false,
                    'filterable' => 1,
                    'sort_order' => 700,
                    'default' => 0,
                    'input' => 'select',
                    'system' => 0,
                    'source' => EavBool::class,
                    'grid' => true,
                    'comment' => 'From Lengow',
                ]
            );

        // deletion of the attribute "on update CURRENT_TIMESTAMP" created automatically by Magento
        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            // remove attribute for table lengow_action
            $tableName = $setup->getTable(LengowAction::TABLE_ACTION);
            $columnName = LengowAction::FIELD_CREATED_AT;
            if ($setup->getConnection()->tableColumnExists($tableName, $columnName)) {
                $setup->getConnection()->modifyColumn(
                    $tableName,
                    $columnName,
                    [
                        'type' => Table::TYPE_TIMESTAMP,
                        'nullable' => true,
                        'default' => null,
                        'comment' => 'Created At',
                    ]
                );
            }
            // remove attribute and index for table lengow_log
            $tableName = $setup->getTable(LengowLog::TABLE_LOG);
            $columnName = LengowLog::FIELD_DATE;
            if ($setup->getConnection()->tableColumnExists($tableName, $columnName)) {
                $setup->getConnection()->dropIndex($tableName, $setup->getIdxName($tableName, [$columnName]));
                $setup->getConnection()->modifyColumn(
                    $tableName,
                    $columnName,
                    [
                        'type' => Table::TYPE_TIMESTAMP,
                        'nullable' => true,
                        'default' => null,
                        'comment' => 'Date',
                    ]
                );
            }
            // remove attribute and index for table lengow_order
            $tableName = $setup->getTable(LengowOrder::TABLE_ORDER);
            $columnName = LengowOrder::FIELD_ORDER_DATE;
            if ($setup->getConnection()->tableColumnExists($tableName, $columnName)) {
                $setup->getConnection()->dropIndex($tableName, $setup->getIdxName($tableName, [$columnName]));
                $setup->getConnection()->modifyColumn(
                    $tableName,
                    $columnName,
                    [
                        'type' => Table::TYPE_TIMESTAMP,
                        'nullable' => true,
                        'default' => null,
                        'comment' => 'Order Date',
                    ]
                );
            }
            $columnName = LengowOrder::FIELD_CREATED_AT;
            if ($setup->getConnection()->tableColumnExists($tableName, $columnName)) {
                $setup->getConnection()->modifyColumn(
                    $tableName,
                    $columnName,
                    [
                        'type' => Table::TYPE_TIMESTAMP,
                        'nullable' => true,
                        'default' => null,
                        'comment' => 'Created At',
                    ]
                );
            }
            // remove attribute for table lengow_order_error
            $tableName = $setup->getTable(LengowOrderError::TABLE_ORDER_ERROR);
            $columnName = LengowOrderError::FIELD_CREATED_AT;
            if ($setup->getConnection()->tableColumnExists($tableName, $columnName)) {
                $setup->getConnection()->modifyColumn(
                    $tableName,
                    $columnName,
                    [
                        'type' => Table::TYPE_TIMESTAMP,
                        'nullable' => true,
                        'default' => null,
                        'comment' => 'Created At',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.2.2', '<')) {
            $tableName = $setup->getTable(LengowOrder::TABLE_ORDER);
            if ((bool) $setup->getConnection()->showTableStatus($tableName)) {
                // add order_types attribute in table lengow_order
                $columnName = LengowOrder::FIELD_ORDER_TYPES;
                if (!$setup->getConnection()->tableColumnExists($tableName, $columnName)) {
                    $setup->getConnection()
                        ->addColumn(
                            $tableName,
                            $columnName,
                            [
                                'type' => Table::TYPE_TEXT,
                                'nullable' => true,
                                'default' => null,
                                'after' => LengowOrder::FIELD_ORDER_ITEM,
                                'comment' => 'Order Types',
                            ]
                        );
                }

            }
        }

        if (version_compare($context->getVersion(), '1.2.3', '<')) {
            $tableName = $setup->getTable(LengowOrder::TABLE_ORDER);
            if ((bool) $setup->getConnection()->showTableStatus($tableName)) {
                $columnName = LengowOrder::FIELD_CUSTOMER_VAT_NUMBER;
                if (!$setup->getConnection()->tableColumnExists($tableName, $columnName)) {
                    $setup->getConnection()
                        ->addColumn(
                            $tableName,
                            $columnName,
                            [
                                'type' => Table::TYPE_TEXT,
                                'nullable' => true,
                                'default' => null,
                                'after' => LengowOrder::FIELD_TOTAL_PAID,
                                'comment' => 'Customer Vat Number'
                            ]
                        );
                }
            }
        }

        $setup->endSetup();
    }
}
