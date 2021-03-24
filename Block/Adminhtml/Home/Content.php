<?php
/**
 * Copyright 2021 Lengow SAS
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
 * @copyright   2021 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Block\Adminhtml\Home;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Store\Model\ResourceModel\Store\Collection as StoreCollection;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Connector as LengowConnector;

class Content extends Template
{
    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $configHelper;

    /**
     * Content constructor
     * @param Context $context Magento context
     * @param ConfigHelper $configHelper lengow config helper
     * @param array $data additional data
     */
    public function __construct(Context $context, ConfigHelper $configHelper, array $data = [])
    {
        $this->configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    /**
     * Get all Magento stores
     *
     * @return StoreCollection
     */
    public function getStores()
    {
        return $this->configHelper->getAllStore();
    }

    /**
     * Get Lengow solution url
     *
     * @return string
     */
    public function getLengowSolutionUrl()
    {
        return '//my.' . LengowConnector::LENGOW_URL;
    }

    /**
     * Check if plugin is a preprod version
     *
     * @return boolean
     */
    public function isPreprodPlugin()
    {
        return LengowConnector::LENGOW_URL === 'lengow.net';
    }
}
