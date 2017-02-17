<?php

namespace Lengow\Connector\Block\Adminhtml;

class Product extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_product';
        $this->_blockGroup = 'Lengow\Connector';
        $this->_headerText = __('product.screen.title');
        parent::_construct();
        $this->buttonList->remove('add');
    }

}
