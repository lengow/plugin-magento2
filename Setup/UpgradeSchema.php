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

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
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
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'grid' => true,
                    'comment' => 'From Lengow',
                ]
            );

        // deletion of the attribute "on update CURRENT_TIMESTAMP" created automatically by Magento
        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            // remove attribute for table lengow_action
            $tableName = $setup->getTable('lengow_action');
            $columnName = 'created_at';
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
            $tableName = $setup->getTable('lengow_log');
            $columnName = 'date';
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
            $tableName = $setup->getTable('lengow_order');
            $columnName = 'order_date';
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
            $columnName = 'created_at';
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
            $tableName = $setup->getTable('lengow_order_error');
            $columnName = 'created_at';
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

        $setup->endSetup();
    }
}
