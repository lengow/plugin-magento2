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

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Model\Connector as Connector;
use Lengow\Connector\Model\Export as Export;

class Sync extends AbstractHelper
{
    /**
     * @var string cms type
     */
    const CMS_TYPE = 'magento';

    /**
     * @var string sync catalog action
     */
    const SYNC_CATALOG = 'catalog';

    /**
     * @var string sync cms option action
     */
    const SYNC_CMS_OPTION = 'cms_option';

    /**
     * @var string sync status account action
     */
    const SYNC_STATUS_ACCOUNT = 'status_account';

    /**
     * @var string sync marketplace action
     */
    const SYNC_MARKETPLACE = 'marketplace';

    /**
     * @var string sync order action
     */
    const SYNC_ORDER = 'order';

    /**
     * @var string sync action action
     */
    const SYNC_ACTION = 'action';

    /**
     * @var mixed status account
     */
    public static $statusAccount;

    /**
     * @var \Magento\Framework\Json\Helper\Data Magento json helper instance
     */
    protected $_jsonHelper;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File Magento driver file instance
     */
    protected $_driverFile;

    /**
     * @var \Magento\Framework\Module\Dir\Reader Magento module reader instance
     */
    protected $_moduleReader;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface Magento datetime timezone instance
     */
    protected $_timezone;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Helper\Security Lengow security helper instance
     */
    protected $_securityHelper;

    /**
     * @var \Lengow\Connector\Model\Connector Lengow connector instance
     */
    protected $_connector;

    /**
     * @var \Lengow\Connector\Model\Export Lengow export instance
     */
    protected $_export;

    /**
     * @var array cache time for catalog, account status, cms options and marketplace synchronisation
     */
    protected $_cacheTimes = [
        self::SYNC_CATALOG => 21600,
        self::SYNC_CMS_OPTION => 86400,
        self::SYNC_STATUS_ACCOUNT => 86400,
        self::SYNC_MARKETPLACE => 43200,
    ];

    /**
     * @var array valid sync actions
     */
    protected $_syncActions = [
        self::SYNC_ORDER,
        self::SYNC_CMS_OPTION,
        self::SYNC_STATUS_ACCOUNT,
        self::SYNC_MARKETPLACE,
        self::SYNC_ACTION,
        self::SYNC_CATALOG,
    ];

    /**
     * @var string marketplace file name
     */
    protected $_marketplaceJson = 'marketplaces.json';

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context Magento context instance
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper Magento json helper instance
     * @param \Magento\Framework\Filesystem\Driver\File $driverFile Magento driver file instance
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader Magento module reader instance
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone Magento datetime timezone instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Helper\Security $securityHelper Lengow security helper instance
     * @param \Lengow\Connector\Model\Connector $connector Lengow connector instance
     * @param \Lengow\Connector\Model\Export $export Lengow export instance
     */
    public function __construct(
        Context $context,
        JsonHelper $jsonHelper,
        DriverFile $driverFile,
        Reader $moduleReader,
        TimezoneInterface $timezone,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        SecurityHelper $securityHelper,
        Connector $connector,
        Export $export
    )
    {
        $this->_jsonHelper = $jsonHelper;
        $this->_driverFile = $driverFile;
        $this->_moduleReader = $moduleReader;
        $this->_timezone = $timezone;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_securityHelper = $securityHelper;
        $this->_connector = $connector;
        $this->_export = $export;
        parent::__construct($context);
    }

    /**
     * Is sync action
     *
     * @param string $action sync action
     *
     * @return boolean
     */
    public function isSyncAction($action)
    {
        return in_array($action, $this->_syncActions);
    }

    /**
     * Plugin is blocked or not
     *
     * @return boolean
     */
    public function pluginIsBlocked()
    {
        if ($this->_configHelper->isNewMerchant()) {
            return true;
        }
        $statusAccount = $this->getStatusAccount();
        if (($statusAccount['type'] === 'free_trial' && $statusAccount['expired'])) {
            return true;
        }
        return false;
    }

    /**
     * Get Sync Data (Inscription / Update)
     *
     * @return array
     */
    public function getSyncData()
    {
        $data = [
            'domain_name' => $_SERVER['SERVER_NAME'],
            'token' => $this->_configHelper->getToken(),
            'type' => self::CMS_TYPE,
            'version' => $this->_securityHelper->getMagentoVersion(),
            'plugin_version' => $this->_securityHelper->getPluginVersion(),
            'email' => $this->scopeConfig->getValue('trans_email/ident_general/email'),
            'cron_url' => $this->_dataHelper->getCronUrl(),
            'return_url' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'],
            'shops' => [],
        ];
        $stores = $this->_configHelper->getAllStore();
        foreach ($stores as $store) {
            $storeId = (int)$store->getId();
            $this->_export->init(['store_id' => $storeId]);
            $data['shops'][$storeId] = [
                'token' => $this->_configHelper->getToken($storeId),
                'shop_name' => $store->getName(),
                'domain_url' => $store->getBaseUrl(),
                'feed_url' => $this->_dataHelper->getExportUrl($storeId),
                'total_product_number' => $this->_export->getTotalProduct(),
                'exported_product_number' => $this->_export->getTotalExportedProduct(),
                'enabled' => $this->_configHelper->storeIsActive($storeId),
            ];
        }
        return $data;
    }

    /**
     * Set store configuration key from Lengow
     *
     * @param array $params Lengow API credentials
     */
    public function sync($params)
    {
        $this->_configHelper->setAccessIds(
            [
                'account_id' => $params['account_id'],
                'access_token' => $params['access_token'],
                'secret_token' => $params['secret_token'],
            ]
        );
        if (isset($params['shops'])) {
            foreach ($params['shops'] as $storeToken => $storeCatalogIds) {
                $store = $this->_configHelper->getStoreByToken($storeToken);
                if ($store) {
                    $this->_configHelper->setCatalogIds($storeCatalogIds['catalog_ids'], (int)$store->getId());
                    $this->_configHelper->setActiveStore((int)$store->getId());
                }
            }
        }
        // save last update date for a specific settings (change synchronisation interval time)
        $this->_configHelper->set('last_setting_update', time());
        // clean config cache to valid configuration
        $this->_configHelper->cleanConfigCache();
    }

    /**
     * Sync Lengow catalogs for order synchronisation
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function syncCatalog($force = false, $logOutput = false)
    {
        $cleanCache = false;
        if ($this->_configHelper->isNewMerchant()) {
            return false;
        }
        if (!$force) {
            $updatedAt = $this->_configHelper->get('last_catalog_update');
            if (!is_null($updatedAt) && (time() - (int)$updatedAt) < $this->_cacheTimes[self::SYNC_CATALOG]) {
                return false;
            }
        }
        $result = $this->_connector->queryApi(Connector::GET, Connector::API_CMS, [], '', $logOutput);
        if (isset($result->cms)) {
            $cmsToken = $this->_configHelper->getToken();
            foreach ($result->cms as $cms) {
                if ($cms->token === $cmsToken) {
                    foreach ($cms->shops as $cmsShop) {
                        $store = $this->_configHelper->getStoreByToken($cmsShop->token);
                        if ($store) {
                            $catalogIdsChange = $this->_configHelper->setCatalogIds(
                                $cmsShop->catalog_ids,
                                (int)$store->getId()
                            );
                            $activeStoreChange = $this->_configHelper->setActiveStore((int)$store->getId());
                            if (!$cleanCache && ($catalogIdsChange || $activeStoreChange)) {
                                $cleanCache = true;
                            }
                        }
                    }
                    break;
                }
            }
        }
        // clean config cache to valid configuration
        if ($cleanCache) {
            // save last update date for a specific settings (change synchronisation interval time)
            $this->_configHelper->set('last_setting_update', time());
            $this->_configHelper->cleanConfigCache();
        }
        $this->_configHelper->set('last_catalog_update', time());
        return true;
    }

    /**
     * Get options for all store
     *
     * @return array
     */
    public function getOptionData()
    {
        $data = [
            'token' => $this->_configHelper->getToken(),
            'version' => $this->_securityHelper->getMagentoVersion(),
            'plugin_version' => $this->_securityHelper->getPluginVersion(),
            'options' => $this->_configHelper->getAllValues(),
            'shops' => [],
        ];
        $stores = $this->_configHelper->getAllStore();
        foreach ($stores as $store) {
            $storeId = (int)$store->getId();
            $this->_export->init(['store_id' => $storeId]);
            $data['shops'][] = [
                'token' => $this->_configHelper->getToken($storeId),
                'enabled' => $this->_configHelper->storeIsActive($storeId),
                'total_product_number' => $this->_export->getTotalProduct(),
                'exported_product_number' => $this->_export->getTotalExportedProduct(),
                'options' => $this->_configHelper->getAllValues($storeId),
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
    public function setCmsOption($force = false, $logOutput = false)
    {
        if ($this->_configHelper->isNewMerchant() || (bool)$this->_configHelper->get('preprod_mode_enable')) {
            return false;
        }
        if (!$force) {
            $updatedAt = $this->_configHelper->get('last_option_cms_update');
            if (!is_null($updatedAt) && (time() - (int)$updatedAt) < $this->_cacheTimes[self::SYNC_CMS_OPTION]) {
                return false;
            }
        }
        $options = $this->_jsonHelper->jsonEncode($this->getOptionData());
        $this->_connector->queryApi(Connector::PUT, Connector::API_CMS, [], $options, $logOutput);
        $this->_configHelper->set('last_option_cms_update', time());
        return true;
    }

    /**
     * Get Status Account
     *
     * @param boolean $force force cache update
     * @param boolean $logOutput see log or not
     *
     * @return array|false
     */
    public function getStatusAccount($force = false, $logOutput = false)
    {
        if ($this->_configHelper->isNewMerchant()) {
            return false;
        }
        if (!$force) {
            $updatedAt = $this->_configHelper->get('last_status_update');
            if (!is_null($updatedAt) && (time() - (int)$updatedAt) < $this->_cacheTimes[self::SYNC_STATUS_ACCOUNT]) {
                return json_decode($this->_configHelper->get('account_status'), true);
            }
        }
        // use static cache for multiple call in same time (specific Magento 2)
        if (!is_null(self::$statusAccount)) {
            return self::$statusAccount;
        }
        $status = false;
        $result = $this->_connector->queryApi(Connector::GET, Connector::API_PLAN, [], '', $logOutput);
        if (isset($result->isFreeTrial)) {
            $status = [
                'type' => $result->isFreeTrial ? 'free_trial' : '',
                'day' => (int)$result->leftDaysBeforeExpired < 0 ? 0 : (int)$result->leftDaysBeforeExpired,
                'expired' => (bool)$result->isExpired,
            ];
            $this->_configHelper->set('account_status', $this->_jsonHelper->jsonEncode($status));
            $this->_configHelper->set('last_status_update', time());
        } else {
            if ($this->_configHelper->get('last_status_update')) {
                $status = json_decode($this->_configHelper->get('account_status'), true);
            }
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
    public function getMarketplaces($force = false, $logOutput = false)
    {
        $sep = DIRECTORY_SEPARATOR;
        $folderPath = $this->_moduleReader->getModuleDir('etc', 'Lengow_Connector');
        $filePath = $folderPath . $sep . $this->_marketplaceJson;
        if (!$force) {
            $updatedAt = $this->_configHelper->get('last_marketplace_update');
            if (!is_null($updatedAt)
                && (time() - (int)$updatedAt) < $this->_cacheTimes[self::SYNC_MARKETPLACE]
                && file_exists($filePath)
            ) {
                // recovering data with the marketplaces.json file
                $marketplacesData = file_get_contents($filePath);
                if ($marketplacesData) {
                    return json_decode($marketplacesData);
                }
            }
        }
        // recovering data with the API
        $result = $this->_connector->queryApi(Connector::GET, Connector::API_MARKETPLACE, [], '', $logOutput);
        if ($result && is_object($result) && !isset($result->error)) {
            // updated marketplaces.json file
            try {
                $file = $this->_driverFile->fileOpen($filePath, 'w+');
                $this->_driverFile->fileLock($file);
                $this->_driverFile->fileWrite($file, $this->_jsonHelper->jsonEncode($result));
                $this->_driverFile->fileUnlock($file);
                $this->_driverFile->fileClose($file);
                $this->_configHelper->set('last_marketplace_update', time());
            } catch (FileSystemException $e) {
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->_dataHelper->setLogMessage('marketplace update failed - %1', [$e->getMessage()]),
                    $logOutput
                );
            }
            return $result;
        } else {
            // if the API does not respond, use marketplaces.json if it exists
            if (file_exists($filePath)) {
                $marketplacesData = file_get_contents($filePath);
                if ($marketplacesData) {
                    return json_decode($marketplacesData);
                }
            }
        }
        return false;
    }
}
