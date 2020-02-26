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

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Model\Connector;

class Header extends Template
{
    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Helper\Security Lengow security helper instance
     */
    protected $_securityHelper;

    /**
     * @var \Lengow\Connector\Helper\Sync Lengow sync helper instance
     */
    protected $_syncHelper;

    /**
     * @var array Lengow status account
     */
    protected $_statusAccount = [];

    /**
     * @var array Lengow plugin data
     */
    protected $_pluginData = [];

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context Magento block context instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Helper\Security $securityHelper Lengow security helper instance
     * @param \Lengow\Connector\Helper\Sync $syncHelper Lengow sync helper instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        SecurityHelper $securityHelper,
        SyncHelper $syncHelper,
        array $data = []
    ) {
        $this->_configHelper = $configHelper;
        $this->_securityHelper = $securityHelper;
        $this->_syncHelper = $syncHelper;
        $this->_statusAccount = $this->_syncHelper->getStatusAccount();
        $this->_pluginData = $this->_syncHelper->getPluginData();
        parent::__construct($context, $data);
    }

    /**
     * Debug Mode is enabled
     *
     * @return boolean
     */
    public function debugModeIsEnabled()
    {
        return $this->_configHelper->debugModeIsActive();
    }

    /**
     * Get Lengow solution url
     *
     * @return string
     */
    public function getLengowSolutionUrl()
    {
        return '//my.' . Connector::LENGOW_URL;
    }

    /**
     * Free trial is enabled
     *
     * @return boolean
     */
    public function freeTrialIsEnabled()
    {
        if ((isset($this->_statusAccount['type']) && $this->_statusAccount['type'] === 'free_trial')
            && (isset($this->_statusAccount['expired']) && !$this->_statusAccount['expired'])
        ) {
            return true;
        }
        return false;
    }

    /**
     * Recovers the number of days of free trial
     *
     * @return integer
     */
    public function getFreeTrialDays()
    {
        return isset($this->_statusAccount['day']) ? (int)$this->_statusAccount['day']  : 0;
    }

    /**
     * New plugin version is available
     *
     * @return boolean
     */
    public function newPluginVersionIsAvailable()
    {
        if (($this->_pluginData && isset($this->_pluginData['version']))
            && version_compare($this->_securityHelper->getPluginVersion(), $this->_pluginData['version'], '<')
        ) {
            return true;
        }
        return false;
    }

    /**
     * Get new plugin version
     *
     * @return string
     */
    public function getNewPluginVersion()
    {
        return ($this->_pluginData && isset($this->_pluginData['version'])) ? $this->_pluginData['version']  : '';
    }

    /**
     * Get new plugin download link
     *
     * @return string
     */
    public function getNewPluginDownloadLink()
    {
        return ($this->_pluginData && isset($this->_pluginData['download_link']))
            ? $this->getLengowSolutionUrl() . $this->_pluginData['download_link']
            : '';
    }
}
