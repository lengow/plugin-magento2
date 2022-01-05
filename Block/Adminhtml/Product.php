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
 * @subpackage  Block
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container as MagentoContainer;

class Product extends MagentoContainer
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_controller = 'adminhtml_product';
        $this->_blockGroup = 'Lengow\Connector';
        $this->_headerText = __('Export product');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}
