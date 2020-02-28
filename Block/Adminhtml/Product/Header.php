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
use Magento\Backend\Block\Template\Context;
use Magento\Store\Api\Data\StoreInterface;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Export as LengowExport;

class Header extends Template
{
    /**
     * @var StoreInterface Magento store instance
     */
    protected $_store;

    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var LengowExport Lengow export instance
     */
    protected $_export;

    /**
     * Constructor
     *
     * @param Context $context Magento block context instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param LengowExport $export Lengow export instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        LengowExport $export,
        array $data = []
    )
    {
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_export = $export;
        $this->_store = $this->_dataHelper->getStore();
        parent::__construct($context, $data);
    }

    /**
     * Selection is enabled
     *
     * @return boolean
     */
    public function selectionIsEnabled()
    {
        return (bool)$this->_configHelper->get('selection_enable', $this->_store->getId());
    }

    /**
     * Get Magento store instance
     *
     * @return StoreInterface
     */
    public function getStore()
    {
        return $this->_store;
    }

    /**
     * Get Lengow export instance
     *
     * @return LengowExport
     */
    public function getExport()
    {
        $this->_export->init(['store_id' => $this->_store->getId()]);
        return $this->_export;
    }

    /**
     * Get export url
     *
     * @return string
     */
    public function getExportUrl()
    {
        return $this->_dataHelper->getExportUrl($this->_store->getId(), ['stream' => 1, 'update_export_date' => 0]);
    }
}
