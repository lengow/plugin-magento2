<?php

namespace Lengow\Connector\Block\Adminhtml\Product;

use Magento\Backend\Block\Template;
use Magento\Backend\Helper\Data as BackendHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Export as Export;

class Header extends Template
{
    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $_backendHelper;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Model\Export
     */
    protected $_export;

    /**
     * Constructor
     *
     * @param \Lengow\Connector\Helper\Data   $dataHelper   Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Magento\Backend\Helper\Data    $backendHelper
     * @param \Lengow\Connector\Model\Export  $export
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = [],
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        BackendHelper $backendHelper,
        Export $export
    ) {
        parent::__construct($context, $data);
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_backendHelper = $backendHelper;
        $this->_export = $export;
    }

    /**
     * Make this public so that templates can use it properly with template engine
     *
     * @return \Magento\Backend\Helper\Data
     */
    public function getBackendHelper()
    {
        return $this->_backendHelper;
    }

    /**
     * Make this public so that templates can use it properly with template engine
     *
     * @return \Lengow\Connector\Helper\Data
     */
    public function getDataHelper()
    {
        return $this->_dataHelper;
    }

    /**
     * Make this public so that templates can use it properly with template engine
     *
     * @return \Lengow\Connector\Helper\Config
     */
    public function getConfigHelper()
    {
        return $this->_configHelper;
    }

    /**
     * Make this public so that templates can use it properly with template engine
     *
     * @return \Lengow\Connector\Model\Export
     */
    public function getExport()
    {
        $this->_export->init(['store_id' => $this->getStore()->getId()]);
        return $this->_export;
    }

    /**
     * Get store
     */
    public function getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        // set default store if storeId is global
        if ($storeId == 0) {
            $storeId = $this->_storeManager->getDefaultStoreView()->getId();
        }
        return $this->_storeManager->getStore($storeId);
    }

}