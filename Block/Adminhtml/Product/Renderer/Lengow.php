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

namespace Lengow\Connector\Block\Adminhtml\Product\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Lengow\Connector\Helper\Data as DataHelper;
use Magento\Backend\Block\Context;
use Magento\Store\Model\StoreManagerInterface;

class Lengow extends AbstractRenderer
{
    protected $_backendHelper;

    protected $_storeManager;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        DataHelper $backendHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_backendHelper = $backendHelper;
        $this->_storeManager = $storeManager;
    }

    /**
     * Decorate lengow publication values
     *
     * @param \Magento\Framework\DataObject $row
     *
     * @return string
     */
    public function render(DataObject $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        return '<div class="lgw-switch '. ($value === '1' ? 'checked' : '').'">
        <label>
            <div><span></span>
                <input type="checkbox"
                name="lengow_export_product" class="lengow_switch_option"
                id="lengow_export_product"
                data-href="'. $this->_backendHelper->getUrl('lengow/product') .'?isAjax=true"
                data-action="lengow_export_product"
                data-id_store="'. $this->_storeManager->getStore()->getId() .'"
                value="1">
            </div>
        </label>
    </div>';
    }
}
