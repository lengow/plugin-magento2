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
 * @subpackage  Helper
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Model\Connector as LengowConnector;
use Lengow\Connector\Model\Export as LengowExport;

class Sync extends AbstractHelper
{
    /**
     * @var string cms type
     */
    const CMS_TYPE = 'magento';

    /* Sync actions */
    const SYNC_CATALOG = 'catalog';
    const SYNC_CMS_OPTION = 'cms_option';
    const SYNC_STATUS_ACCOUNT = 'status_account';
    const SYNC_MARKETPLACE = 'marketplace';
    const SYNC_ORDER = 'order';
    const SYNC_ACTION = 'action';
    const SYNC_PLUGIN_DATA = 'plugin';

    /**
     * @var string marketplace file name
     */
    const MARKETPLACE_FILE = 'marketplaces.json';

    /* Plugin link types */
    const LINK_TYPE_HELP_CENTER = 'help_center';
    const LINK_TYPE_CHANGELOG = 'changelog';
    const LINK_TYPE_UPDATE_GUIDE = 'update_guide';
    const LINK_TYPE_SUPPORT = 'support';

    /* Default plugin links */
    const LINK_HELP_CENTER = 'https://support.lengow.com/kb/guide/en/zIKNDzKdKk';
    const LINK_CHANGELOG = 'https://support.lengow.com/kb/guide/en/o153qbn91H';
    const LINK_UPDATE_GUIDE = 'https://support.lengow.com/kb/guide/en/zIKNDzKdKk/Steps/25861,120332';
    const LINK_SUPPORT = 'https://help-support.lengow.com/hc/en-us/requests/new';

    /* Api iso codes */
    const API_ISO_CODE_EN = 'en';
    const API_ISO_CODE_FR = 'fr';
    const API_ISO_CODE_DE = 'de';

    /**
     * @var mixed status account
     */
    public static $statusAccount;

    /**
     * @var DriverFile Magento driver file instance
     */
    private $driverFile;

    /**
     * @var JsonHelper Magento json helper instance
     */
    private $jsonHelper;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var DataHelper data helper instance
     */
    private $dataHelper;

    /**
     * @var SecurityHelper Lengow security helper instance
     */
    private $securityHelper;

    /**
     * @var LengowConnector Lengow connector instance
     */
    private $connector;

    /**
     * @var LengowExport Lengow export instance
     */
    private $export;

    /**
     * @var array cache time for catalog, account status, cms options and marketplace synchronisation
     */
    protected $cacheTimes = [
        self::SYNC_CATALOG => 21600,
        self::SYNC_CMS_OPTION => 86400,
        self::SYNC_STATUS_ACCOUNT => 86400,
        self::SYNC_MARKETPLACE => 43200,
        self::SYNC_PLUGIN_DATA => 86400,
    ];

    /**
     * @var array valid sync actions
     */
    protected $syncActions = [
        self::SYNC_ORDER,
        self::SYNC_CMS_OPTION,
        self::SYNC_STATUS_ACCOUNT,
        self::SYNC_MARKETPLACE,
        self::SYNC_ACTION,
        self::SYNC_CATALOG,
        self::SYNC_PLUGIN_DATA,
    ];

    /**
     * @var array iso code correspondence for plugin links
     */
    protected $genericIsoCodes = [
        self::API_ISO_CODE_EN => DataHelper::ISO_CODE_EN,
        self::API_ISO_CODE_FR => DataHelper::ISO_CODE_FR,
        self::API_ISO_CODE_DE => DataHelper::ISO_CODE_DE,
    ];

    /**
     * @var array default plugin links when the API is not available
     */
    protected $defaultPluginLinks = [
        self::LINK_TYPE_HELP_CENTER => self::LINK_HELP_CENTER,
        self::LINK_TYPE_CHANGELOG => self::LINK_CHANGELOG,
        self::LINK_TYPE_UPDATE_GUIDE => self::LINK_UPDATE_GUIDE,
        self::LINK_TYPE_SUPPORT => self::LINK_SUPPORT,
    ];

    /**
     * Constructor
     *
     * @param Context $context Magento context instance
     * @param JsonHelper $jsonHelper Magento json helper instance
     * @param DriverFile $driverFile Magento driver file instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param SecurityHelper $securityHelper Lengow security helper instance
     * @param LengowConnector $connector Lengow connector instance
     * @param LengowExport $export Lengow export instance
     */
    public function __construct(
        Context $context,
        JsonHelper $jsonHelper,
        DriverFile $driverFile,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        SecurityHelper $securityHelper,
        LengowConnector $connector,
        LengowExport $export
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->driverFile = $driverFile;
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->securityHelper = $securityHelper;
        $this->connector = $connector;
        $this->export = $export;
        parent::__construct($context);
    }

    /**
     * Is sync action
     *
     * @param string $action sync action
     *
     * @return boolean
     */
    public function isSyncAction(string $action): bool
    {
        return in_array($action, $this->syncActions, true);
    }

    /**
     * Plugin is blocked or not
     *
     * @return boolean
     */
    public function pluginIsBlocked(): bool
    {
        if ($this->configHelper->isNewMerchant()) {
            return true;
        }
        $statusAccount = $this->getStatusAccount();
        return ($statusAccount && ($statusAccount['type'] === 'free_trial' && $statusAccount['expired']));
    }

    /**
     * Get Sync Data (Inscription / Update)
     *
     * @return array
     */
    public function getSyncData(): array
    {
        $data = [
            'domain_name' => $_SERVER['SERVER_NAME'],
            'token' => $this->configHelper->getToken(),
            'type' => self::CMS_TYPE,
            'version' => $this->securityHelper->getMagentoVersion(),
            'plugin_version' => $this->securityHelper->getPluginVersion(),
            'email' => $this->scopeConfig->getValue('trans_email/ident_general/email'),
            'cron_url' => $this->dataHelper->getCronUrl(),
            'toolbox_url' => $this->dataHelper->getToolboxUrl(),
            'shops' => [],
        ];
        $stores = $this->configHelper->getAllStore();
        foreach ($stores as $store) {
            $storeId = (int) $store->getId();
            $this->export->init([LengowExport::PARAM_STORE_ID => $storeId]);
            $data['shops'][] = [
                'token' => $this->configHelper->getToken($storeId),
                'shop_name' => $store->getName(),
                'domain_url' => $store->getBaseUrl(),
                'feed_url' => $this->dataHelper->getExportUrl($storeId),
                'total_product_number' => $this->export->getTotalProduct(),
                'exported_product_number' => $this->export->getTotalExportProduct(),
                'enabled' => $this->configHelper->storeIsActive($storeId),
            ];
        }
        return $data;
    }

    /**
     * Sync Lengow catalogs for order synchronisation
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function syncCatalog(bool $force = false, bool $logOutput = false): bool
    {
        $success = false;
        $cleanCache = false;
        if ($this->configHelper->isNewMerchant()) {
            return $success;
        }
        if (!$force) {
            $updatedAt = $this->configHelper->get(ConfigHelper::LAST_UPDATE_CATALOG);
            if ($updatedAt !== null && (time() - (int) $updatedAt) < $this->cacheTimes[self::SYNC_CATALOG]) {
                return $success;
            }
        }
        $result = $this->connector->queryApi(LengowConnector::GET, LengowConnector::API_CMS, [], '', $logOutput);
        if (isset($result->cms)) {
            $cmsToken = $this->configHelper->getToken();
            foreach ($result->cms as $cms) {
                if ($cms->token === $cmsToken) {
                    foreach ($cms->shops as $cmsShop) {
                        $store = $this->configHelper->getStoreByToken($cmsShop->token);
                        if ($store) {
                            $catalogIdsChange = $this->configHelper->setCatalogIds(
                                $cmsShop->catalog_ids,
                                (int) $store->getId()
                            );
                            $activeStoreChange = $this->configHelper->setActiveStore((int) $store->getId());
                            if (!$cleanCache && ($catalogIdsChange || $activeStoreChange)) {
                                $cleanCache = true;
                            }
                        }
                    }
                    $success = true;
                    break;
                }
            }
        }
        // clean config cache to valid configuration
        if ($cleanCache) {
            // save last update date for a specific settings (change synchronisation interval time)
            $this->configHelper->set(ConfigHelper::LAST_UPDATE_SETTING, time());
            $this->configHelper->cleanConfigCache();
        }
        $this->configHelper->set(ConfigHelper::LAST_UPDATE_CATALOG, time());
        return $success;
    }

    /**
     * Get options for all store
     *
     * @return array
     */
    public function getOptionData(): array
    {
        $data = [
            'token' => $this->configHelper->getToken(),
            'version' => $this->securityHelper->getMagentoVersion(),
            'plugin_version' => $this->securityHelper->getPluginVersion(),
            'options' => $this->configHelper->getAllValues(),
            'shops' => [],
        ];
        $stores = $this->configHelper->getAllStore();
        foreach ($stores as $store) {
            $storeId = (int) $store->getId();
            $this->export->init([LengowExport::PARAM_STORE_ID => $storeId]);
            $data['shops'][] = [
                'token' => $this->configHelper->getToken($storeId),
                'enabled' => $this->configHelper->storeIsActive($storeId),
                'total_product_number' => $this->export->getTotalProduct(),
                'exported_product_number' => $this->export->getTotalExportProduct(),
                'options' => $this->configHelper->getAllValues($storeId),
            ];
        }
        return $data;
    }

    /**
     * Set CMS options
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function setCmsOption(bool $force = false, bool $logOutput = false): bool
    {
        if ($this->configHelper->isNewMerchant() || $this->configHelper->debugModeIsActive()) {
            return false;
        }
        if (!$force) {
            $updatedAt = $this->configHelper->get(ConfigHelper::LAST_UPDATE_OPTION_CMS);
            if ($updatedAt !== null && (time() - (int) $updatedAt) < $this->cacheTimes[self::SYNC_CMS_OPTION]) {
                return false;
            }
        }
        $options = $this->jsonHelper->jsonEncode($this->getOptionData());
        $this->connector->queryApi(LengowConnector::PUT, LengowConnector::API_CMS, [], $options, $logOutput);
        $this->configHelper->set(ConfigHelper::LAST_UPDATE_OPTION_CMS, time());
        return true;
    }

    /**
     * Get Status Account
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return array|null
     */
    public function getStatusAccount(bool $force = false, bool $logOutput = false)
    {
        if ($this->configHelper->isNewMerchant()) {
            return null;
        }
        if (!$force) {
            $updatedAt = $this->configHelper->get(ConfigHelper::LAST_UPDATE_ACCOUNT_STATUS_DATA);
            if ($updatedAt !== null && (time() - (int) $updatedAt) < $this->cacheTimes[self::SYNC_STATUS_ACCOUNT]) {
                return json_decode($this->configHelper->get(ConfigHelper::ACCOUNT_STATUS_DATA), true);
            }
        }
        // use static cache for multiple call in same time (specific Magento 2)
        if (self::$statusAccount !== null) {
            return self::$statusAccount;
        }
        $status = null;
        $result = $this->connector->queryApi(LengowConnector::GET, LengowConnector::API_PLAN, [], '', $logOutput);
        if (isset($result->isFreeTrial)) {
            $status = [
                'type' => $result->isFreeTrial ? 'free_trial' : '',
                'day' => (int) $result->leftDaysBeforeExpired < 0 ? 0 : (int) $result->leftDaysBeforeExpired,
                'expired' => (bool) $result->isExpired,
            ];
            $this->configHelper->set(ConfigHelper::ACCOUNT_STATUS_DATA, $this->jsonHelper->jsonEncode($status));
            $this->configHelper->set(ConfigHelper::LAST_UPDATE_ACCOUNT_STATUS_DATA, time());
        } elseif ($this->configHelper->get(ConfigHelper::ACCOUNT_STATUS_DATA)) {
            $status = json_decode($this->configHelper->get(ConfigHelper::ACCOUNT_STATUS_DATA), true);
        }
        self::$statusAccount = $status;
        return $status;
    }

    /**
     * Get marketplace data
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return array|false
     */
    public function getMarketplaces(bool $force = false, bool $logOutput = false)
    {
        $sep = DIRECTORY_SEPARATOR;
        $folderPath = $this->dataHelper->getMediaPath() . $sep . DataHelper::LENGOW_FOLDER;
        $filePath = $folderPath . $sep . self::MARKETPLACE_FILE;
        if (!$force) {
            $updatedAt = $this->configHelper->get(ConfigHelper::LAST_UPDATE_MARKETPLACE);
            if ($updatedAt !== null
                && (time() - (int) $updatedAt) < $this->cacheTimes[self::SYNC_MARKETPLACE]
                && file_exists($filePath)
            ) {
                // recovering data with the marketplaces.json file
                $marketplacesData = file_get_contents($filePath);
                if ($marketplacesData) {
                    // don't add true, decoded data are used as object
                    return json_decode($marketplacesData);
                }
            }
        }
        // recovering data with the API
        $result = $this->connector->queryApi(
            LengowConnector::GET,
            LengowConnector::API_MARKETPLACE,
            [],
            '',
            $logOutput
        );
        if ($result && is_object($result) && !isset($result->error)) {
            // updated marketplaces.json file
            try {
                $this->driverFile->createDirectory($folderPath);
                $file = $this->driverFile->fileOpen($filePath, 'w+');
                $this->driverFile->fileLock($file);
                $this->driverFile->fileWrite($file, $this->jsonHelper->jsonEncode($result));
                $this->driverFile->fileUnlock($file);
                $this->driverFile->fileClose($file);
                $this->configHelper->set(ConfigHelper::LAST_UPDATE_MARKETPLACE, time());
            } catch (FileSystemException $e) {
                $this->dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->dataHelper->setLogMessage('marketplace update failed - %1', [$e->getMessage()]),
                    $logOutput
                );
            }
            return $result;
        }

        // if the API does not respond, use marketplaces.json if it exists
        if (file_exists($filePath)) {
            $marketplacesData = file_get_contents($filePath);
            if ($marketplacesData) {
                // don't add true, decoded data are used as object
                return json_decode($marketplacesData);
            }
        }

        return false;
    }

    /**
     * Get Lengow plugin data (last version and download link)
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return array|false
     */
    public function getPluginData(bool $force = false, bool $logOutput = false)
    {
        if (!$force) {
            $updatedAt = $this->configHelper->get(ConfigHelper::LAST_UPDATE_PLUGIN_DATA);
            if ($updatedAt !== null && (time() - (int) $updatedAt) < $this->cacheTimes[self::SYNC_PLUGIN_DATA]) {
                return json_decode($this->configHelper->get(ConfigHelper::PLUGIN_DATA), true);
            }
        }
        $plugins = $this->connector->queryApi(
            LengowConnector::GET,
            LengowConnector::API_PLUGIN,
            [],
            '',
            $logOutput
        );
        if ($plugins) {
            $pluginData = false;
            foreach ($plugins as $plugin) {
                if ($plugin->type === self::CMS_TYPE . '2') {
                    $cmsMinVersion = '';
                    $cmsMaxVersion = '';
                    $pluginLinks = [];
                    $currentVersion = $plugin->version;
                    if (!empty($plugin->versions)) {
                        foreach ($plugin->versions as $version) {
                            if ($version->version === $currentVersion) {
                                $cmsMinVersion = $version->cms_min_version;
                                $cmsMaxVersion = $version->cms_max_version;
                                break;
                            }
                        }
                    }
                    if (!empty($plugin->links)) {
                        foreach ($plugin->links as $link) {
                            if (array_key_exists($link->language->iso_a2, $this->genericIsoCodes)) {
                                $genericIsoCode = $this->genericIsoCodes[$link->language->iso_a2];
                                $pluginLinks[$genericIsoCode][$link->link_type] = $link->link;
                            }
                        }
                    }
                    $pluginData = [
                        'version' => $currentVersion,
                        'download_link' => $plugin->archive,
                        'cms_min_version' => $cmsMinVersion,
                        'cms_max_version' => $cmsMaxVersion,
                        'links' => $pluginLinks,
                        'extensions' => $plugin->extensions,
                    ];
                    break;
                }
            }
            if ($pluginData) {
                $this->configHelper->set(ConfigHelper::PLUGIN_DATA, $this->jsonHelper->jsonEncode($pluginData));
                $this->configHelper->set(ConfigHelper::LAST_UPDATE_PLUGIN_DATA, time());
                return $pluginData;
            }
        } elseif ($this->configHelper->get(ConfigHelper::PLUGIN_DATA)) {
            return json_decode($this->configHelper->get(ConfigHelper::PLUGIN_DATA), true);
        }
        return false;
    }

    /**
     * Get an array of plugin links for a specific iso code
     *
     * @param string|null $isoCode
     *
     * @return array
     */
    public function getPluginLinks(string $isoCode = null): array
    {
        $pluginData = $this->getPluginData();
        if (!$pluginData) {
            return $this->defaultPluginLinks;
        }
        // check if the links are available in the locale
        $isoCode = $isoCode ?: DataHelper::DEFAULT_ISO_CODE;
        $localeLinks = $pluginData['links'][$isoCode] ?? false;
        $defaultLocaleLinks = $pluginData['links'][DataHelper::DEFAULT_ISO_CODE] ?? false;
        // for each type of link, we check if the link is translated
        $pluginLinks = [];
        foreach ($this->defaultPluginLinks as $linkType => $defaultLink) {
            if ($localeLinks && isset($localeLinks[$linkType])) {
                $link = $localeLinks[$linkType];
            } elseif ($defaultLocaleLinks && isset($defaultLocaleLinks[$linkType])) {
                $link = $defaultLocaleLinks[$linkType];
            } else {
                $link = $defaultLink;
            }
            $pluginLinks[$linkType] = $link;
        }
        return $pluginLinks;
    }
}
