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
 * @subpackage  Helper
 * @author      Team module <team-module@lengow.com>
 * @copyright   2021 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\Dir\Reader as ModuleReader;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Model\Export as LengowExport;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Log as LengowLog;

class Toolbox extends AbstractHelper
{
    /* Toolbox GET params */
    const PARAM_TOKEN = 'token';
    const PARAM_TOOLBOX_ACTION = 'toolbox_action';
    const PARAM_DATE = 'date';
    const PARAM_TYPE = 'type';

    /* Toolbox Actions */
    const ACTION_DATA = 'data';
    const ACTION_LOG = 'log';

    /* Data type */
    const DATA_TYPE_ALL = 'all';
    const DATA_TYPE_CHECKLIST = 'checklist';
    const DATA_TYPE_CHECKSUM = 'checksum';
    const DATA_TYPE_CMS = 'cms';
    const DATA_TYPE_LOG = 'log';
    const DATA_TYPE_PLUGIN = 'plugin';
    const DATA_TYPE_OPTION = 'option';
    const DATA_TYPE_SHOP = 'shop';
    const DATA_TYPE_SYNCHRONIZATION = 'synchronization';

    /* Toolbox Data  */
    const CHECKLIST = 'checklist';
    const CHECKLIST_CURL_ACTIVATED = 'curl_activated';
    const CHECKLIST_SIMPLE_XML_ACTIVATED = 'simple_xml_activated';
    const CHECKLIST_JSON_ACTIVATED = 'json_activated';
    const CHECKLIST_MD5_SUCCESS = 'md5_success';
    const PLUGIN = 'plugin';
    const PLUGIN_CMS_VERSION = 'cms_version';
    const PLUGIN_VERSION = 'plugin_version';
    const PLUGIN_DEBUG_MODE_DISABLE = 'debug_mode_disable';
    const PLUGIN_WRITE_PERMISSION = 'write_permission';
    const PLUGIN_SERVER_IP = 'server_ip';
    const PLUGIN_AUTHORIZED_IP_ENABLE = 'authorized_ip_enable';
    const PLUGIN_AUTHORIZED_IPS = 'authorized_ips';
    const PLUGIN_TOOLBOX_URL = 'toolbox_url';
    const SYNCHRONIZATION = 'synchronization';
    const SYNCHRONIZATION_CMS_TOKEN = 'cms_token';
    const SYNCHRONIZATION_CRON_URL = 'cron_url';
    const SYNCHRONIZATION_NUMBER_ORDERS_IMPORTED = 'number_orders_imported';
    const SYNCHRONIZATION_NUMBER_ORDERS_WAITING_SHIPMENT = 'number_orders_waiting_shipment';
    const SYNCHRONIZATION_NUMBER_ORDERS_IN_ERROR = 'number_orders_in_error';
    const SYNCHRONIZATION_SYNCHRONIZATION_IN_PROGRESS = 'synchronization_in_progress';
    const SYNCHRONIZATION_LAST_SYNCHRONIZATION = 'last_synchronization';
    const SYNCHRONIZATION_LAST_SYNCHRONIZATION_TYPE = 'last_synchronization_type';
    const CMS_OPTIONS = 'cms_options';
    const SHOPS = 'shops';
    const SHOP_ID = 'shop_id';
    const SHOP_NAME = 'shop_name';
    const SHOP_DOMAIN_URL = 'domain_url';
    const SHOP_TOKEN = 'shop_token';
    const SHOP_FEED_URL = 'feed_url';
    const SHOP_ENABLED = 'enabled';
    const SHOP_CATALOG_IDS = 'catalog_ids';
    const SHOP_NUMBER_PRODUCTS_AVAILABLE = 'number_products_available';
    const SHOP_NUMBER_PRODUCTS_EXPORTED = 'number_products_exported';
    const SHOP_LAST_EXPORT = 'last_export';
    const SHOP_OPTIONS = 'shop_options';
    const CHECKSUM = 'checksum';
    const CHECKSUM_AVAILABLE = 'available';
    const CHECKSUM_SUCCESS = 'success';
    const CHECKSUM_NUMBER_FILES_CHECKED = 'number_files_checked';
    const CHECKSUM_NUMBER_FILES_MODIFIED = 'number_files_modified';
    const CHECKSUM_NUMBER_FILES_DELETED = 'number_files_deleted';
    const CHECKSUM_FILE_MODIFIED = 'file_modified';
    const CHECKSUM_FILE_DELETED = 'file_deleted';
    const LOGS = 'logs';

    /* Toolbox files  */
    const FILE_CHECKMD5 = 'checkmd5.csv';
    const FILE_TEST = 'test.txt';

    /**
     * @var array valid toolbox actions
     */
    private $toolboxActions = [
        self::ACTION_DATA,
        self::ACTION_LOG,
    ];

    /**
     * @var ModuleReader Magento module reader instance
     */
    protected $moduleReader;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $dataHelper;

    /**
     * @var ImportHelper Lengow import helper instance
     */
    protected $importHelper;

    /**
     * @var SecurityHelper Lengow security helper instance
     */
    protected $securityHelper;

    /**
     * @var LengowExport Lengow export instance
     */
    protected $lengowExport;

    /**
     * @var LengowLog Lengow log instance
     */
    protected $lengowLog;

    /**
     * @var LengowOrder Lengow order instance
     */
    protected $lengowOrder;

    /**
     * Constructor
     *
     * @param Context $context Magento context instance
     * @param ModuleReader $moduleReader Magento module reader instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param ImportHelper $importHelper Lengow import helper instance
     * @param SecurityHelper $securityHelper Lengow security helper instance
     * @param LengowExport $lengowExport Lengow export instance
     * @param LengowLog $lengowLog Lengow log instance
     * @param LengowOrder $lengowOrder Lengow order instance
     */
    public function __construct(
        Context $context,
        ModuleReader $moduleReader,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        SecurityHelper $securityHelper,
        LengowExport $lengowExport,
        LengowLog $lengowLog,
        LengowOrder $lengowOrder
    ) {
        $this->moduleReader = $moduleReader;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->importHelper = $importHelper;
        $this->securityHelper = $securityHelper;
        $this->lengowExport = $lengowExport;
        $this->lengowLog = $lengowLog;
        $this->lengowOrder = $lengowOrder;
        parent::__construct($context);
    }

    /**
     * Get all toolbox data
     *
     * @param string $type Toolbox data type
     *
     * @return array
     */
    public function getData($type = self::DATA_TYPE_CMS)
    {
        switch ($type) {
            case self::DATA_TYPE_ALL:
                return $this->getAllData();
            case self::DATA_TYPE_CHECKLIST:
                return $this->getChecklistData();
            case self::DATA_TYPE_CHECKSUM:
                return $this->getChecksumData();
            case self::DATA_TYPE_LOG:
                return $this->getLogData();
            case self::DATA_TYPE_OPTION:
                return $this->getOptionData();
            case self::DATA_TYPE_PLUGIN:
                return $this->getPluginData();
            case self::DATA_TYPE_SHOP:
                return $this->getShopData();
            case self::DATA_TYPE_SYNCHRONIZATION:
                return $this->getSynchronizationData();
            default:
            case self::DATA_TYPE_CMS:
                return $this->getCmsData();
        }
    }

    /**
     * Download log file individually or globally
     *
     * @param string|null $date name of file to download
     */
    public function downloadLog($date = null)
    {
        $this->lengowLog->download($date);
    }

    /**
     * Is toolbox action
     *
     * @param string $action toolbox action
     *
     * @return bool
     */
    public function isToolboxAction($action)
    {
        return in_array($action, $this->toolboxActions, true);
    }

    /**
     * Check if PHP Curl is activated
     *
     * @return bool
     */
    public static function isCurlActivated()
    {
        return function_exists('curl_version');
    }

    /**
     * Get all toolbox data
     *
     * @return array
     */
    private function getAllData()
    {
        return [
            self::CHECKLIST => $this->getChecklistData(),
            self::PLUGIN => $this->getPluginData(),
            self::SYNCHRONIZATION => $this->getSynchronizationData(),
            self::CMS_OPTIONS => $this->configHelper->getAllValues(null, true),
            self::SHOPS => $this->getShopData(),
            self::CHECKSUM => $this->getChecksumData(),
            self::LOGS => $this->getLogData(),
        ];
    }

    /**
     * Get cms data
     *
     * @return array
     */
    private function getCmsData()
    {
        return [
            self::CHECKLIST => $this->getChecklistData(),
            self::PLUGIN => $this->getPluginData(),
            self::SYNCHRONIZATION => $this->getSynchronizationData(),
            self::CMS_OPTIONS => $this->configHelper->getAllValues(null, true),
        ];
    }

    /**
     * Get array of requirements
     *
     * @return array
     */
    private function getChecklistData()
    {
        $checksumData = $this->getChecksumData();
        return [
            self::CHECKLIST_CURL_ACTIVATED => self::isCurlActivated(),
            self::CHECKLIST_SIMPLE_XML_ACTIVATED => $this->isSimpleXMLActivated(),
            self::CHECKLIST_JSON_ACTIVATED  => $this->isJsonActivated(),
            self::CHECKLIST_MD5_SUCCESS => $checksumData[self::CHECKSUM_SUCCESS],
        ];
    }

    /**
     * Get array of plugin data
     *
     * @return array
     */
    private function getPluginData()
    {
        return [
            self::PLUGIN_CMS_VERSION => $this->securityHelper->getMagentoVersion(),
            self::PLUGIN_VERSION => $this->securityHelper->getPluginVersion(),
            self::PLUGIN_DEBUG_MODE_DISABLE => !$this->configHelper->debugModeIsActive(),
            self::PLUGIN_WRITE_PERMISSION => $this->testWritePermission(),
            self::PLUGIN_SERVER_IP => $_SERVER['SERVER_ADDR'],
            self::PLUGIN_AUTHORIZED_IP_ENABLE => (bool) $this->configHelper->get(ConfigHelper::AUTHORIZED_IP_ENABLED),
            self::PLUGIN_AUTHORIZED_IPS => $this->configHelper->getAuthorizedIps(),
            self::PLUGIN_TOOLBOX_URL => $this->dataHelper->getToolboxUrl(),
        ];
    }

    /**
     * Get array of import data
     *
     * @return array
     */
    private function getSynchronizationData()
    {
        $lastImport = $this->importHelper->getLastImport();
        return [
            self::SYNCHRONIZATION_CMS_TOKEN => $this->configHelper->getToken(),
            self::SYNCHRONIZATION_CRON_URL => $this->dataHelper->getCronUrl(),
            self::SYNCHRONIZATION_NUMBER_ORDERS_IMPORTED => $this->lengowOrder->countOrderImportedByLengow(),
            self::SYNCHRONIZATION_NUMBER_ORDERS_WAITING_SHIPMENT => $this->lengowOrder->countOrderToBeSent(),
            self::SYNCHRONIZATION_NUMBER_ORDERS_IN_ERROR => $this->lengowOrder->countOrderWithError(),
            self::SYNCHRONIZATION_SYNCHRONIZATION_IN_PROGRESS => $this->importHelper->isInProcess(),
            self::SYNCHRONIZATION_LAST_SYNCHRONIZATION => $lastImport['type'] === 'none' ? 0 : $lastImport['timestamp'],
            self::SYNCHRONIZATION_LAST_SYNCHRONIZATION_TYPE => $lastImport['type'],
        ];
    }

    /**
     * Get array of export data
     *
     * @return array
     */
    private function getShopData()
    {
        $exportData = [];
        $stores = $this->configHelper->getAllStore();
        if (empty($stores)) {
            return $exportData;
        }
        foreach ($stores as $store) {
            $storeId = $store->getId();
            $this->lengowExport->init([LengowExport::PARAM_STORE_ID => $storeId]);
            $lastExport = $this->configHelper->get(ConfigHelper::LAST_UPDATE_EXPORT, $storeId);
            $exportData[] = [
                self::SHOP_ID => $storeId,
                self::SHOP_NAME => $store->getName(),
                self::SHOP_DOMAIN_URL => $store->getBaseUrl(),
                self::SHOP_TOKEN => $this->configHelper->getToken($storeId),
                self::SHOP_FEED_URL => $this->dataHelper->getExportUrl($storeId),
                self::SHOP_ENABLED => $this->configHelper->storeIsActive($storeId),
                self::SHOP_CATALOG_IDS => $this->configHelper->getCatalogIds($storeId),
                self::SHOP_NUMBER_PRODUCTS_AVAILABLE => $this->lengowExport->getTotalProduct(),
                self::SHOP_NUMBER_PRODUCTS_EXPORTED => $this->lengowExport->getTotalExportProduct(),
                self::SHOP_LAST_EXPORT => empty($lastExport) ? 0 : (int) $lastExport,
                self::SHOP_OPTIONS => $this->configHelper->getAllValues($storeId, true),
            ];
        }
        return $exportData;
    }

    /**
     * Get array of export data
     *
     * @return array
     */
    private function getOptionData()
    {
        $optionData = [
            self::CMS_OPTIONS => $this->configHelper->getAllValues(),
            self::SHOP_OPTIONS => [],
        ];
        $stores = $this->configHelper->getAllStore();
        foreach ($stores as $store) {
            $optionData[self::SHOP_OPTIONS][] = $this->configHelper->getAllValues($store->getId());
        }
        return $optionData;
    }

    /**
     * Get files checksum
     *
     * @return array
     */
    private function getChecksumData()
    {
        $fileCounter = 0;
        $fileModified = [];
        $fileDeleted = [];
        $sep = DIRECTORY_SEPARATOR;
        $fileName = $this->moduleReader->getModuleDir('etc', SecurityHelper::MODULE_NAME) . $sep . self::FILE_CHECKMD5;
        if (file_exists($fileName)) {
            $md5Available = true;
            if (($file = fopen($fileName, 'r')) !== false) {
                while (($data = fgetcsv($file, 1000, '|')) !== false) {
                    $fileCounter++;
                    $shortPath = $data[0];
                    $filePath = $this->moduleReader->getModuleDir('', SecurityHelper::MODULE_NAME) . $data[0];
                    if (file_exists($filePath)) {
                        $fileMd = md5_file($filePath);
                        if ($fileMd !== $data[1]) {
                            $fileModified[] = $shortPath;
                        }
                    } else {
                        $fileDeleted[] = $shortPath;
                    }
                }
                fclose($file);
            }
        } else {
            $md5Available = false;
        }
        $fileModifiedCounter = count($fileModified);
        $fileDeletedCounter = count($fileDeleted);
        return [
            self::CHECKSUM_AVAILABLE => $md5Available,
            self::CHECKSUM_SUCCESS => !$md5Available || !($fileModifiedCounter > 0) || !($fileModifiedCounter > 0),
            self::CHECKSUM_NUMBER_FILES_CHECKED => $fileCounter,
            self::CHECKSUM_NUMBER_FILES_MODIFIED => $fileModifiedCounter,
            self::CHECKSUM_NUMBER_FILES_DELETED => $fileDeletedCounter,
            self::CHECKSUM_FILE_MODIFIED => $fileModified,
            self::CHECKSUM_FILE_DELETED => $fileDeleted,
        ];
    }

    /**
     * Get all log files available
     *
     * @return array
     */
    private function getLogData()
    {
        $logs = [];
        $logDates = $this->lengowLog->getAvailableLogDates();
        if (!empty($logDates)) {
            foreach ($logDates as $date) {
                $logs[] = [
                    LengowLog::LOG_DATE => $date,
                    LengowLog::LOG_LINK => $this->dataHelper->getToolboxUrl([
                        self::PARAM_TOOLBOX_ACTION => self::ACTION_LOG,
                        self::PARAM_DATE => urlencode($date),
                    ]),
                ];
            }
            $logs[] = [
                LengowLog::LOG_DATE => null,
                LengowLog::LOG_LINK => $this->dataHelper->getToolboxUrl([
                    self::PARAM_TOOLBOX_ACTION => self::ACTION_LOG,
                ]),
            ];
        }
        return $logs;
    }

    /**
     * Check if SimpleXML Extension is activated
     *
     * @return bool
     */
    private function isSimpleXMLActivated()
    {
        return function_exists('simplexml_load_file');
    }

    /**
     * Check if SimpleXML Extension is activated
     *
     * @return bool
     */
    private function isJsonActivated()
    {
        return function_exists('json_decode');
    }

    /**
     * Test write permission for log and export in file
     *
     * @return bool
     */
    private function testWritePermission()
    {
        $sep = DIRECTORY_SEPARATOR;
        $filePath = $this->dataHelper->getMediaPath() . $sep . DataHelper::LENGOW_FOLDER . $sep . self::FILE_TEST;
        try {
            $file = fopen($filePath, 'w+');
            if (!$file) {
                return false;
            }
            unlink($filePath);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
