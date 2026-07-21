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

namespace Lengow\Connector\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class PopulateOrderItemId implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(): void
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $orderLineTable = $this->moduleDataSetup->getTable('lengow_order_line');
        $orderItemTable = $this->moduleDataSetup->getTable('sales_order_item');

        // get all lengow_order_line entries without order_item_id
        $select = $connection->select()
            ->from($orderLineTable, ['id', 'order_id', 'product_id'])
            ->where('order_item_id IS NULL');

        $rows = $connection->fetchAll($select);

        foreach ($rows as $row) {
            // find matching sales_order_item by order_id and product_id
            $itemSelect = $connection->select()
                ->from($orderItemTable, ['item_id', 'qty_ordered'])
                ->where('order_id = ?', $row['order_id'])
                ->where('product_id = ?', $row['product_id'])
                ->where('parent_item_id IS NULL')
                ->limit(2);

            $items = $connection->fetchAll($itemSelect);

            // only populate if there is exactly one match (no ambiguity)
            if (count($items) === 1) {
                $connection->update(
                    $orderLineTable,
                    [
                        'order_item_id' => (int) $items[0]['item_id'],
                        'quantity' => (int) $items[0]['qty_ordered'],
                    ],
                    ['id = ?' => $row['id']]
                );
            }
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}
