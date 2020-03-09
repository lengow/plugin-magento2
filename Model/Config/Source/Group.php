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
use Lengow\Connector\Helper\Config as ConfigHelper;

class Group implements ArrayInterface
{
    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * Constructor
     *
     * @param ConfigHelper $configHelper Lengow config helper instance
     */
    public function __construct(ConfigHelper $configHelper)
    {
        $this->_configHelper = $configHelper;
    }

    /**
     * Get option array for settings
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_configHelper->getAllCustomerGroup();
    }
}
