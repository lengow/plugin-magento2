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
 * @subpackage  Model
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Model;

use Exception;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\Store;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Model\Connector as LengowConnector;
use Lengow\Connector\Model\Exception as LengowException;
use Lengow\Connector\Model\Import\Action as LengowAction;
use Lengow\Connector\Model\Import\Importorder as LengowImportOrder;
use Lengow\Connector\Model\Import\ImportorderFactory as LengowImportOrderFactory;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;
use Lengow\Connector\Model\Import\Ordererror as LengowOrderError;
use Lengow\Connector\Model\Import\OrdererrorFactory as LengowOrderErrorFactory;

/**
 * Lengow import
 */
class Import
{
    /* Import GET params */
    public const PARAM_TOKEN = 'token';
    public const PARAM_TYPE = 'type';
    public const PARAM_STORE_ID = 'store_id';
    public const PARAM_MARKETPLACE_SKU = 'marketplace_sku';
    public const PARAM_MARKETPLACE_NAME = 'marketplace_name';
    public const PARAM_DELIVERY_ADDRESS_ID = 'delivery_address_id';
    public const PARAM_DAYS = 'days';
    public const PARAM_MINUTES = 'minutes';
    public const PARAM_CREATED_FROM = 'created_from';
    public const PARAM_CREATED_TO = 'created_to';
    public const PARAM_ORDER_LENGOW_ID = 'order_lengow_id';
    public const PARAM_LIMIT = 'limit';
    public const PARAM_LOG_OUTPUT = 'log_output';
    public const PARAM_DEBUG_MODE = 'debug_mode';
    public const PARAM_FORCE = 'force';
    public const PARAM_FORCE_SYNC = 'force_sync';
    public const PARAM_SYNC = 'sync';
    public const PARAM_GET_SYNC = 'get_sync';

    /* Import API arguments */
    public const ARG_ACCOUNT_ID = 'account_id';
    public const ARG_CATALOG_IDS = 'catalog_ids';
    public const ARG_MARKETPLACE = 'marketplace';
    public const ARG_MARKETPLACE_ORDER_DATE_FROM = 'marketplace_order_date_from';
    public const ARG_MARKETPLACE_ORDER_DATE_TO = 'marketplace_order_date_to';
    public const ARG_MARKETPLACE_ORDER_ID = 'marketplace_order_id';
    public const ARG_MERCHANT_ORDER_ID = 'merchant_order_id';
    public const ARG_NO_CURRENCY_CONVERSION = 'no_currency_conversion';
    public const ARG_PAGE = 'page';
    public const ARG_UPDATED_FROM = 'updated_from';
    public const ARG_UPDATED_TO = 'updated_to';

    /* Import types */
    public const TYPE_MANUAL = 'manual';
    public const TYPE_CRON = 'cron';
    public const TYPE_MAGENTO_CRON = 'magento cron';
    public const TYPE_MAGENTO_CLI = 'magento cli';
    public const TYPE_TOOLBOX = 'toolbox';

    /* Import Data */
    public const NUMBER_ORDERS_PROCESSED = 'number_orders_processed';
    public const NUMBER_ORDERS_CREATED = 'number_orders_created';
    public const NUMBER_ORDERS_UPDATED = 'number_orders_updated';
    public const NUMBER_ORDERS_FAILED = 'number_orders_failed';
    public const NUMBER_ORDERS_IGNORED = 'number_orders_ignored';
    public const NUMBER_ORDERS_NOT_FORMATTED = 'number_orders_not_formatted';
    public const ORDERS_CREATED = 'orders_created';
    public const ORDERS_UPDATED = 'orders_updated';
    public const ORDERS_FAILED = 'orders_failed';
    public const ORDERS_IGNORED = 'orders_ignored';
    public const ORDERS_NOT_FORMATTED = 'orders_not_formatted';
    public const ERRORS = 'errors';

    /**
     * @var integer max interval time for order synchronisation old versions (1 day)
     */
    public const MIN_INTERVAL_TIME = 86400;

    /**
     * @var integer max import days for old versions (10 days)
     */
    public const MAX_INTERVAL_TIME = 864000;

    /**
     * @var integer security interval time for cron synchronisation (2 hours)
     */
    public const SECURITY_INTERVAL_TIME = 7200;

    /**
     * @var integer interval of months for cron synchronisation
     */
    public const MONTH_INTERVAL_TIME = 3;

    /**
     * @var DateTime Magento datetime instance
     */
    private $dateTime;

    /**
     * @var TimezoneInterface Magento datetime timezone instance
     */
    private $timezone;

    /**
     * @var BackendSession Magento Backend session instance
     */
    private $backendSession;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var ImportHelper Lengow config helper instance
     */
    private $importHelper;

    /**
     * @var SyncHelper Lengow sync helper instance
     */
    private $syncHelper;

    /**
     * @var LengowAction Lengow action instance
     */
    private $lengowAction;

    /**
     * @var LengowConnector Lengow connector instance
     */
    private $lengowConnector;

    /**
     * @var LengowOrderFactory Lengow order instance
     */
    private $lengowOrderFactory;

    /**
     * @var LengowOrderErrorFactory Lengow order error factory instance
     */
    private $lengowOrderErrorFactory;

    /**
     * @var LengowImportOrderFactory Lengow import order factory instance
     */
    private $lengowImportOrderFactory;

    /**
     * @var integer Magento store id
     */
    private $storeId;

    /**
     * @var integer amount of products to export
     */
    private $limit;

    /**
     * @var boolean force import order even if there are errors
     */
    private $forceSync;

    /**
     * @var boolean see log or not
     */
    private $logOutput;

    /**
     * @var string import type (manual, cron or magento cron)
     */
    private $typeImport;

    /**
     * @var boolean import one order
     */
    private $importOneOrder = false;

    /**
     * @var boolean use debug mode
     */
    private $debugMode = false;

    /**
     * @var string|null marketplace order sku
     */
    private $marketplaceSku;

    /**
     * @var string|null marketplace name
     */
    private $marketplaceName;

    /**
     * @var integer|null Lengow order id
     */
    private $orderLengowId;

    /**
     * @var integer|null delivery address id
     */
    private $deliveryAddressId;

    /**
     * @var integer|false imports orders updated since (timestamp)
     */
    private $updatedFrom = false;

    /**
     * @var integer|false imports orders updated until (timestamp)
     */
    private $updatedTo = false;

    /**
     * @var integer|false imports orders created since (timestamp)
     */
    private $createdFrom = false;

    /**
     * @var integer|false imports orders created until (timestamp)
     */
    private $createdTo = false;

    /**
     * @var string account ID
     */
    private $accountId;

    /**
     * @var array store catalog ids for import
     */
    private $storeCatalogIds = [];

    /**
     * @var array catalog ids already imported
     */
    private $catalogIds = [];

    /**
     * @var array all orders created during the process
     */
    private $ordersCreated = [];

    /**
     * @var array all orders updated during the process
     */
    private $ordersUpdated = [];

    /**
     * @var array all orders failed during the process
     */
    private $ordersFailed = [];

    /**
     * @var array all orders ignored during the process
     */
    private $ordersIgnored = [];

    /**
     * @var array all incorrectly formatted orders that cannot be processed
     */
    private $ordersNotFormatted = [];

    /**
     * @var array all synchronization error (global or by shop)
     */
    private $errors = [];

    /**
     * Constructor
     *
     * @param DateTime $dateTime Magento datetime instance
     * @param TimezoneInterface $timezone Magento datetime timezone instance
     * @param BackendSession $backendSession Backend session instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param ImportHelper $importHelper Lengow import helper instance
     * @param SyncHelper $syncHelper Lengow sync helper instance
     * @param LengowOrderErrorFactory $lengowOrderErrorFactory Lengow orderError factory instance
     * @param LengowConnector $lengowConnector Lengow connector instance
     * @param LengowImportOrderFactory $lengowImportOrderFactory Lengow import order factory instance
     * @param LengowOrderFactory $lengowOrderFactory Lengow order factory instance
     * @param LengowAction $lengowAction Lengow action instance
     */
    public function __construct(
        DateTime $dateTime,
        TimezoneInterface $timezone,
        BackendSession $backendSession,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        SyncHelper $syncHelper,
        LengowOrderErrorFactory $lengowOrderErrorFactory,
        LengowConnector $lengowConnector,
        LengowImportOrderFactory $lengowImportOrderFactory,
        LengowOrderFactory $lengowOrderFactory,
        LengowAction $lengowAction
    ) {
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->backendSession = $backendSession;
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->importHelper = $importHelper;
        $this->syncHelper = $syncHelper;
        $this->lengowOrderErrorFactory = $lengowOrderErrorFactory;
        $this->lengowConnector = $lengowConnector;
        $this->lengowImportOrderFactory = $lengowImportOrderFactory;
        $this->lengowOrderFactory = $lengowOrderFactory;
        $this->lengowAction = $lengowAction;
    }

    /**
     * Init a new import
     *
     * @param array $params optional options
     * string  marketplace_sku     lengow marketplace order id to import
     * string  marketplace_name    lengow marketplace name to import
     * string  type                type of current import
     * string  created_from        import of orders since
     * string  created_to          import of orders until
     * integer delivery_address_id Lengow delivery address id to import
     * integer order_lengow_id     Lengow order id in Magento
     * integer store_id            store id for current import
     * integer days                import period
     * integer limit               maximum number of new orders created
     * boolean log_output          display log messages
     * boolean debug_mode          debug mode
     * boolean force_sync          force import order even if there are errors
     */
    public function init(array $params): void
    {
        $this->dataHelper->registerShutdownFunction();
        // get generic params for synchronisation
        $this->debugMode = isset($params[self::PARAM_DEBUG_MODE])
            ? (bool) $params[self::PARAM_DEBUG_MODE]
            : $this->configHelper->debugModeIsActive();
        $this->typeImport = $params[self::PARAM_TYPE] ?? self::TYPE_MANUAL;
        $this->forceSync = isset($params[self::PARAM_FORCE_SYNC]) && $params[self::PARAM_FORCE_SYNC];
        $this->logOutput = isset($params[self::PARAM_LOG_OUTPUT]) && $params[self::PARAM_LOG_OUTPUT];
        $this->storeId = isset($params[self::PARAM_STORE_ID]) ? (int) $params[self::PARAM_STORE_ID] : null;
        // get params for synchronise one or all orders
        if (array_key_exists(self::PARAM_MARKETPLACE_SKU, $params)
            && array_key_exists(self::PARAM_MARKETPLACE_NAME, $params)
            && array_key_exists(self::PARAM_STORE_ID, $params)
        ) {
            if (isset($params[self::PARAM_ORDER_LENGOW_ID])) {
                $this->orderLengowId = (int) $params[self::PARAM_ORDER_LENGOW_ID];
                $this->forceSync = true;
            }
            $this->marketplaceSku = (string) $params[self::PARAM_MARKETPLACE_SKU];
            $this->marketplaceName = (string) $params[self::PARAM_MARKETPLACE_NAME];
            $this->importOneOrder = true;
            $this->limit = 1;
            if (array_key_exists(self::PARAM_DELIVERY_ADDRESS_ID, $params)
                && $params[self::PARAM_DELIVERY_ADDRESS_ID] !== ''
            ) {
                $this->deliveryAddressId = (int) $params[self::PARAM_DELIVERY_ADDRESS_ID];
            }
        } else {
            // set the time interval
            if (isset($params[self::PARAM_MINUTES])) {
                $minutes = (float) $params[self::PARAM_MINUTES];
                $this->setIntervalTime(
                    $minutes,
                    null,
                    $params[self::PARAM_CREATED_FROM] ?? null,
                    $params[self::PARAM_CREATED_TO] ?? null
                );
            } else {
                $days = $params[self::PARAM_DAYS] ?? null;
                $this->setIntervalTime(
                    null,
                    $days,
                    $params[self::PARAM_CREATED_FROM] ?? null,
                    $params[self::PARAM_CREATED_TO] ?? null
                );
            }
            $this->limit = isset($params[self::PARAM_LIMIT]) ? (int) $params[self::PARAM_LIMIT] : 0;
        }
        $this->dataHelper->log(
            DataHelper::CODE_IMPORT,
            $this->dataHelper->setLogMessage(
                'Import initialized with params : %1',
                [
                    json_encode($params)
                ]
            ),
            $this->logOutput
        );
    }

    /**
     * Execute import: fetch orders and import them
     *
     * @return array
     */
    public function exec(): array
    {
        $syncOk = true;
        // checks if a synchronization is not already in progress
        if (!$this->canExecuteSynchronization()) {
            return $this->getResult();
        }
        // starts some processes necessary for synchronization
        $this->setupSynchronization();
        // get all active store in Lengow for order synchronization
        $activeStore = $this->configHelper->getLengowActiveStores($this->storeId);
        foreach ($activeStore as $store) {
            // synchronize all orders for a specific store
            if (!$this->synchronizeOrdersByStore($store)) {
                $syncOk = false;
            }
        }
        // get order synchronization result
        $result = $this->getResult();
        $this->dataHelper->log(
            DataHelper::CODE_IMPORT,
            $this->dataHelper->setLogMessage(
                '%1 orders processed, %2 created, %3 updated, %4 failed, %5 ignored and %6 not formatted',
                [
                    $result[self::NUMBER_ORDERS_PROCESSED],
                    $result[self::NUMBER_ORDERS_CREATED],
                    $result[self::NUMBER_ORDERS_UPDATED],
                    $result[self::NUMBER_ORDERS_FAILED],
                    $result[self::NUMBER_ORDERS_IGNORED],
                    $result[self::NUMBER_ORDERS_NOT_FORMATTED],
                ]
            ),
            $this->logOutput
        );
        // update last synchronization date only if importation succeeded
        if (!$this->importOneOrder && $syncOk) {
            $this->importHelper->updateDateImport($this->typeImport);
        }
        // complete synchronization and start all necessary processes
        $this->finishSynchronization();
        return $result;
    }

    /**
     * Set store id for import
     */
    public function setStoreId(int $storeId): self
    {
        $this->storeId = $storeId;

        return $this;
    }

    /**
     * Set days for order synchronisation
     */
    public function setDays(float $days): self
    {
        $this->setIntervalTime($days);

        return $this;
    }

    /**
     * Set debug mode for import
     */
    public function setImportOneOrder(bool $importOneOrder): self
    {
        $this->importOneOrder = $importOneOrder;

        return $this;
    }

    /**
     * Set limit for import
     */
    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Set interval time for order synchronisation
     *
     * @param float|null $minutes Import period in minutes
     * @param float|null $days Import period
     * @param string|null $createdFrom Import of orders since
     * @param string|null $createdTo Import of orders until
     */
    private function setIntervalTime($minutes = null, $days = null, $createdFrom = null, $createdTo = null): void
    {

        if ($createdFrom && $createdTo) {
            // retrieval of orders created from ... until ...
            if ($createdTo < $createdFrom) {
                $createdTo = $createdFrom;
            }
            $createdFromTimestamp = $this->dateTime->gmtTimestamp($createdFrom);
            if ($createdFrom === $createdTo) {
                $createdToTimestamp = $createdFromTimestamp + (self::MIN_INTERVAL_TIME - 1);
            } else {
                $createdToTimestamp = $this->dateTime->gmtTimestamp($createdTo);
            }
            $createdToTimestamp = $this->dateTime->gmtTimestamp($createdTo);
            $intervalTime = $createdToTimestamp - $createdFromTimestamp;
            $this->createdFrom = $createdFromTimestamp;
            $this->createdTo = $intervalTime > self::MAX_INTERVAL_TIME
                ? $createdFromTimestamp + self::MAX_INTERVAL_TIME
                : $createdToTimestamp;
            return;
        }
        if ($minutes) {
            $intervalTime = floor($minutes * 60);
            $intervalTime = $intervalTime > self::MAX_INTERVAL_TIME ? self::MAX_INTERVAL_TIME : $intervalTime;
        }
        elseif ($days) {
            $intervalTime = floor($days * self::MIN_INTERVAL_TIME);
            $intervalTime = $intervalTime > self::MAX_INTERVAL_TIME ? self::MAX_INTERVAL_TIME : $intervalTime;
        } else {
            // order recovery updated since ... days
            $importDays = (float) $this->configHelper->get(ConfigHelper::SYNCHRONIZATION_DAY_INTERVAL);
            $intervalTime = floor($importDays * self::MIN_INTERVAL_TIME);
            // add security for older versions of the plugin
            $intervalTime = $intervalTime < self::MIN_INTERVAL_TIME ? self::MIN_INTERVAL_TIME : $intervalTime;
            $intervalTime = $intervalTime > self::MAX_INTERVAL_TIME ? self::MAX_INTERVAL_TIME : $intervalTime;
            // get dynamic interval time for cron synchronisation
            $lastImport = $this->importHelper->getLastImport();
            $lastSettingUpdate = (int) $this->configHelper->get(ConfigHelper::LAST_UPDATE_SETTING);
            if (($this->typeImport === self::TYPE_CRON || $this->typeImport === self::TYPE_MAGENTO_CRON)
                && $lastImport['timestamp'] !== 'none'
                && $lastImport['timestamp'] > $lastSettingUpdate
            ) {
                $lastIntervalTime = (time() - $lastImport['timestamp']) + self::SECURITY_INTERVAL_TIME;
                $intervalTime = $lastIntervalTime > $intervalTime ? $intervalTime : $lastIntervalTime;
            }
        }
        $this->updatedFrom = time() - $intervalTime;
        $this->updatedTo = time();
    }

    /**
     * Checks if a synchronization is not already in progress
     *
     * @return boolean
     */
    private function canExecuteSynchronization(): bool
    {
        $globalError = false;
        // checks if the process can start
        if (!$this->debugMode && !$this->importOneOrder && $this->importHelper->isInProcess()) {
            $globalError = $this->dataHelper->setLogMessage(
                'Import has already started. Please wait %1 seconds before re-importing orders',
                [$this->importHelper->restTimeToImport()]
            );
            $this->dataHelper->log(DataHelper::CODE_IMPORT, $globalError, $this->logOutput);
        } elseif (!$this->checkCredentials()) {
            $globalError = $this->dataHelper->setLogMessage('Account ID, token access or secret token are not valid');
            $this->dataHelper->log(DataHelper::CODE_IMPORT, $globalError, $this->logOutput);
        }
        // if we have a global error, we stop the process directly
        if ($globalError) {
            $this->errors[0] = $globalError;
            if (isset($this->orderLengowId) && $this->orderLengowId) {
                $this->lengowOrderErrorFactory->create()->finishOrderErrors($this->orderLengowId);
                $this->lengowOrderErrorFactory->create()->createOrderError(
                    [
                        LengowOrderError::FIELD_ORDER_LENGOW_ID => $this->orderLengowId,
                        LengowOrderError::FIELD_MESSAGE => $globalError,
                        LengowOrderError::FIELD_TYPE => LengowOrderError::TYPE_ERROR_IMPORT,
                    ]
                );
            }
            return false;
        }
        return true;
    }

    /**
     * Starts some processes necessary for synchronization
     */
    private function setupSynchronization(): void
    {
        // suppress log files when too old
        $this->dataHelper->cleanLog();
        if (!$this->importOneOrder) {
            $this->importHelper->setImportInProcess();
        }
        // to activate lengow shipping method
        $this->backendSession->setIsFromlengow(1);
        // check Lengow catalogs for order synchronisation
        if (!$this->importOneOrder && $this->typeImport === self::TYPE_MANUAL) {
            $this->syncHelper->syncCatalog();
        }
        $this->dataHelper->log(
            DataHelper::CODE_IMPORT,
            $this->dataHelper->setLogMessage('## start %1 import ##', [$this->typeImport]),
            $this->logOutput
        );
        if ($this->debugMode) {
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage('WARNING! Debug Mode is activated'),
                $this->logOutput
            );
        }
    }

    /**
     * Check credentials and get Lengow connector
     *
     * @return boolean
     */
    private function checkCredentials(): bool
    {
        if ($this->lengowConnector->isValidAuth($this->logOutput)) {
            list($this->accountId, $accessToken, $secret) = $this->configHelper->getAccessIds();
            $this->lengowConnector->init(['access_token' => $accessToken, 'secret' => $secret]);
            return true;
        }
        return false;
    }

    /**
     * Return the synchronization result
     *
     * @return array
     */
    private function getResult(): array
    {
        $nbOrdersCreated = count($this->ordersCreated);
        $nbOrdersUpdated = count($this->ordersUpdated);
        $nbOrdersFailed = count($this->ordersFailed);
        $nbOrdersIgnored = count($this->ordersIgnored);
        $nbOrdersNotFormatted = count($this->ordersNotFormatted);
        $nbOrdersProcessed = $nbOrdersCreated
            + $nbOrdersUpdated
            + $nbOrdersFailed
            + $nbOrdersIgnored
            + $nbOrdersNotFormatted;
        return [
            self::NUMBER_ORDERS_PROCESSED => $nbOrdersProcessed,
            self::NUMBER_ORDERS_CREATED => $nbOrdersCreated,
            self::NUMBER_ORDERS_UPDATED => $nbOrdersUpdated,
            self::NUMBER_ORDERS_FAILED => $nbOrdersFailed,
            self::NUMBER_ORDERS_IGNORED => $nbOrdersIgnored,
            self::NUMBER_ORDERS_NOT_FORMATTED => $nbOrdersNotFormatted,
            self::ORDERS_CREATED => $this->ordersCreated,
            self::ORDERS_UPDATED => $this->ordersUpdated,
            self::ORDERS_FAILED => $this->ordersFailed,
            self::ORDERS_IGNORED => $this->ordersIgnored,
            self::ORDERS_NOT_FORMATTED => $this->ordersNotFormatted,
            self::ERRORS => $this->errors,
        ];
    }

    /**
     * Synchronize all orders for a specific store
     *
     * @param Store $store Magento store instance
     *
     * @return boolean
     */
    private function synchronizeOrdersByStore(Store $store): bool
    {
        $this->dataHelper->log(
            DataHelper::CODE_IMPORT,
            $this->dataHelper->setLogMessage('start import in store %1 (%2)', [$store->getName(), $store->getId()]),
            $this->logOutput
        );
        // check shop catalog ids
        if (!$this->checkCatalogIds($store)) {
            return true;
        }
        try {
            // get orders from Lengow API
            $orders = $this->getOrdersFromApi($store, $numberOrdersFound);
            // current() will trigger the first api call & populate $numberOrdersFound
            // leaving the cursor at the first element
            $orders->current();
            if ($this->importOneOrder) {
                $this->dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->dataHelper->setLogMessage(
                        '%1 order found for order ID: %2 and marketplace: %3 with account ID: %4',
                        [
                            $numberOrdersFound,
                            $this->marketplaceSku,
                            $this->marketplaceName,
                            $this->accountId,
                        ]
                    ),
                    $this->logOutput
                );
            } else {
                $this->dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->dataHelper->setLogMessage(
                        '%1 order(s) found with account ID: %2',
                        [
                            $numberOrdersFound,
                            $this->accountId,
                        ]
                    ),
                    $this->logOutput
                );
            }
            if ($numberOrdersFound <= 0 && $this->importOneOrder) {
                throw new LengowException('Lengow error: order cannot be found in Lengow feed');
            }
            if ($numberOrdersFound > 0) {
                // import orders in Magento
                $this->importOrders($orders, (int) $store->getId());
            }
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Magento error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            if (isset($this->orderLengowId) && $this->orderLengowId) {
                $this->lengowOrderErrorFactory->create()->finishOrderErrors($this->orderLengowId);
                $this->lengowOrderErrorFactory->create()->createOrderError(
                    [
                        LengowOrderError::FIELD_ORDER_LENGOW_ID => $this->orderLengowId,
                        LengowOrderError::FIELD_MESSAGE => $errorMessage,
                        LengowOrderError::FIELD_TYPE => LengowOrderError::TYPE_ERROR_IMPORT,
                    ]
                );
            }
            $decodedMessage = $this->dataHelper->decodeLogMessage($errorMessage, false);
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage('import failed - %1', [$decodedMessage]),
                $this->logOutput
            );
            $this->errors[(int) $store->getId()] = $errorMessage;
            unset($errorMessage);
            return false;
        }
        return true;
    }

    /**
     * Check catalog ids for a store
     *
     * @param Store $store Magento store instance
     *
     * @return boolean
     */
    private function checkCatalogIds(Store $store): bool
    {
        if ($this->importOneOrder) {
            return true;
        }
        $storeCatalogIds = [];
        $catalogIds = $this->configHelper->getCatalogIds((int) $store->getId());
        foreach ($catalogIds as $catalogId) {
            if (array_key_exists($catalogId, $this->catalogIds)) {
                $this->dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->dataHelper->setLogMessage(
                        'catalog ID %1 is already used by shop %2 (%3)',
                        [
                            $catalogId,
                            $this->catalogIds[$catalogId]['name'],
                            $this->catalogIds[$catalogId]['store_id'],
                        ]
                    ),
                    $this->logOutput
                );
            } else {
                $this->catalogIds[$catalogId] = ['store_id' => (int) $store->getId(), 'name' => $store->getName()];
                $storeCatalogIds[] = $catalogId;
            }
        }
        if (!empty($storeCatalogIds)) {
            $this->storeCatalogIds = $storeCatalogIds;
            return true;
        }
        $message = $this->dataHelper->setLogMessage(
            'No catalog ID valid for the store %1 (%2)',
            [$store->getName(), $store->getId()]
        );
        $this->dataHelper->log(DataHelper::CODE_IMPORT, $message, $this->logOutput);
        $this->errors[(int) $store->getId()] = $message;
        return false;
    }

    /**
     * Call Lengow order API
     *
     * @param Store $store Magento store instance
     * @param int|null $count ref . that will be set on the first iteration.
     *  You can populate the count before iterating by calling $generator->current();
     *
     * @return \Generator<array>
     *
     * @throws Exception
     */
    private function getOrdersFromApi(Store $store, ?int &$count = null): \Generator
    {
        $page = 1;
        $orders = [];
        // convert order amount or not
        $noCurrencyConversion = !(bool) $this->configHelper->get(
            ConfigHelper::CURRENCY_CONVERSION_ENABLED,
            (int) $store->getId()
        );
        if ($this->importOneOrder) {
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage(
                    'get order with order ID: %1 and marketplace: %2',
                    [
                        $this->marketplaceSku,
                        $this->marketplaceName,
                    ]
                ),
                $this->logOutput
            );
        } else {
            $dateFrom = $this->createdFrom
                ? $this->dateTime->gmtDate(DataHelper::DATE_FULL, $this->createdFrom)
                : $this->timezone->date($this->updatedFrom)->format(DataHelper::DATE_FULL);
            $dateTo = $this->createdTo
                ? $this->dateTime->gmtDate(DataHelper::DATE_FULL, $this->createdTo)
                : $this->timezone->date($this->updatedTo)->format(DataHelper::DATE_FULL);
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage(
                    'get orders between %1 and %2 for catalogs ID: %3',
                    [
                        $dateFrom,
                        $dateTo,
                        implode(', ', $this->storeCatalogIds),
                    ]
                ),
                $this->logOutput
            );
        }
        do {
            try {
                if ($this->importOneOrder) {
                    $results = $this->lengowConnector->get(
                        Connector::API_ORDER,
                        [
                            self::ARG_MARKETPLACE_ORDER_ID => $this->marketplaceSku,
                            self::ARG_MARKETPLACE => $this->marketplaceName,
                            self::ARG_NO_CURRENCY_CONVERSION => $noCurrencyConversion,
                            self::ARG_ACCOUNT_ID => $this->accountId,
                            self::ARG_PAGE => $page,
                        ],
                        Connector::FORMAT_STREAM,
                        '',
                        $this->logOutput
                    );
                } else {
                    if ($this->createdFrom && $this->createdTo) {
                        $timeParams = [
                            self::ARG_MARKETPLACE_ORDER_DATE_FROM => $this->dateTime->gmtDate(
                                DataHelper::DATE_ISO_8601,
                                $this->createdFrom
                            ),
                            self::ARG_MARKETPLACE_ORDER_DATE_TO => $this->dateTime->gmtDate(
                                DataHelper::DATE_ISO_8601,
                                $this->createdTo
                            ),
                        ];
                    } else {
                        $timeParams = [
                            self::ARG_UPDATED_FROM => $this->timezone->date($this->updatedFrom)
                                ->format(DataHelper::DATE_ISO_8601),
                            self::ARG_UPDATED_TO => $this->timezone->date($this->updatedTo)
                                ->format(DataHelper::DATE_ISO_8601),
                        ];
                    }
                    $filterParams = [
                        self::ARG_CATALOG_IDS => implode(',', $this->storeCatalogIds),
                        self::ARG_NO_CURRENCY_CONVERSION => $noCurrencyConversion,
                        self::ARG_ACCOUNT_ID => $this->accountId,
                        self::ARG_PAGE => $page,
                    ];
                    if (!empty($this->marketplaceName)) {
                        $filterParams[self::ARG_MARKETPLACE] = $this->marketplaceName;
                    }
                    $results = $this->lengowConnector->get(
                        Connector::API_ORDER,
                        array_merge(
                            $timeParams,
                            $filterParams
                        ),
                        Connector::FORMAT_STREAM,
                        '',
                        $this->logOutput
                    );
                }
            } catch (Exception $e) {
                throw new LengowException(
                    $this->dataHelper->setLogMessage(
                        'Lengow webservice : %1 - "%2" in store %3 (%4)',
                        [
                            $e->getCode(),
                            $this->dataHelper->decodeLogMessage($e->getMessage(), false),
                            $store->getName(),
                            $store->getId(),
                        ]
                    )
                );
            }
            if ($results === null) {
                throw new LengowException(
                    $this->dataHelper->setLogMessage(
                        "connection didn't work with Lengow's webservice in store %1 (%2)",
                        [$store->getName(), $store->getId()]
                    )
                );
            }
            $results = json_decode($results);
            if (!is_object($results)) {
                throw new LengowException(
                    $this->dataHelper->setLogMessage(
                        "connection didn't work with Lengow's webservice in store %1 (%2)",
                        [$store->getName(), $store->getId()]
                    )
                );
            }

            if (null === $count) {
                $count = $results->count;
            }

            // construct array orders
            foreach ($results->results as $order) {
                yield $order;
            }

            $page++;
            $finish = $results->next === null || $this->importOneOrder;
        } while ($finish !== true);
    }

    /**
     * Create or update order in Magento
     *
     * @param mixed $orders API orders
     * @param integer $storeId Magento store Id
     */
    private function importOrders($orders, int $storeId): void
    {
        $importFinished = false;
        foreach ($orders as $orderData) {
            if (!$this->importOneOrder) {
                $this->importHelper->setImportInProcess();
            }
            $nbPackage = 0;
            $marketplaceSku = (string) $orderData->marketplace_order_id;
            if ($this->debugMode) {
                $marketplaceSku .= '--' . time();
            }
            // set current order to cancel hook updateOrderStatus
            $this->backendSession->setCurrentOrderLengow($marketplaceSku);
            // set the current order data for plugins and observers
            $this->backendSession->setCurrentOrderLengowData($orderData);
            // if order contains no package
            if (empty($orderData->packages)) {
                $message = $this->dataHelper->setLogMessage(
                    'import order failed - Lengow error: no package in the order'
                );
                $this->dataHelper->log(DataHelper::CODE_IMPORT, $message, $this->logOutput, $marketplaceSku);
                $this->addOrderNotFormatted($marketplaceSku, $message, $orderData);
                continue;
            }
            // start import
            foreach ($orderData->packages as $packageData) {
                $nbPackage++;
                // check whether the package contains a shipping address
                if (!isset($packageData->delivery->id)) {
                    $message = $this->dataHelper->setLogMessage(
                        'import order failed - Lengow error: no delivery address in the order'
                    );
                    $this->dataHelper->log(DataHelper::CODE_IMPORT, $message, $this->logOutput, $marketplaceSku);
                    $this->addOrderNotFormatted($marketplaceSku, $message, $orderData);
                    continue;
                }
                $packageDeliveryAddressId = (int) $packageData->delivery->id;
                $firstPackage = !($nbPackage > 1);
                // check the package for re-import order
                if ($this->importOneOrder
                    && $this->deliveryAddressId !== null
                    && $this->deliveryAddressId !== $packageDeliveryAddressId
                ) {
                    $message = $this->dataHelper->setLogMessage('import order failed - wrong package number');
                    $this->dataHelper->log(DataHelper::CODE_IMPORT, $message, $this->logOutput, $marketplaceSku);
                    $this->addOrderNotFormatted($marketplaceSku, $message, $orderData);
                    continue;
                }
                try {
                    // try to import or update order
                    $importOrderFactory = $this->lengowImportOrderFactory->create();
                    $importOrderFactory->init(
                        [
                            LengowImportOrder::PARAM_STORE_ID => $storeId,
                            LengowImportOrder::PARAM_FORCE_SYNC => $this->forceSync,
                            LengowImportOrder::PARAM_DEBUG_MODE => $this->debugMode,
                            LengowImportOrder::PARAM_LOG_OUTPUT => $this->logOutput,
                            LengowImportOrder::PARAM_MARKETPLACE_SKU => $marketplaceSku,
                            LengowImportOrder::PARAM_DELIVERY_ADDRESS_ID => $packageDeliveryAddressId,
                            LengowImportOrder::PARAM_ORDER_DATA => $orderData,
                            LengowImportOrder::PARAM_PACKAGE_DATA => $packageData,
                            LengowImportOrder::PARAM_FIRST_PACKAGE => $firstPackage,
                            LengowImportOrder::PARAM_IMPORT_ONE_ORDER => $this->importOneOrder,
                        ]
                    );
                    $result = $importOrderFactory->importOrder();
                    // synchronize the merchant order id with Lengow
                    $this->synchronizeMerchantOrderId($result);
                    // save the result of the order synchronization by type
                    $this->saveSynchronizationResult($result);
                    // clean import order process
                    unset($importOrderFactory, $result);
                } catch (Exception $e) {
                    $errorMessage = '[Magento error]: "' . $e->getMessage()
                        . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
                }
                if (isset($errorMessage)) {
                    $decodedMessage = $this->dataHelper->decodeLogMessage($errorMessage, false);
                    $this->dataHelper->log(
                        DataHelper::CODE_IMPORT,
                        $this->dataHelper->setLogMessage('import order failed - %1', [$decodedMessage]),
                        $this->logOutput,
                        $marketplaceSku
                    );
                    unset($errorMessage);
                    continue;
                }
                // if limit is set
                if ($this->limit > 0 && count($this->ordersCreated) === $this->limit) {
                    $importFinished = true;
                    break;
                }
            }
            // clean current order in session
            $this->backendSession->setCurrentOrderLengow(false);
            $this->backendSession->setCurrentOrderLengowData([]);
            $this->backendSession->setCurrentOrderLengowProducts([]);
            $this->backendSession->setBundleItems([]);
            $this->backendSession->setHasBundleItems(false);
            // reset backend session b2b attribute
            $this->backendSession->setIsLengowB2b(0);
            if ($importFinished) {
                break;
            }
        }
    }

    /**
     * Return an array of result for order not formatted
     *
     * @param string $marketplaceSku id lengow of current order
     * @param string $errorMessage Error message
     * @param mixed $orderData API order data
     */
    private function addOrderNotFormatted(string $marketplaceSku, string $errorMessage, $orderData): void
    {
        $messageDecoded = $this->dataHelper->decodeLogMessage($errorMessage, false);
        $this->ordersNotFormatted[] = [
            LengowImportOrder::MERCHANT_ORDER_ID => null,
            LengowImportOrder::MERCHANT_ORDER_REFERENCE => null,
            LengowImportOrder::LENGOW_ORDER_ID => $this->orderLengowId,
            LengowImportOrder::MARKETPLACE_SKU => $marketplaceSku,
            LengowImportOrder::MARKETPLACE_NAME => (string) $orderData->marketplace,
            LengowImportOrder::DELIVERY_ADDRESS_ID => null,
            LengowImportOrder::SHOP_ID => $this->storeId,
            LengowImportOrder::CURRENT_ORDER_STATUS => (string) $orderData->lengow_status,
            LengowImportOrder::PREVIOUS_ORDER_STATUS => (string) $orderData->lengow_status,
            LengowImportOrder::ERRORS => [$messageDecoded],
        ];
    }

    /**
     * Synchronize the merchant order id with Lengow
     *
     * @param array $result synchronization order result
     */
    private function synchronizeMerchantOrderId(array $result): void
    {
        if (!$this->debugMode && $result[LengowImportOrder::RESULT_TYPE] === LengowImportOrder::RESULT_CREATED) {
            $lengowOrder = $this->lengowOrderFactory->create()->load($result[LengowImportOrder::LENGOW_ORDER_ID]);
            $success = $this->lengowOrderFactory->create()->synchronizeOrder(
                $lengowOrder,
                $this->lengowConnector,
                $this->logOutput
            );
            $messageKey = $success
                ? 'order successfully synchronized with Lengow webservice (ORDER ID %1)'
                : 'WARNING! Order could NOT be synchronized with Lengow webservice (ORDER ID %1)';
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage($messageKey, [$result[LengowImportOrder::MERCHANT_ORDER_ID]]),
                $this->logOutput,
                $result[LengowImportOrder::MARKETPLACE_SKU]
            );
        }
    }

    /**
     * Save the result of the order synchronization by type
     *
     * @param array $result synchronization order result
     */
    private function saveSynchronizationResult(array $result): void
    {
        $resultType = $result[LengowImportOrder::RESULT_TYPE];
        unset($result[LengowImportOrder::RESULT_TYPE]);
        switch ($resultType) {
            case LengowImportOrder::RESULT_CREATED:
                $this->ordersCreated[] = $result;
                break;
            case LengowImportOrder::RESULT_UPDATED:
                $this->ordersUpdated[] = $result;
                break;
            case LengowImportOrder::RESULT_FAILED:
                $this->ordersFailed[] = $result;
                break;
            case LengowImportOrder::RESULT_IGNORED:
                $this->ordersIgnored[] = $result;
                break;
        }
    }

    /**
     * Complete synchronization and start all necessary processes
     */
    private function finishSynchronization(): void
    {
        // finish synchronization process
        $this->importHelper->setImportEnd();
        $this->dataHelper->log(
            DataHelper::CODE_IMPORT,
            $this->dataHelper->setLogMessage('## end %1 import ##', [$this->typeImport]),
            $this->logOutput
        );
        // check if order action is finish (ship or cancel)
        if (!$this->debugMode && !$this->importOneOrder && $this->typeImport === self::TYPE_MANUAL) {
            $this->lengowAction->checkFinishAction($this->logOutput);
            $this->lengowAction->checkOldAction($this->logOutput);
            $this->lengowAction->checkActionNotSent($this->logOutput);
        }
        // sending email in error for orders (import and send errors)
        if (!$this->debugMode
            && !$this->importOneOrder
            && $this->configHelper->get(ConfigHelper::REPORT_MAIL_ENABLED)
        ) {
            $this->importHelper->sendMailAlert($this->logOutput);
        }
        // clear session
        $this->backendSession->setIsFromlengow(0);
    }
}
