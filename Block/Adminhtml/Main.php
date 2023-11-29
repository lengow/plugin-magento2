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

namespace Lengow\Connector\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Store\Model\ResourceModel\Store\Collection as StoreCollection;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Model\Connector as LengowConnector;
use Lengow\Connector\Model\Import\Order as LengowOrder;

class Main extends Template
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
     * @var LengowOrder Lengow order instance
     */
    private $lengowOrder;

    /**
     * @var array|null Lengow status account
     */
    private $statusAccount;

    /**
     * @var array Lengow plugin data
     */
    private $pluginData;

    /**
     * @var array Lengow plugin links
     */
    private $pluginLinks;

    /**
     * Constructor
     *
     * @param Context $context Magento block context instance
     * @param AuthSession $authSession Magento auth session instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param SecurityHelper $securityHelper Lengow security helper instance
     * @param SyncHelper $syncHelper Lengow sync helper instance
     * @param LengowOrder $lengowOrder Lengow order instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        AuthSession $authSession,
        ConfigHelper $configHelper,
        SecurityHelper $securityHelper,
        SyncHelper $syncHelper,
        LengowOrder $lengowOrder,
        array $data = []
    ) {
        $this->securityHelper = $securityHelper;
        $this->configHelper = $configHelper;
        $this->lengowOrder = $lengowOrder;
        $this->statusAccount = $syncHelper->getStatusAccount();
        $this->pluginData = $syncHelper->getPluginData();
        // get actual plugin urls in current language
        $interfaceLocale = $authSession->getUser()
            ? $authSession->getUser()->getInterfaceLocale()
            : DataHelper::DEFAULT_ISO_CODE;
        $this->pluginLinks = $syncHelper->getPluginLinks($interfaceLocale);
        parent::__construct($context, $data);
    }

    /**
     * Check if is a new merchant
     *
     * @return boolean
     */
    public function isNewMerchant(): bool
    {
        return $this->configHelper->isNewMerchant();
    }

    /**
     * Get preprod warning
     *
     * @return boolean
     */
    public function isPreprodPlugin(): bool
    {
        return !$this->configHelper->isProdEnvironment();
    }

    /**
     * Check if debug mode is active
     *
     * @return boolean
     */
    public function debugModeIsActive(): bool
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
        return '//my.' .$this->configHelper->getLengowUrl();
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getPluginVersion(): string
    {
        return $this->securityHelper->getPluginVersion();
    }

    /**
     * Get all Magento stores
     *
     * @return StoreCollection
     */
    public function getStores(): StoreCollection
    {
        return $this->configHelper->getAllStore();
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
     * Free trial is expired
     *
     * @return boolean
     */
    public function freeTrialIsExpired(): bool
    {
        return isset($this->statusAccount['type'], $this->statusAccount['expired'])
            && $this->statusAccount['type'] === 'free_trial'
            && $this->statusAccount['expired'];
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

    /**
     * Return CMS minimal version compatibility
     *
     * @return string
     */
    public function getCmsMinVersion(): string
    {
        return ($this->pluginData && isset($this->pluginData['cms_min_version']))
            ? $this->pluginData['cms_min_version']
            : '';
    }

    /**
     * Return CMS maximal version compatibility
     *
     * @return string
     */
    public function getCmsMaxVersion(): string
    {
        return ($this->pluginData && isset($this->pluginData['cms_max_version']))
            ? $this->pluginData['cms_max_version']
            : '';
    }

    /**
     * Return all required extensions
     *
     * @return array
     */
    public function getPluginExtensions(): array
    {
        return ($this->pluginData && isset($this->pluginData['extensions']))
            ? $this->pluginData['extensions']
            : [];
    }

    /**
     * Return plugin help center link for current locale
     *
     * @return string
     */
    public function getHelpCenterLink(): string
    {
        return $this->pluginLinks[SyncHelper::LINK_TYPE_HELP_CENTER];
    }

    /**
     * Return plugin changelog link for current locale
     *
     * @return string
     */
    public function getChangelogLink(): string
    {
        return $this->pluginLinks[SyncHelper::LINK_TYPE_CHANGELOG];
    }

    /**
     * Return plugin update guide link for current locale
     *
     * @return string
     */
    public function getUpdateGuideLink(): string
    {
        return $this->pluginLinks[SyncHelper::LINK_TYPE_UPDATE_GUIDE];
    }

    /**
     * Return Lengow support link for current locale
     *
     * @return string
     */
    public function getSupportLink(): string
    {
        return $this->pluginLinks[SyncHelper::LINK_TYPE_SUPPORT];
    }

    /**
     * Get plugin copyright
     *
     * @return string
     */
    public function getPluginCopyright(): string
    {
        return 'copyright © ' . date('Y');
    }

    /**
     * Checks if the plugin upgrade modal should be displayed or not
     *
     * @return boolean
     */
    public function showPluginUpgradeModal(): bool
    {
        if (!$this->newPluginVersionIsAvailable()) {
            return false;
        }
        $updatedAt = $this->configHelper->get(ConfigHelper::LAST_UPDATE_PLUGIN_MODAL);
        if ($updatedAt !== null && (time() - (int) $updatedAt) < 86400) {
            return false;
        }
        $this->configHelper->set(ConfigHelper::LAST_UPDATE_PLUGIN_MODAL, time());
        return true;
    }

    /**
     * Get number order to be sent
     *
     * @return integer
     */
    public function getNumberOrderToBeSent(): int
    {
        return $this->lengowOrder->countOrderToBeSent();
    }
}
