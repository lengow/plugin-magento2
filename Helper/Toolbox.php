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

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\Dir\Reader as ModuleReader;
use Magento\Sales\Api\Data\OrderInterface as MagentoOrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Model\Connector as LengowConnector;
use Lengow\Connector\Model\Export as LengowExport;
use Lengow\Connector\Model\Import as LengowImport;
use Lengow\Connector\Model\Import\Action as LengowAction;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\Ordererror as LengowOrderError;
use Lengow\Connector\Model\Log as LengowLog;

class Toolbox extends AbstractHelper
{
    /* Toolbox GET params */
    public const PARAM_CREATED_FROM = 'created_from';
    public const PARAM_CREATED_TO = 'created_to';
    public const PARAM_DATE = 'date';
    public const PARAM_DAYS = 'days';
    public const PARAM_FORCE = 'force';
    public const PARAM_MARKETPLACE_NAME = 'marketplace_name';
    public const PARAM_MARKETPLACE_SKU = 'marketplace_sku';
    public const PARAM_PROCESS = 'process';
    public const PARAM_SHOP_ID = 'shop_id';
    public const PARAM_TOKEN = 'token';
    public const PARAM_TOOLBOX_ACTION = 'toolbox_action';
    public const PARAM_TYPE = 'type';

    /* Toolbox Actions */
    public const ACTION_DATA = 'data';
    public const ACTION_LOG = 'log';
    public const ACTION_ORDER = 'order';

    /* Data data type */
    public const DATA_TYPE_ACTION = 'action';
    public const DATA_TYPE_ALL = 'all';
    public const DATA_TYPE_CHECKLIST = 'checklist';
    public const DATA_TYPE_CHECKSUM = 'checksum';
    public const DATA_TYPE_CMS = 'cms';
    public const DATA_TYPE_ERROR = 'error';
    public const DATA_TYPE_EXTRA = 'extra';
    public const DATA_TYPE_LOG = 'log';
    public const DATA_TYPE_PLUGIN = 'plugin';
    public const DATA_TYPE_OPTION = 'option';
    public const DATA_TYPE_ORDER = 'order';
    public const DATA_TYPE_ORDER_STATUS = 'order_status';
    public const DATA_TYPE_SHOP = 'shop';
    public const DATA_TYPE_SYNCHRONIZATION = 'synchronization';

    /* Process type */
    public const PROCESS_TYPE_GET_DATA = 'get_data';
    public const PROCESS_TYPE_SYNC = 'sync';

    /* Toolbox Data  */
    public const CHECKLIST = 'checklist';
    public const CHECKLIST_CURL_ACTIVATED = 'curl_activated';
    public const CHECKLIST_SIMPLE_XML_ACTIVATED = 'simple_xml_activated';
    public const CHECKLIST_JSON_ACTIVATED = 'json_activated';
    public const CHECKLIST_MD5_SUCCESS = 'md5_success';
    public const PLUGIN = 'plugin';
    public const PLUGIN_CMS_VERSION = 'cms_version';
    public const PLUGIN_VERSION = 'plugin_version';
    public const PLUGIN_PHP_VERSION = 'php_version';
    public const PLUGIN_DEBUG_MODE_DISABLE = 'debug_mode_disable';
    public const PLUGIN_WRITE_PERMISSION = 'write_permission';
    public const PLUGIN_SERVER_IP = 'server_ip';
    public const PLUGIN_AUTHORIZED_IP_ENABLE = 'authorized_ip_enable';
    public const PLUGIN_AUTHORIZED_IPS = 'authorized_ips';
    public const PLUGIN_TOOLBOX_URL = 'toolbox_url';
    public const SYNCHRONIZATION = 'synchronization';
    public const SYNCHRONIZATION_CMS_TOKEN = 'cms_token';
    public const SYNCHRONIZATION_CRON_URL = 'cron_url';
    public const SYNCHRONIZATION_NUMBER_ORDERS_IMPORTED = 'number_orders_imported';
    public const SYNCHRONIZATION_NUMBER_ORDERS_WAITING_SHIPMENT = 'number_orders_waiting_shipment';
    public const SYNCHRONIZATION_NUMBER_ORDERS_IN_ERROR = 'number_orders_in_error';
    public const SYNCHRONIZATION_SYNCHRONIZATION_IN_PROGRESS = 'synchronization_in_progress';
    public const SYNCHRONIZATION_LAST_SYNCHRONIZATION = 'last_synchronization';
    public const SYNCHRONIZATION_LAST_SYNCHRONIZATION_TYPE = 'last_synchronization_type';
    public const CMS_OPTIONS = 'cms_options';
    public const SHOPS = 'shops';
    public const SHOP_ID = 'shop_id';
    public const SHOP_NAME = 'shop_name';
    public const SHOP_DOMAIN_URL = 'domain_url';
    public const SHOP_TOKEN = 'shop_token';
    public const SHOP_FEED_URL = 'feed_url';
    public const SHOP_ENABLED = 'enabled';
    public const SHOP_CATALOG_IDS = 'catalog_ids';
    public const SHOP_NUMBER_PRODUCTS_AVAILABLE = 'number_products_available';
    public const SHOP_NUMBER_PRODUCTS_EXPORTED = 'number_products_exported';
    public const SHOP_LAST_EXPORT = 'last_export';
    public const SHOP_OPTIONS = 'shop_options';
    public const CHECKSUM = 'checksum';
    public const CHECKSUM_AVAILABLE = 'available';
    public const CHECKSUM_SUCCESS = 'success';
    public const CHECKSUM_NUMBER_FILES_CHECKED = 'number_files_checked';
    public const CHECKSUM_NUMBER_FILES_MODIFIED = 'number_files_modified';
    public const CHECKSUM_NUMBER_FILES_DELETED = 'number_files_deleted';
    public const CHECKSUM_FILE_MODIFIED = 'file_modified';
    public const CHECKSUM_FILE_DELETED = 'file_deleted';
    public const LOGS = 'logs';

    /* Toolbox order data  */
    public const ID = 'id';
    public const ORDERS = 'orders';
    public const ORDER_MARKETPLACE_SKU = 'marketplace_sku';
    public const ORDER_MARKETPLACE_NAME = 'marketplace_name';
    public const ORDER_MARKETPLACE_LABEL = 'marketplace_label';
    public const ORDER_MERCHANT_ORDER_ID = 'merchant_order_id';
    public const ORDER_MERCHANT_ORDER_REFERENCE = 'merchant_order_reference';
    public const ORDER_DELIVERY_ADDRESS_ID = 'delivery_address_id';
    public const ORDER_DELIVERY_COUNTRY_ISO = 'delivery_country_iso';
    public const ORDER_PROCESS_STATE = 'order_process_state';
    public const ORDER_STATUSES = 'order_statuses';
    public const ORDER_STATUS = 'order_status';
    public const ORDER_MERCHANT_ORDER_STATUS = 'merchant_order_status';
    public const ORDER_TOTAL_PAID = 'total_paid';
    public const ORDER_MERCHANT_TOTAL_PAID = 'merchant_total_paid';
    public const ORDER_COMMISSION= 'commission';
    public const ORDER_CURRENCY = 'currency';
    public const ORDER_DATE = 'order_date';
    public const ORDER_ITEMS = 'order_items';
    public const ORDER_IS_REIMPORTED = 'is_reimported';
    public const ORDER_IS_IN_ERROR = 'is_in_error';
    public const ORDER_ACTION_IN_PROGRESS = 'action_in_progress';
    public const CUSTOMER = 'customer';
    public const CUSTOMER_NAME = 'name';
    public const CUSTOMER_EMAIL = 'email';
    public const CUSTOMER_VAT_NUMBER = 'vat_number';
    public const ORDER_TYPES = 'order_types';
    public const ORDER_TYPE_EXPRESS = 'is_express';
    public const ORDER_TYPE_PRIME = 'is_prime';
    public const ORDER_TYPE_BUSINESS = 'is_business';
    public const ORDER_TYPE_DELIVERED_BY_MARKETPLACE = 'is_delivered_by_marketplace';
    public const TRACKING = 'tracking';
    public const TRACKING_CARRIER = 'carrier';
    public const TRACKING_METHOD = 'method';
    public const TRACKING_NUMBER = 'tracking_number';
    public const TRACKING_RELAY_ID = 'relay_id';
    public const TRACKING_DELIVERED_BY_MARKETPLACE = 'is_delivered_by_marketplace';
    public const TRACKING_MERCHANT_CARRIER = 'merchant_carrier';
    public const TRACKING_MERCHANT_TRACKING_NUMBER = 'merchant_tracking_number';
    public const TRACKING_MERCHANT_TRACKING_URL = 'merchant_tracking_url';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const IMPORTED_AT = 'imported_at';
    public const ERRORS = 'errors';
    public const ERROR_TYPE = 'type';
    public const ERROR_MESSAGE = 'message';
    public const ERROR_CODE = 'code';
    public const ERROR_FINISHED = 'is_finished';
    public const ERROR_REPORTED = 'is_reported';
    public const ACTIONS = 'actions';
    public const ACTION_ID = 'action_id';
    public const ACTION_PARAMETERS = 'parameters';
    public const ACTION_RETRY = 'retry';
    public const ACTION_FINISH = 'is_finished';

    /* Process state labels */
    private const PROCESS_STATE_NEW = 'new';
    private const PROCESS_STATE_IMPORT = 'import';
    private const PROCESS_STATE_FINISH = 'finish';

    /* Error type labels */
    private const TYPE_ERROR_IMPORT = 'import';
    private const TYPE_ERROR_SEND = 'send';

    /* PHP extensions */
    private const PHP_EXTENSION_CURL = 'curl_version';
    private const PHP_EXTENSION_SIMPLEXML = 'simplexml_load_file';
    private const PHP_EXTENSION_JSON = 'json_decode';

    /* Toolbox files  */
    private const FILE_CHECKMD5 = 'checkmd5.csv';
    private const FILE_TEST = 'test.txt';

    /**
     * @var array valid toolbox actions
     */
    private $toolboxActions = [
        self::ACTION_DATA,
        self::ACTION_LOG,
        self::ACTION_ORDER,
    ];

    /**
     * @var ModuleReader Magento module reader instance
     */
    private $moduleReader;

    /**
     * @var OrderRepositoryInterface Magento order repository instance
     */
    private $orderRepository;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var ImportHelper Lengow import helper instance
     */
    private $importHelper;

    /**
     * @var SecurityHelper Lengow security helper instance
     */
    private $securityHelper;

    /**
     * @var LengowExport Lengow export instance
     */
    private $lengowExport;

    /**
     * @var LengowImport Lengow import instance
     */
    private $lengowImport;

    /**
     * @var LengowLog Lengow log instance
     */
    private $lengowLog;

    /**
     * @var LengowAction Lengow action instance
     */
    private $lengowAction;

    /**
     * @var LengowOrder Lengow order instance
     */
    private $lengowOrder;

    /**
     * @var LengowOrderError Lengow order error instance
     */
    private $lengowOrderError;

    /**
     * Constructor
     *
     * @param Context $context Magento context instance
     * @param ModuleReader $moduleReader Magento module reader instance
     * @param OrderRepositoryInterface $orderRepository Magento order instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param ImportHelper $importHelper Lengow import helper instance
     * @param SecurityHelper $securityHelper Lengow security helper instance
     * @param LengowExport $lengowExport Lengow export instance
     * @param LengowImport $lengowImport Lengow import instance
     * @param LengowLog $lengowLog Lengow log instance
     * @param LengowAction $lengowAction Lengow action instance
     * @param LengowOrder $lengowOrder Lengow order instance
     * @param LengowOrderError $lengowOrderError Lengow order error instance
     */
    public function __construct(
        Context $context,
        ModuleReader $moduleReader,
        OrderRepositoryInterface $orderRepository,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        SecurityHelper $securityHelper,
        LengowExport $lengowExport,
        LengowImport $lengowImport,
        LengowLog $lengowLog,
        LengowAction $lengowAction,
        LengowOrder $lengowOrder,
        LengowOrderError $lengowOrderError
    ) {
        $this->moduleReader = $moduleReader;
        $this->orderRepository = $orderRepository;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->importHelper = $importHelper;
        $this->securityHelper = $securityHelper;
        $this->lengowExport = $lengowExport;
        $this->lengowImport = $lengowImport;
        $this->lengowLog = $lengowLog;
        $this->lengowAction = $lengowAction;
        $this->lengowOrder = $lengowOrder;
        $this->lengowOrderError = $lengowOrderError;
        parent::__construct($context);
    }

    /**
     * Get all toolbox data
     *
     * @param string $type Toolbox data type
     *
     * @return array
     */
    public function getData(string $type = self::DATA_TYPE_CMS): array
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
    public function downloadLog(string $date = null): void
    {
        $this->lengowLog->download($date);
    }

    /**
     * Start order synchronization based on specific parameters
     *
     * @param array $params synchronization parameters
     *
     * @return array
     */
    public function syncOrders(array $params = []): array
    {
        // get all params for order synchronization
        $params = $this->filterParamsForSync($params);
        $this->lengowImport->init($params);
        $result = $this->lengowImport->exec();
        // if global error return error message and request http code
        if (isset($result[LengowImport::ERRORS][0])) {
            return $this->generateErrorReturn(LengowConnector::CODE_403, $result[LengowImport::ERRORS][0]);
        }
        unset($result[LengowImport::ERRORS]);
        return $result;
    }

    /**
     * Get all order data from a marketplace reference
     *
     * @param string|null $marketplaceSku marketplace order reference
     * @param string|null $marketplaceName marketplace code
     * @param string $type Toolbox order data type
     *
     * @return array
     */
    public function getOrderData(
        string $marketplaceSku = null,
        string $marketplaceName = null,
        string $type = self::DATA_TYPE_ORDER
    ): array {
        $lengowOrders = $marketplaceSku && $marketplaceName
            ? $this->lengowOrder->getAllLengowOrders($marketplaceSku, $marketplaceName)
            : [];
        // if no reference is found, process is blocked
        if (empty($lengowOrders)) {
            return $this->generateErrorReturn(
                LengowConnector::CODE_404,
                $this->dataHelper->setLogMessage('Unable to find the requested order')
            );
        }
        $orders = [];
        foreach ($lengowOrders as $data) {
            if ($type === self::DATA_TYPE_EXTRA) {
                return $this->getOrderExtraData($data);
            }
            $marketplaceLabel = $data[LengowOrder::FIELD_MARKETPLACE_LABEL];
            $orders[] = $this->getOrderDataByType($data, $type);
        }
        return [
            self::ORDER_MARKETPLACE_SKU => $marketplaceSku,
            self::ORDER_MARKETPLACE_NAME => $marketplaceName,
            self::ORDER_MARKETPLACE_LABEL => $marketplaceLabel ?? null,
            self::ORDERS => $orders,
        ];
    }

    /**
     * Is toolbox action
     *
     * @param string $action toolbox action
     *
     * @return bool
     */
    public function isToolboxAction(string $action): bool
    {
        return in_array($action, $this->toolboxActions, true);
    }

    /**
     * Check if PHP Curl is activated
     *
     * @return bool
     */
    public static function isCurlActivated(): bool
    {
        return function_exists(self::PHP_EXTENSION_CURL);
    }

    /**
     * Get all toolbox data
     *
     * @return array
     */
    private function getAllData(): array
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
    private function getCmsData(): array
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
    private function getChecklistData(): array
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
    private function getPluginData(): array
    {
        return [
            self::PLUGIN_CMS_VERSION => $this->securityHelper->getMagentoVersion(),
            self::PLUGIN_VERSION => $this->securityHelper->getPluginVersion(),
            self::PLUGIN_PHP_VERSION => PHP_VERSION,
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
    private function getSynchronizationData(): array
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
    private function getShopData(): array
    {
        $exportData = [];
        $stores = $this->configHelper->getAllStore();
        if (empty($stores)) {
            return $exportData;
        }
        foreach ($stores as $store) {
            $storeId = (int) $store->getId();
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
    private function getOptionData(): array
    {
        $optionData = [
            self::CMS_OPTIONS => $this->configHelper->getAllValues(),
            self::SHOP_OPTIONS => [],
        ];
        $stores = $this->configHelper->getAllStore();
        foreach ($stores as $store) {
            $optionData[self::SHOP_OPTIONS][] = $this->configHelper->getAllValues((int) $store->getId());
        }
        return $optionData;
    }

    /**
     * Get files checksum
     *
     * @return array
     */
    private function getChecksumData(): array
    {
        $fileCounter = 0;
        $fileModified = [];
        $fileDeleted = [];
        $sep = DIRECTORY_SEPARATOR;
        $fileName = $this->moduleReader->getModuleDir('etc', SecurityHelper::MODULE_NAME) . $sep . self::FILE_CHECKMD5;
        if (file_exists($fileName)) {
            $md5Available = true;
            if (($file = fopen($fileName, 'rb')) !== false) {
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
        $md5Success = $md5Available && !($fileModifiedCounter > 0) && !($fileDeletedCounter > 0);
        return [
            self::CHECKSUM_AVAILABLE => $md5Available,
            self::CHECKSUM_SUCCESS => $md5Success,
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
    private function getLogData(): array
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
    private function isSimpleXMLActivated(): bool
    {
        return function_exists(self::PHP_EXTENSION_SIMPLEXML);
    }

    /**
     * Check if SimpleXML Extension is activated
     *
     * @return bool
     */
    private function isJsonActivated(): bool
    {
        return function_exists(self::PHP_EXTENSION_JSON);
    }

    /**
     * Test write permission for log and export in file
     *
     * @return bool
     */
    private function testWritePermission(): bool
    {
        $sep = DIRECTORY_SEPARATOR;
        $filePath = $this->dataHelper->getMediaPath() . $sep . DataHelper::LENGOW_FOLDER . $sep . self::FILE_TEST;
        try {
            $file = fopen($filePath, 'wb+');
            if (!$file) {
                return false;
            }
            unlink($filePath);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Filter parameters for order synchronization
     *
     * @param array $params synchronization params
     *
     * @return array
     */
    private function filterParamsForSync(array $params = []): array
    {
        $paramsFiltered = [LengowImport::PARAM_TYPE => LengowImport::TYPE_TOOLBOX];
        if (isset(
            $params[self::PARAM_MARKETPLACE_SKU],
            $params[self::PARAM_MARKETPLACE_NAME],
            $params[self::PARAM_SHOP_ID]
        )) {
            // get all parameters to synchronize a specific order
            $paramsFiltered[LengowImport::PARAM_MARKETPLACE_SKU] = $params[self::PARAM_MARKETPLACE_SKU];
            $paramsFiltered[LengowImport::PARAM_MARKETPLACE_NAME] = $params[self::PARAM_MARKETPLACE_NAME];
            $paramsFiltered[LengowImport::PARAM_STORE_ID] = (int) $params[self::PARAM_SHOP_ID];
        } elseif (isset($params[self::PARAM_CREATED_FROM], $params[self::PARAM_CREATED_TO])) {
            // get all parameters to synchronize over a fixed period
            $paramsFiltered[LengowImport::PARAM_CREATED_FROM] = $params[self::PARAM_CREATED_FROM];
            $paramsFiltered[LengowImport::PARAM_CREATED_TO] = $params[self::PARAM_CREATED_TO];
        } elseif (isset($params[self::PARAM_DAYS])) {
            // get all parameters to synchronize over a time interval
            $paramsFiltered[LengowImport::PARAM_DAYS] = (int) $params[self::PARAM_DAYS];
        }
        // force order synchronization by removing pending errors
        if (isset($params[self::PARAM_FORCE])) {
            $paramsFiltered[LengowImport::PARAM_FORCE_SYNC] = (bool) $params[self::PARAM_FORCE];
        }
        return $paramsFiltered;
    }

    /**
     * Get array of all the data of the order
     *
     * @param array $data All Lengow order data
     * @param string $type Toolbox order data type
     *
     * @return array
     */
    private function getOrderDataByType(array $data, string $type): array
    {
        $order = $data[LengowOrder::FIELD_ORDER_ID]
            ? $this->orderRepository->get((int) $data[LengowOrder::FIELD_ORDER_ID])
            : null;
        $orderReferences = [
            self::ID => (int) $data[LengowOrder::FIELD_ID],
            self::ORDER_MERCHANT_ORDER_ID  => $order ? (int) $order->getId() : null,
            self::ORDER_MERCHANT_ORDER_REFERENCE  => $order ? $order->getIncrementId() : null,
            self::ORDER_DELIVERY_ADDRESS_ID => (int) $data[LengowOrder::FIELD_DELIVERY_ADDRESS_ID],
        ];
        switch ($type) {
            case self::DATA_TYPE_ACTION:
                $orderData = [
                    self::ACTIONS => $order ? $this->getOrderActionData((int) $order->getId()) : [],
                ];
                break;
            case self::DATA_TYPE_ERROR:
                $orderData = [
                    self::ERRORS => $this->getOrderErrorsData((int) $data[LengowOrder::FIELD_ID]),
                ];
                break;
            case self::DATA_TYPE_ORDER_STATUS:
                $orderData = [
                    self::ORDER_STATUSES => $order ? $this->getOrderStatusesData($order) : [],
                ];
                break;
            case self::DATA_TYPE_ORDER:
            default:
                $orderData = $this->getAllOrderData($data, $order);
        }
        return array_merge($orderReferences, $orderData);
    }

    /**
     * Get array of all the data of the order
     *
     * @param array $data All Lengow order data
     * @param MagentoOrderInterface|null $order Lengow order instance
     *
     * @return array
     */
    private function getAllOrderData(array $data, MagentoOrderInterface $order = null): array
    {
        $importedAt = 0;
        $lastTrack = null;
        $hasActionInProgress = false;
        $orderTypes = json_decode($data[LengowOrder::FIELD_ORDER_TYPES], true);
        if ($order) {
            $tracks = $order->getShipmentsCollection()->getLastItem()->getAllTracks();
            $lastTrack = !empty($tracks) ? end($tracks) : null;
            $hasActionInProgress = (bool) $this->lengowAction->getActionsByOrderId((int) $order->getId(), true);
            $importedAt = strtotime($order->getStatusHistoryCollection()->getFirstItem()->getCreatedAt());
        }
        return [
            self::ORDER_DELIVERY_COUNTRY_ISO => $data[LengowOrder::FIELD_DELIVERY_COUNTRY_ISO],
            self::ORDER_PROCESS_STATE => $this->getOrderProcessLabel(
                (int) $data[LengowOrder::FIELD_ORDER_PROCESS_STATE]
            ),
            self::ORDER_STATUS => $data[LengowOrder::FIELD_ORDER_LENGOW_STATE],
            self::ORDER_MERCHANT_ORDER_STATUS => $order ? $order->getState() : null,
            self::ORDER_STATUSES => $order ? $this->getOrderStatusesData($order) : [],
            self::ORDER_TOTAL_PAID => (float) $data[LengowOrder::FIELD_TOTAL_PAID],
            self::ORDER_MERCHANT_TOTAL_PAID => $order ? (float) $order->getTotalPaid() : null,
            self::ORDER_COMMISSION => (float) $data[LengowOrder::FIELD_COMMISSION],
            self::ORDER_CURRENCY => $data[LengowOrder::FIELD_CURRENCY],
            self::CUSTOMER => [
                self::CUSTOMER_NAME => !empty($data[LengowOrder::FIELD_CUSTOMER_NAME])
                    ? $data[LengowOrder::FIELD_CUSTOMER_NAME]
                    : null,
                self::CUSTOMER_EMAIL => !empty($data[LengowOrder::FIELD_CUSTOMER_EMAIL])
                    ? $data[LengowOrder::FIELD_CUSTOMER_EMAIL]
                    : null,
                self::CUSTOMER_VAT_NUMBER => !empty($data[LengowOrder::FIELD_CUSTOMER_VAT_NUMBER])
                    ? $data[LengowOrder::FIELD_CUSTOMER_VAT_NUMBER]
                    : null,
            ],
            self::ORDER_DATE => strtotime($data[LengowOrder::FIELD_ORDER_DATE]),
            self::ORDER_TYPES => [
                self::ORDER_TYPE_EXPRESS => isset($orderTypes[LengowOrder::TYPE_EXPRESS]),
                self::ORDER_TYPE_PRIME => isset($orderTypes[LengowOrder::TYPE_PRIME]),
                self::ORDER_TYPE_BUSINESS => isset($orderTypes[LengowOrder::TYPE_BUSINESS]),
                self::ORDER_TYPE_DELIVERED_BY_MARKETPLACE => isset(
                    $orderTypes[LengowOrder::TYPE_DELIVERED_BY_MARKETPLACE]
                ),
            ],
            self::ORDER_ITEMS => (int) $data[LengowOrder::FIELD_ORDER_ITEM],
            self::TRACKING => [
                self::TRACKING_CARRIER => !empty($data[LengowOrder::FIELD_CARRIER])
                    ? $data[LengowOrder::FIELD_CARRIER]
                    : null,
                self::TRACKING_METHOD => !empty($data[LengowOrder::FIELD_CARRIER_METHOD])
                    ? $data[LengowOrder::FIELD_CARRIER_METHOD]
                    : null,
                self::TRACKING_NUMBER => !empty($data[LengowOrder::FIELD_CARRIER_TRACKING])
                    ? $data[LengowOrder::FIELD_CARRIER_TRACKING]
                    : null,
                self::TRACKING_RELAY_ID => !empty($data[LengowOrder::FIELD_CARRIER_RELAY_ID])
                    ? $data[LengowOrder::FIELD_CARRIER_RELAY_ID]
                    : null,
                self::TRACKING_MERCHANT_CARRIER => $lastTrack ? $lastTrack->getTitle() : null,
                self::TRACKING_MERCHANT_TRACKING_NUMBER => $lastTrack ? $lastTrack->getNumber() : null,
                self::TRACKING_MERCHANT_TRACKING_URL => null,
            ],
            self::ORDER_IS_REIMPORTED => (bool) $data[LengowOrder::FIELD_IS_REIMPORTED],
            self::ORDER_IS_IN_ERROR => (bool) $data[LengowOrder::FIELD_IS_IN_ERROR],
            self::ERRORS => $this->getOrderErrorsData((int) $data[LengowOrder::FIELD_ID]),
            self::ORDER_ACTION_IN_PROGRESS => $hasActionInProgress,
            self::ACTIONS => $order ? $this->getOrderActionData((int) $order->getId()) : [],
            self::CREATED_AT => strtotime($data[LengowOrder::FIELD_CREATED_AT]),
            self::UPDATED_AT => strtotime($data[LengowOrder::FIELD_UPDATED_AT]),
            self::IMPORTED_AT => $importedAt,
        ];
    }

    /**
     * Get array of all the errors of a Lengow order
     *
     * @param integer $lengowOrderId Lengow order id
     *
     * @return array
     */
    private function getOrderErrorsData(int $lengowOrderId): array
    {
        $orderErrors = [];
        $errors = $this->lengowOrderError->getOrderErrors($lengowOrderId);
        if ($errors) {
            foreach ($errors as $error) {
                $type = (int) $error[LengowOrderError::FIELD_TYPE];
                $orderErrors[] = [
                    self::ID => (int) $error[LengowOrderError::FIELD_ID],
                    self::ERROR_TYPE => $type === LengowOrderError::TYPE_ERROR_IMPORT
                        ? self::TYPE_ERROR_IMPORT
                        : self::TYPE_ERROR_SEND,
                    self::ERROR_MESSAGE => $this->dataHelper->decodeLogMessage(
                        $error[LengowOrderError::FIELD_MESSAGE],
                        false
                    ),
                    self::ERROR_FINISHED => (bool) $error[LengowOrderError::FIELD_IS_FINISHED],
                    self::ERROR_REPORTED => (bool) $error[LengowOrderError::FIELD_MAIL],
                    self::CREATED_AT => strtotime($error[LengowOrderError::FIELD_CREATED_AT]),
                    self::UPDATED_AT => $error[LengowOrderError::FIELD_UPDATED_AT]
                        ? strtotime($error[LengowOrderError::FIELD_UPDATED_AT])
                        : 0,
                ];
            }
        }
        return $orderErrors;
    }

    /**
     * Get array of all the actions of a Lengow order
     *
     * @param integer $orderId Magento order id
     *
     * @return array
     */
    private function getOrderActionData(int $orderId): array
    {
        $orderActions = [];
        $actions = $this->lengowAction->getActionsByOrderId($orderId);
        if ($actions) {
            foreach ($actions as $action) {
                $orderActions[] = [
                    self::ID => (int) $action[LengowAction::FIELD_ID],
                    self::ACTION_ID => (int) $action[LengowAction::FIELD_ACTION_ID],
                    self::ACTION_PARAMETERS => json_decode($action[LengowAction::FIELD_PARAMETERS], true),
                    self::ACTION_RETRY => (int) $action[LengowAction::FIELD_RETRY],
                    self::ACTION_FINISH => $action[LengowAction::FIELD_STATE] === LengowAction::STATE_FINISH,
                    self::CREATED_AT => strtotime($action[LengowAction::FIELD_CREATED_AT]),
                    self::UPDATED_AT => $action[LengowAction::FIELD_UPDATED_AT]
                        ? strtotime($action[LengowAction::FIELD_UPDATED_AT])
                        : 0,
                ];
            }
        }
        return $orderActions;
    }

    /**
     * Get array of all the statuses of an order
     *
     * @param MagentoOrderInterface $order Magento order instance
     *
     * @return array
     */
    private function getOrderStatusesData(MagentoOrderInterface $order): array
    {
        $orderStatuses = [];
        $pendingStatusHistoryCreatedAt = $order->getStatusHistoryCollection()->getFirstItem()->getCreatedAt();
        $invoiceCreatedAt = $order->getInvoiceCollection()->getFirstItem()->getCreatedAt();
        $shipmentCreatedAt = $order->getShipmentsCollection()->getFirstItem()->getCreatedAt();
        if ($pendingStatusHistoryCreatedAt) {
            $orderStatuses[] = [
                self::ORDER_MERCHANT_ORDER_STATUS => MagentoOrder::STATE_NEW,
                self::ORDER_STATUS => null,
                self::CREATED_AT => strtotime($pendingStatusHistoryCreatedAt),
            ];
        }
        if ($invoiceCreatedAt) {
            $orderStatuses[] = [
                self::ORDER_MERCHANT_ORDER_STATUS => MagentoOrder::STATE_PROCESSING,
                self::ORDER_STATUS => LengowOrder::STATE_WAITING_SHIPMENT,
                self::CREATED_AT => strtotime($invoiceCreatedAt),
            ];
        }
        if ($shipmentCreatedAt) {
            $orderStatuses[] = [
                self::ORDER_MERCHANT_ORDER_STATUS => MagentoOrder::STATE_COMPLETE,
                self::ORDER_STATUS => LengowOrder::STATE_SHIPPED,
                self::CREATED_AT => strtotime($shipmentCreatedAt),
            ];
        }
        if ($order->getState() === MagentoOrder::STATE_CANCELED) {
            $orderStatuses[] = [
                self::ORDER_MERCHANT_ORDER_STATUS => MagentoOrder::STATE_CANCELED,
                self::ORDER_STATUS => LengowOrder::STATE_CANCELED,
                self::CREATED_AT => strtotime($order->getUpdatedAt()),
            ];
        }
        return $orderStatuses;
    }

    /**
     * Get all the data of the order at the time of import
     *
     * @param array $data All Lengow order data
     *
     * @return array
     */
    private function getOrderExtraData(array $data): array
    {
        return json_decode($data[LengowOrder::FIELD_EXTRA], true);
    }

    /**
     * Get order process label
     *
     * @param integer $orderProcess Lengow order process (new, import or finish)
     *
     * @return string
     */
    private function getOrderProcessLabel(int $orderProcess): string
    {
        switch ($orderProcess) {
            case LengowOrder::PROCESS_STATE_NEW:
                return self::PROCESS_STATE_NEW;
            case LengowOrder::PROCESS_STATE_IMPORT:
                return self::PROCESS_STATE_IMPORT;
            case LengowOrder::PROCESS_STATE_FINISH:
            default:
                return self::PROCESS_STATE_FINISH;
        }
    }

    /**
     * Generates an error return for the Toolbox webservice
     *
     * @param integer $httpCode request http code
     * @param string $error error message
     *
     * @return array
     */
    private function generateErrorReturn(int $httpCode, string $error): array
    {
        return [
            self::ERRORS => [
                self::ERROR_MESSAGE => $this->dataHelper->decodeLogMessage($error, false),
                self::ERROR_CODE => $httpCode,
            ],
        ];
    }
}
