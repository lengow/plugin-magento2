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

namespace Lengow\Connector\Block\Adminhtml\Product;

use Magento\Backend\Block\Template;
use Magento\Backend\Helper\Data as BackendHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Export as Export;
use Magento\Backend\Block\Template\Context;

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
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Lengow\Connector\Helper\Data   $dataHelper   Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Magento\Backend\Helper\Data    $backendHelper
     * @param \Lengow\Connector\Model\Export  $export
     * @param array $data
     */
    public function __construct(
        Context $context,
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
     *
     * @return \Magento\Backend\Helper\Data
     */
    public function getBackendHelper()
    {
        return $this->_backendHelper;
    }

    /**
     *
     * @return \Lengow\Connector\Helper\Data
     */
    public function getDataHelper()
    {
        return $this->_dataHelper;
    }

    /**
     *
     * @return \Lengow\Connector\Helper\Config
     */
    public function getConfigHelper()
    {
        return $this->_configHelper;
    }

    /**
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
     * TODO dans Grid également
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        if ($storeId == 0) {
            $storeId = $this->_storeManager->getDefaultStoreView()->getId();
        }
        return $this->_storeManager->getStore($storeId);
    }

}