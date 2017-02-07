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

namespace Lengow\Connector\Block\Adminhtml\System\Config;

use \Magento\Config\Block\System\Config\Form\Field;
use \Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Lengow adminhtml system config check
 */
class Check extends Field
{
    /**
     * Retrieve element HTML markup
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element Magento Abstract Element Instance
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return '
		<div class="notices-wrapper">
		    <div class="messages">
		        <div class="message">
		            <strong>'.__('To change store credentials, please select a store').'</strong>
		        </div>
		    </div>
		</div>';
    }
}
