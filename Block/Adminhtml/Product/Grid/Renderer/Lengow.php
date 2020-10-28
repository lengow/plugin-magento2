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

namespace Lengow\Connector\Block\Adminhtml\Product\Grid\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Lengow\Connector\Helper\Data as DataHelper;

class Lengow extends AbstractRenderer
{
    /**
     * @var BackendHelper backend helper instance
     */
    protected $_backendHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * Constructor
     *
     * @param Context $context Magento block context instance
     * @param BackendHelper $backendHelper Magento backend helper instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        DataHelper $dataHelper,
        array $data = []
    ) {
        $this->_backendHelper = $backendHelper;
        $this->_dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    /**
     * Decorate lengow publication values
     *
     * @param DataObject $row Magento data object instance
     *
     * @return string
     */
    public function render(DataObject $row)
    {
        $value = (int)$row->getData($this->getColumn()->getIndex());
        return '<div class="lgw-switch ' . ($value === 1 ? 'checked' : '') . '">
        <label>
            <div>
                <a href="javascript:void(0)" name="lengow_export_product" class="lengow_switch_export_product"
                id="lengow_export_product_' . $row->getData('entity_id') . '"
                data-href="' . $this->_backendHelper->getUrl('lengow/product') . '"
                data-action="lengow_export_product"
                data-id_store="' . $this->_dataHelper->getStore()->getId() . '"
                data-id_product="' . $row->getData('entity_id') . '"
                data-checked="' . $value . '">
                <span></span>
            </a>
        </label>
    </div>';
    }
}
