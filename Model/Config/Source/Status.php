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

namespace Lengow\Connector\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Status implements ArrayInterface
{
    /**
     * Return array of lengow status
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'accepted', 'label' => __('Accepted')],
            ['value' => 'waiting_shipment', 'label' => __('Awaiting shipment')],
            ['value' => 'shipped', 'label' => __('Shipped')],
            ['value' => 'closed', 'label' => __('Closed')],
            ['value' => 'canceled', 'label' => __('Canceled')]
        ];
    }
}
