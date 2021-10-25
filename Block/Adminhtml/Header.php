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
use Lengow\Connector\Model\Connector as LengowConnector;

class Header extends Template
{
    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var SecurityHelper Lengow security helper instance
     */
    private $securityHelper;

    /**
     * @var array Lengow status account
     */
    private $statusAccount;

    /**
     * @var array Lengow plugin data
     */
    private $pluginData;

    /**
     * Constructor
     *
     * @param Context $context Magento block context instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param SecurityHelper $securityHelper Lengow security helper instance
     * @param SyncHelper $syncHelper Lengow sync helper instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        SecurityHelper $securityHelper,
        SyncHelper $syncHelper,
        array $data = []
    ) {
        $this->configHelper = $configHelper;
        $this->securityHelper = $securityHelper;
        $this->statusAccount = $syncHelper->getStatusAccount();
        $this->pluginData = $syncHelper->getPluginData();
        parent::__construct($context, $data);
    }

    /**
     * Debug Mode is enabled
     *
     * @return boolean
     */
    public function debugModeIsEnabled(): bool
    {
        return $this->configHelper->debugModeIsActive();
    }

    /**
     * Get Lengow solution url
     *
     * @return string
     */
    public function getLengowSolutionUrl(): string
    {
        return '//my.' . LengowConnector::LENGOW_URL;
    }

    /**
     * Free trial is enabled
     *
     * @return boolean
     */
    public function freeTrialIsEnabled(): bool
    {
        return isset($this->statusAccount['type'], $this->statusAccount['expired'])
            && $this->statusAccount['type'] === 'free_trial'
            && !$this->statusAccount['expired'];
    }

    /**
     * Recovers the number of days of free trial
     *
     * @return integer
     */
    public function getFreeTrialDays(): int
    {
        return isset($this->statusAccount['day']) ? (int) $this->statusAccount['day'] : 0;
    }

    /**
     * New plugin version is available
     *
     * @return boolean
     */
    public function newPluginVersionIsAvailable(): bool
    {
        return ($this->pluginData && isset($this->pluginData['version']))
            && version_compare($this->securityHelper->getPluginVersion(), $this->pluginData['version'], '<');
    }

    /**
     * Get new plugin version
     *
     * @return string
     */
    public function getNewPluginVersion(): string
    {
        return ($this->pluginData && isset($this->pluginData['version'])) ? $this->pluginData['version'] : '';
    }

    /**
     * Get new plugin download link
     *
     * @return string
     */
    public function getNewPluginDownloadLink(): string
    {
        return ($this->pluginData && isset($this->pluginData['download_link']))
            ? $this->getLengowSolutionUrl() . $this->pluginData['download_link']
            : '';
    }
}
