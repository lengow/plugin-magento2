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
 * @subpackage  Model
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Lengow\Connector\Model\Import\Orderline as LengowOrderLine;

class Orderline extends AbstractDb
{
    /**
     * Initialize orderline resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(LengowOrderline::TABLE_ORDER_LINE, LengowOrderline::FIELD_ID);
    }
}
