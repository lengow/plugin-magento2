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

use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Store\Model\WebsiteFactory;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Store\Api\StoreRepositoryInterface;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Model\Import\OrdererrorFactory;
use Lengow\Connector\Model\Exception as LengowException;
use Lengow\Connector\Model\Import\Action;
use Lengow\Connector\Model\Import\ImportorderFactory;
use Lengow\Connector\Model\Import\OrderFactory;

/**
 * Lengow import
 */
class Import
{
    /**
     * @var integer max interval time for order synchronisation old versions (1 day)
     */
    const MIN_INTERVAL_TIME = 86400;

    /**
     * @var integer max import days for old versions (10 days)
     */
    const MAX_INTERVAL_TIME = 864000;

    /**
     * @var integer security interval time for cron synchronisation (2 hours)
     */
    const SECURITY_INTERVAL_TIME = 7200;

    /**
     * @var string manual import type
     */
    const TYPE_MANUAL = 'manual';

    /**
     * @var string cron import type
     */
    const TYPE_CRON = 'cron';

    /**
     * @var string Magento cron import type
     */
    const TYPE_MAGENTO_CRON = 'magento cron';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface Magento store manager instance
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface Magento datetime timezone instance
     */
    protected $_timezone;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface Magento scope config instance
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Json\Helper\Data Magento json helper instance
     */
    protected $_jsonHelper;

    /**
     * @var \Magento\Store\Model\WebsiteFactory Magento website factory instance
     */
    protected $_websiteFactory;

    /**
     * @var \Magento\Backend\Model\Session $_backendSession Backend session instance
     */
    protected $_backendSession;

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    protected $_storeRepository;

    /**
     * @var \Lengow\Connector\Model\Import\OrderFactory Lengow order instance
     */
    protected $_lengowOrderFactory;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Helper\Import Lengow config helper instance
     */
    protected $_importHelper;

    /**
     * @var \Lengow\Connector\Helper\Sync Lengow sync helper instance
     */
    protected $_syncHelper;

    /**
     * @var \Lengow\Connector\Model\Connector Lengow connector instance
     */
    protected $_connector;

    /**
     * @var \Lengow\Connector\Model\Import\OrdererrorFactory Lengow order error instance
     */
    protected $_orderErrorFactory;

    /**
     * @var \Lengow\Connector\Model\Import\ImportorderFactory Lengow import order factory instance
     */
    protected $_importorderFactory;

    /**
     * @var \Lengow\Connector\Model\Import\Action Lengow action instance
     */
    protected $_action;

    /**
     * @var integer Magento store id
     */
    protected $_storeId;

    /**
     * @var integer amount of products to export
     */
    protected $_limit;

    /**
     * @var boolean export Lengow selection
     */
    protected $_selection;

    /**
     * @var boolean see log or not
     */
    protected $_logOutput;

    /**
     * @var string import type (manual, cron or magento cron)
     */
    protected $_typeImport;

    /**
     * @var boolean import one order
     */
    protected $_importOneOrder = false;

    /**
     * @var boolean use preprod mode
     */
    protected $_preprodMode = false;

    /**
     * @var string|null marketplace order sku
     */
    protected $_marketplaceSku = null;

    /**
     * @var string|null marketplace name
     */
    protected $_marketplaceName = null;

    /**
     * @var integer|null Lengow order id
     */
    protected $_orderLengowId = null;

    /**
     * @var integer|null delivery address id
     */
    protected $_deliveryAddressId = null;

    /**
     * @var integer|false imports orders updated since (timestamp)
     */
    protected $_updatedFrom = false;

    /**
     * @var integer|false imports orders updated until (timestamp)
     */
    protected $_updatedTo = false;

    /**
     * @var integer|false imports orders created since (timestamp)
     */
    protected $_createdFrom = false;

    /**
     * @var integer|false imports orders created until (timestamp)
     */
    protected $_createdTo = false;

    /**
     * @var string account ID
     */
    protected $_accountId;

    /**
     * @var string access token
     */
    protected $_accessToken;

    /**
     * @var string secret token
     */
    protected $_secretToken;

    /**
     * @var array store catalog ids for import
     */
    protected $_storeCatalogIds = [];

    /**
     * @var array catalog ids already imported
     */
    protected $_catalogIds = [];

    /**
     * Constructor
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Magento store manager instance
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime Magento datetime instance
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone Magento datetime timezone instance
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig Magento scope config instance
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper Magento json helper instance
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory Magento website factory instance
     * @param \Magento\Backend\Model\Session $backendSession Backend session instance
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository Magento store repository instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Helper\Import $importHelper Lengow config helper instance
     * @param \Lengow\Connector\Helper\Sync $syncHelper Lengow sync helper instance
     * @param \Lengow\Connector\Model\Import\OrdererrorFactory $orderErrorFactory Lengow orderError instance
     * @param \Lengow\Connector\Model\Connector $connector Lengow connector instance
     * @param \Lengow\Connector\Model\Import\ImportorderFactory $importorderFactory Lengow importorder instance
     * @param \Lengow\Connector\Model\Import\OrderFactory $lengowOrderFactory Lengow order instance
     * @param \Lengow\Connector\Model\Import\Action $action Lengow action instance
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DateTime $dateTime,
        TimezoneInterface $timezone,
        ScopeConfigInterface $scopeConfig,
        JsonHelper $jsonHelper,
        WebsiteFactory $websiteFactory,
        BackendSession $backendSession,
        StoreRepositoryInterface $storeRepository,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        SyncHelper $syncHelper,
        OrdererrorFactory $orderErrorFactory,
        Connector $connector,
        ImportorderFactory $importorderFactory,
        OrderFactory $lengowOrderFactory,
        Action $action
    )
    {
        $this->_storeManager = $storeManager;
        $this->_dateTime = $dateTime;
        $this->_timezone = $timezone;
        $this->_scopeConfig = $scopeConfig;
        $this->_jsonHelper = $jsonHelper;
        $this->_websiteFactory = $websiteFactory;
        $this->_backendSession = $backendSession;
        $this->_storeRepository = $storeRepository;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_importHelper = $importHelper;
        $this->_syncHelper = $syncHelper;
        $this->_orderErrorFactory = $orderErrorFactory;
        $this->_connector = $connector;
        $this->_importorderFactory = $importorderFactory;
        $this->_lengowOrderFactory = $lengowOrderFactory;
        $this->_action = $action;
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
     * integer limit               number of orders to import
     * boolean log_output          display log messages
     * boolean preprod_mode        preprod mode
     */
    public function init($params)
    {
        // get generic params for synchronisation
        $this->_preprodMode = isset($params['preprod_mode'])
            ? (bool)$params['preprod_mode']
            : (bool)$this->_configHelper->get('preprod_mode_enable');
        $this->_typeImport = isset($params['type']) ? $params['type'] : self::TYPE_MANUAL;
        $this->_logOutput = isset($params['log_output']) ? (bool)$params['log_output'] : false;
        $this->_storeId = isset($params['store_id']) ? (int)$params['store_id'] : null;
        // get params for synchronise one or all orders
        if (array_key_exists('marketplace_sku', $params)
            && array_key_exists('marketplace_name', $params)
            && array_key_exists('store_id', $params)
        ) {
            if (isset($params['order_lengow_id'])) {
                $this->_orderLengowId = (int)$params['order_lengow_id'];
            }
            $this->_importOneOrder = true;
            $this->_limit = 1;
            $this->_marketplaceSku = (string)$params['marketplace_sku'];
            $this->_marketplaceName = (string)$params['marketplace_name'];
            if (array_key_exists('delivery_address_id', $params) && $params['delivery_address_id'] !== '') {
                $this->_deliveryAddressId = (int)$params['delivery_address_id'];
            }
        } else {
            // set the time interval
            $this->_setIntervalTime(
                isset($params['days']) ? (int)$params['days'] : false,
                isset($params['created_from']) ? $params['created_from'] : false,
                isset($params['created_to']) ? $params['created_to'] : false
            );
            $this->_limit = isset($params['limit']) ? (int)$params['limit'] : 0;
        }
    }

    /**
     * Execute import: fetch orders and import them
     *
     * @return array
     */
    public function exec()
    {
        $orderNew = 0;
        $orderUpdate = 0;
        $orderError = 0;
        $errors = [];
        $globalError = false;
        $syncOk = true;
        // clean logs > 20 days
        $this->_dataHelper->cleanLog();
        if ($this->_importHelper->importIsInProcess() && !$this->_preprodMode && !$this->_importOneOrder) {
            $globalError = $this->_dataHelper->setLogMessage(
                'Import has already started. Please wait %1 seconds before re-importing orders',
                [$this->_importHelper->restTimeToImport()]
            );
            $this->_dataHelper->log(DataHelper::CODE_IMPORT, $globalError, $this->_logOutput);
        } elseif (!$this->_checkCredentials()) {
            $globalError = $this->_dataHelper->setLogMessage('Account ID, token access or secret token are not valid');
            $this->_dataHelper->log(DataHelper::CODE_IMPORT, $globalError, $this->_logOutput);
        } else {
            if (!$this->_importOneOrder) {
                $this->_importHelper->setImportInProcess();
            }
            // to activate lengow shipping method
            $this->_backendSession->setIsFromlengow(1);
            // check Lengow catalogs for order synchronisation
            if (!$this->_importOneOrder && $this->_typeImport === self::TYPE_MANUAL) {
                $this->_syncHelper->syncCatalog();
            }
            $this->_dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->_dataHelper->setLogMessage('## start %1 import ##', [$this->_typeImport]),
                $this->_logOutput
            );
            if ($this->_preprodMode) {
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->_dataHelper->setLogMessage('WARNING! Pre-production mode is activated'),
                    $this->_logOutput
                );
            }
            // get all store for import
            $storeCollection = $this->_storeManager->getStores();
            /** @var Store $store */
            foreach ($storeCollection as $store) {
                if ((!is_null($this->_storeId) && (int)$store->getId() !== $this->_storeId) || !$store->isActive()) {
                    continue;
                }
                if ($this->_configHelper->get('store_enable', (int)$store->getId())) {
                    $this->_dataHelper->log(
                        DataHelper::CODE_IMPORT,
                        $this->_dataHelper->setLogMessage(
                            'start import in store %1 (%2)',
                            [
                                $store->getName(),
                                (int)$store->getId(),
                            ]
                        ),
                        $this->_logOutput
                    );
                    try {
                        // check store catalog ids
                        if (!$this->_checkCatalogIds($store)) {
                            $errorCatalogIds = $this->_dataHelper->setLogMessage(
                                'No catalog ID valid for the store %1 (%2)',
                                [$store->getName(), (int)$store->getId()]
                            );
                            $this->_dataHelper->log(DataHelper::CODE_IMPORT, $errorCatalogIds, $this->_logOutput);
                            $errors[(int)$store->getId()] = $errorCatalogIds;
                            continue;
                        }
                        // get orders from Lengow API
                        $orders = $this->_getOrdersFromApi($store);
                        $totalOrders = count($orders);
                        if ($this->_importOneOrder) {
                            $this->_dataHelper->log(
                                DataHelper::CODE_IMPORT,
                                $this->_dataHelper->setLogMessage(
                                    '%1 order found for order ID: %2 and marketplace: %3 with account ID: %4',
                                    [
                                        $totalOrders,
                                        $this->_marketplaceSku,
                                        $this->_marketplaceName,
                                        $this->_accountId,
                                    ]
                                ),
                                $this->_logOutput
                            );
                        } else {
                            $this->_dataHelper->log(
                                DataHelper::CODE_IMPORT,
                                $this->_dataHelper->setLogMessage(
                                    '%1 order(s) found with account ID: %2',
                                    [
                                        $totalOrders,
                                        $this->_accountId,
                                    ]
                                ),
                                $this->_logOutput
                            );
                        }
                        if ($totalOrders <= 0 && $this->_importOneOrder) {
                            throw new Exception('Lengow error: order cannot be found in Lengow feed');
                        } elseif ($totalOrders <= 0) {
                            continue;
                        }
                        if (!is_null($this->_orderLengowId)) {
                            $this->_orderErrorFactory->create()->finishOrderErrors($this->_orderLengowId);
                        }
                        // import orders in Magento
                        $result = $this->_importOrders($orders, (int)$store->getId());
                        if (!$this->_importOneOrder) {
                            $orderNew += $result['order_new'];
                            $orderUpdate += $result['order_update'];
                            $orderError += $result['order_error'];
                        }
                    } catch (LengowException $e) {
                        $errorMessage = $e->getMessage();
                    } catch (\Exception $e) {
                        $errorMessage = 'Magento error: "' . $e->getMessage()
                            . '" ' . $e->getFile() . ' line ' . $e->getLine();
                    }
                    if (isset($errorMessage)) {
                        $syncOk = false;
                        if (!is_null($this->_orderLengowId)) {
                            $this->_orderErrorFactory->create()->finishOrderErrors($this->_orderLengowId);
                            $this->_orderErrorFactory->create()->createOrderError(
                                [
                                    'order_lengow_id' => $this->_orderLengowId,
                                    'message' => $errorMessage,
                                    'type' => 'import',
                                ]
                            );
                        }
                        $decodedMessage = $this->_dataHelper->decodeLogMessage($errorMessage, false);
                        $this->_dataHelper->log(
                            DataHelper::CODE_IMPORT,
                            $this->_dataHelper->setLogMessage('import failed - %1', [$decodedMessage]),
                            $this->_logOutput
                        );
                        $errors[(int)$store->getId()] = $errorMessage;
                        unset($errorMessage);
                        continue;
                    }
                }
                unset($store);
            }
            if (!$this->_importOneOrder) {
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->_dataHelper->setLogMessage('%1 order(s) imported', [$orderNew]),
                    $this->_logOutput
                );
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->_dataHelper->setLogMessage('%1 order(s) updated', [$orderUpdate]),
                    $this->_logOutput
                );
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->_dataHelper->setLogMessage('%1 order(s) with errors', [$orderError]),
                    $this->_logOutput
                );
            }
            // update last import date
            if (!$this->_importOneOrder && $syncOk) {
                $this->_importHelper->updateDateImport($this->_typeImport);
            }
            // finish import process
            $this->_importHelper->setImportEnd();
            $this->_dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->_dataHelper->setLogMessage('## end %1 import ##', [$this->_typeImport]),
                $this->_logOutput
            );
            // sending email in error for orders
            if ($this->_configHelper->get('report_mail_enable') && !$this->_preprodMode && !$this->_importOneOrder) {
                $this->_importHelper->sendMailAlert($this->_logOutput);
            }
            // checking marketplace actions
            if (!$this->_preprodMode && !$this->_importOneOrder && $this->_typeImport === self::TYPE_MANUAL) {
                $this->_action->checkFinishAction($this->_logOutput);
                $this->_action->checkOldAction($this->_logOutput);
                $this->_action->checkActionNotSent($this->_logOutput);
            }
        }
        // clear session
        $this->_backendSession->setIsFromlengow(0);
        // save global error
        if ($globalError) {
            $errors[0] = $globalError;
            if (isset($this->_orderLengowId) && $this->_orderLengowId) {
                $this->_orderErrorFactory->create()->finishOrderErrors($this->_orderLengowId);
                $this->_orderErrorFactory->create()->createOrderError(
                    [
                        'order_lengow_id' => $this->_orderLengowId,
                        'message' => $globalError,
                        'type' => 'import',
                    ]
                );
            }
        }
        if ($this->_importOneOrder) {
            $result['error'] = $errors;
            return $result;
        } else {
            return [
                'order_new' => $orderNew,
                'order_update' => $orderUpdate,
                'order_error' => $orderError,
                'error' => $errors,
            ];
        }
    }

    /**
     * Create or update order in Magento
     *
     * @param mixed $orders API orders
     * @param integer $storeId Magento store Id
     *
     * @return array|false
     */
    protected function _importOrders($orders, $storeId)
    {
        $orderNew = 0;
        $orderUpdate = 0;
        $orderError = 0;
        $importFinished = false;
        foreach ($orders as $orderData) {
            if (!$this->_importOneOrder) {
                $this->_importHelper->setImportInProcess();
            }
            $nbPackage = 0;
            $marketplaceSku = (string)$orderData->marketplace_order_id;
            if ($this->_preprodMode) {
                $marketplaceSku .= '--' . time();
            }
            // set current order to cancel hook updateOrderStatus
            $this->_backendSession->setCurrentOrderLengow($marketplaceSku);
            // if order contains no package
            if (empty($orderData->packages)) {
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->_dataHelper->setLogMessage('import order failed - Lengow error: no package in the order'),
                    $this->_logOutput,
                    $marketplaceSku
                );
                continue;
            }
            // start import
            foreach ($orderData->packages as $packageData) {
                $nbPackage++;
                // check whether the package contains a shipping address
                if (!isset($packageData->delivery->id)) {
                    $this->_dataHelper->log(
                        DataHelper::CODE_IMPORT,
                        $this->_dataHelper->setLogMessage(
                            'import order failed - Lengow error: no delivery address in the order'
                        ),
                        $this->_logOutput,
                        $marketplaceSku
                    );
                    continue;
                }
                $packageDeliveryAddressId = (int)$packageData->delivery->id;
                $firstPackage = $nbPackage > 1 ? false : true;
                // check the package for re-import order
                if ($this->_importOneOrder) {
                    if (!is_null($this->_deliveryAddressId)
                        && $this->_deliveryAddressId !== $packageDeliveryAddressId
                    ) {
                        $this->_dataHelper->log(
                            DataHelper::CODE_IMPORT,
                            $this->_dataHelper->setLogMessage('import order failed - wrong package number'),
                            $this->_logOutput,
                            $marketplaceSku
                        );
                        continue;
                    }
                }
                try {
                    // try to import or update order
                    $importOrderFactory = $this->_importorderFactory->create();
                    $importOrderFactory->init(
                        [
                            'store_id' => $storeId,
                            'preprod_mode' => $this->_preprodMode,
                            'log_output' => $this->_logOutput,
                            'marketplace_sku' => $marketplaceSku,
                            'delivery_address_id' => $packageDeliveryAddressId,
                            'order_data' => $orderData,
                            'package_data' => $packageData,
                            'first_package' => $firstPackage,
                        ]
                    );
                    $order = $importOrderFactory->importOrder();
                    unset($importOrderFactory);
                } catch (LengowException $e) {
                    $errorMessage = $e->getMessage();
                } catch (\Exception $e) {
                    $errorMessage = 'Magento error: "' . $e->getMessage()
                        . '" ' . $e->getFile() . ' line ' . $e->getLine();
                }
                if (isset($errorMessage)) {
                    $decodedMessage = $this->_dataHelper->decodeLogMessage($errorMessage, false);
                    $this->_dataHelper->log(
                        DataHelper::CODE_IMPORT,
                        $this->_dataHelper->setLogMessage('import order failed - %1', [$decodedMessage]),
                        $this->_logOutput,
                        $marketplaceSku
                    );
                    unset($errorMessage);
                    continue;
                }
                if (isset($order)) {
                    // sync to lengow if no preprod_mode
                    if (!$this->_preprodMode && isset($order['order_new']) && $order['order_new']) {
                        $lengowOrder = $this->_lengowOrderFactory->create()->load($order['order_lengow_id']);
                        $synchro = $this->_lengowOrderFactory->create()->synchronizeOrder(
                            $lengowOrder,
                            $this->_connector,
                            $this->_logOutput
                        );
                        if ($synchro) {
                            $synchroMessage = $this->_dataHelper->setLogMessage(
                                'order successfully synchronised with Lengow webservice (ORDER ID %1)',
                                [$lengowOrder->getData('order_sku')]
                            );
                        } else {
                            $synchroMessage = $this->_dataHelper->setLogMessage(
                                'WARNING! Order could NOT be synchronised with Lengow webservice (ORDER ID %1)',
                                [$lengowOrder->getData('order_sku')]
                            );
                        }
                        $this->_dataHelper->log(
                            DataHelper::CODE_IMPORT,
                            $synchroMessage,
                            $this->_logOutput,
                            $marketplaceSku
                        );
                        unset($lengowOrder);
                    }
                    // clean current order in session
                    $this->_backendSession->setCurrentOrderLengow(false);
                    // if re-import order -> return order informations
                    if ($this->_importOneOrder) {
                        return $order;
                    }
                    if (isset($order['order_new']) && $order['order_new']) {
                        $orderNew++;
                    } elseif (isset($order['order_update']) && $order['order_update']) {
                        $orderUpdate++;
                    } elseif (isset($order['order_error']) && $order['order_error']) {
                        $orderError++;
                    }
                }
                // if limit is set
                if ($this->_limit > 0 && $orderNew === $this->_limit) {
                    $importFinished = true;
                    break;
                }
            }
            if ($importFinished) {
                break;
            }
        }
        return [
            'order_new' => $orderNew,
            'order_update' => $orderUpdate,
            'order_error' => $orderError,
        ];
    }

    /**
     * Check credentials and get Lengow connector
     *
     * @return boolean
     */
    protected function _checkCredentials()
    {
        if ($this->_connector->isValidAuth($this->_logOutput)) {
            list($this->_accountId, $this->_accessToken, $this->_secretToken) = $this->_configHelper->getAccessIds();
            $this->_connector->init(['access_token' => $this->_accessToken, 'secret' => $this->_secretToken]);
            return true;
        }
        return false;
    }

    /**
     * Check catalog ids for a store
     *
     * @param Store $store Magento store instance
     *
     * @return boolean
     */
    protected function _checkCatalogIds($store)
    {
        if ($this->_importOneOrder) {
            return true;
        }
        $storeCatalogIds = [];
        $catalogIds = $this->_configHelper->getCatalogIds((int)$store->getId());
        foreach ($catalogIds as $catalogId) {
            if (array_key_exists($catalogId, $this->_catalogIds)) {
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->_dataHelper->setLogMessage(
                        'catalog ID %1 is already used by shop %2 (%3)',
                        [
                            $catalogId,
                            $this->_catalogIds[$catalogId]['name'],
                            $this->_catalogIds[$catalogId]['store_id'],
                        ]
                    ),
                    $this->_logOutput
                );
            } else {
                $this->_catalogIds[$catalogId] = ['store_id' => (int)$store->getId(), 'name' => $store->getName()];
                $storeCatalogIds[] = $catalogId;
            }
        }
        if (count($storeCatalogIds) > 0) {
            $this->_storeCatalogIds = $storeCatalogIds;
            return true;
        }
        return false;
    }

    /**
     * Call Lengow order API
     *
     * @param Store $store Magento store instance
     *
     * @throws LengowException no connection with webservices / credentials not valid
     *
     * @return array
     */
    protected function _getOrdersFromApi($store)
    {
        $page = 1;
        $orders = [];
        // convert order amount or not
        $noCurrencyConversion = !(bool)$this->_configHelper->get('currency_conversion_enabled', $store->getId());
        if ($this->_importOneOrder) {
            $this->_dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->_dataHelper->setLogMessage(
                    'get order with order ID: %1 and marketplace: %2',
                    [
                        $this->_marketplaceSku,
                        $this->_marketplaceName,
                    ]
                ),
                $this->_logOutput
            );
        } else {
            $dateFrom = $this->_createdFrom
                ? $this->_dateTime->gmtDate('Y-m-d H:i:s', $this->_createdFrom)
                : $this->_timezone->date($this->_updatedFrom)->format('Y-m-d H:i:s');
            $dateTo = $this->_createdTo
                ? $this->_dateTime->gmtDate('Y-m-d H:i:s', $this->_createdTo)
                : $this->_timezone->date($this->_updatedTo)->format('Y-m-d H:i:s');
            $this->_dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->_dataHelper->setLogMessage(
                    'get orders between %1 and %2 for catalogs ID: %3',
                    [
                        $dateFrom,
                        $dateTo,
                        implode(', ', $this->_storeCatalogIds),
                    ]
                ),
                $this->_logOutput
            );
        }
        do {
            try {
                if ($this->_importOneOrder) {
                    $results = $this->_connector->get(
                        Connector::API_ORDER,
                        [
                            'marketplace_order_id' => $this->_marketplaceSku,
                            'marketplace' => $this->_marketplaceName,
                            'no_currency_conversion' => $noCurrencyConversion,
                            'account_id' => $this->_accountId,
                            'page' => $page,
                        ],
                        Connector::FORMAT_STREAM,
                        '',
                        $this->_logOutput
                    );
                } else {
                    if ($this->_createdFrom && $this->_createdTo) {
                        $timeParams = [
                            'marketplace_order_date_from' => $this->_dateTime->gmtDate('c', $this->_createdFrom),
                            'marketplace_order_date_to' => $this->_dateTime->gmtDate('c', $this->_createdTo),
                        ];
                    } else {
                        $timeParams = [
                            'updated_from' => $this->_timezone->date($this->_updatedFrom)->format('c'),
                            'updated_to' => $this->_timezone->date($this->_updatedTo)->format('c'),
                        ];
                    }
                    $results = $this->_connector->get(
                        Connector::API_ORDER,
                        array_merge(
                            $timeParams,
                            [
                                'catalog_ids' => implode(',', $this->_storeCatalogIds),
                                'no_currency_conversion' => $noCurrencyConversion,
                                'account_id' => $this->_accountId,
                                'page' => $page,
                            ]
                        ),
                        Connector::FORMAT_STREAM,
                        '',
                        $this->_logOutput
                    );
                }
            } catch (\Exception $e) {
                throw new LengowException(
                    $this->_dataHelper->setLogMessage(
                        'Lengow webservice : %1 - "%2" in store %3 (%4)',
                        [
                            $e->getCode(),
                            $this->_dataHelper->decodeLogMessage($e->getMessage(), false),
                            $store->getName(),
                            $store->getId(),
                        ]
                    )
                );
            }
            if ($results === null) {
                throw new LengowException(
                    $this->_dataHelper->setLogMessage(
                        "connection didn't work with Lengow's webservice in store %1 (%2)",
                        [$store->getName(), $store->getId()]
                    )
                );
            }
            $results = json_decode($results);
            if (!is_object($results)) {
                throw new LengowException(
                    $this->_dataHelper->setLogMessage(
                        "connection didn't work with Lengow's webservice in store %1 (%2)",
                        [$store->getName(), $store->getId()]
                    )
                );
            }
            // construct array orders
            foreach ($results->results as $order) {
                $orders[] = $order;
            }
            $page++;
            $finish = (is_null($results->next) || $this->_importOneOrder) ? true : false;
        } while ($finish != true);
        return $orders;
    }

    /**
     * Set interval time for order synchronisation
     *
     * @param integer|false $days Import period
     * @param string|false $createdFrom Import of orders since
     * @param string|false $createdTo Import of orders until
     */
    protected function _setIntervalTime($days, $createdFrom, $createdTo)
    {
        if ($createdFrom && $createdTo) {
            // retrieval of orders created from ... until ...
            $createdFromTimestamp = $this->_dateTime->gmtTimestamp($createdFrom);
            $createdToTimestamp = $this->_dateTime->gmtTimestamp($createdTo) + 86399;
            $intervalTime = (int)($createdToTimestamp - $createdFromTimestamp);
            $this->_createdFrom = $createdFromTimestamp;
            $this->_createdTo = $intervalTime > self::MAX_INTERVAL_TIME
                ? $createdFromTimestamp + self::MAX_INTERVAL_TIME
                : $createdToTimestamp;
        } else {
            if ($days) {
                $intervalTime = $days * 86400;
                $intervalTime = $intervalTime > self::MAX_INTERVAL_TIME ? self::MAX_INTERVAL_TIME : $intervalTime;
            } else {
                // order recovery updated since ... days
                $importDays = (int)$this->_configHelper->get('days');
                $intervalTime = $importDays * 86400;
                // add security for older versions of the plugin
                $intervalTime = $intervalTime < self::MIN_INTERVAL_TIME ? self::MIN_INTERVAL_TIME : $intervalTime;
                $intervalTime = $intervalTime > self::MAX_INTERVAL_TIME ? self::MAX_INTERVAL_TIME : $intervalTime;
                // get dynamic interval time for cron synchronisation
                $lastImport = $this->_importHelper->getLastImport();
                $lastSettingUpdate = (int)$this->_configHelper->get('last_setting_update');
                if ($this->_typeImport !== self::TYPE_MANUAL
                    && $lastImport['timestamp'] !== 'none'
                    && $lastImport['timestamp'] > $lastSettingUpdate
                ) {
                    $lastIntervalTime = (time() - $lastImport['timestamp']) + self::SECURITY_INTERVAL_TIME;
                    $intervalTime = $lastIntervalTime > $intervalTime ? $intervalTime : $lastIntervalTime;
                }
            }
            $this->_updatedFrom = time() - $intervalTime;
            $this->_updatedTo = time();
        }
    }
}
