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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Store\Model\WebsiteFactory;
use Magento\Backend\Model\Session as BackendSession;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Model\Import\Ordererror;
use Magento\Store\Api\StoreRepositoryInterface;
use Lengow\Connector\Model\Exception as LengowException;

/**
 * Lengow import
 */
class Import
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface Magento store manager instance
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime Magento datetime instance
     */
    protected $_dateTime;

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
     * @var \Magento\Store\Model\Store\Interceptor Magento store instance
     */
    protected $_store;

    /**
     * @var integer Magento store id
     */
    protected $_storeId;

    /**
     * @var integer amount of products to export
     */
    protected $_limit;

    /**
     * @var integer offset of total product
     */
    protected $_offset;

    /**
     * @var string format to return
     */
    protected $_format;

    /**
     * @var boolean stream return
     */
    protected $_stream;

    /**
     * @var string currency iso code for conversion
     */
    protected $_currency;

    /**
     * @var boolean export Lengow selection
     */
    protected $_selection;

    /**
     * @var boolean export out of stock product
     */
    protected $_outOfStock;

    /**
     * @var boolean include active products
     */
    protected $_inactive;

    /**
     * @var boolean see log or not
     */
    protected $_logOutput;

    /**
     * @var boolean update export date.
     */
    protected $_updateExportDate;

    /**
     * @var string export type (manual, cron or magento cron)
     */
    protected $_exportType;

    /**
     * @var boolean get params available.
     */
    protected $_getParams;

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
     * @var string marketplace order sku
     */
    protected $_marketplaceSku = null;

    /**
     * @var string markeplace name
     */
    protected $_marketplaceName = null;

    /**
     * @var integer Lengow order id
     */
    protected $_orderLengowId = null;

    /**
     * @var integer delivery address id
     */
    protected $_deliveryAddressId = null;

    /**
     * @var integer delivery address id
     */
    protected $_days = null;

    /**
     * @var \Lengow\Connector\Model\Import\Ordererror Lengow ordererror instance
     */
    protected $_orderError;

    /**
     * @var \Magento\Backend\Model\Session $_backendSession Backend session instance
     */
    protected $_backendSession;

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    protected $_storeRepository;

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
     * @var array account ids already imported
     */
    protected $_accountIds = [];

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
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig Magento scope config instance
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper Magento json helper instance
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory Magento website factory instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Helper\Import $importHelper Lengow config helper instance
     * @param \Lengow\Connector\Helper\Sync $syncHelper Lengow sync helper instance
     * @param \Lengow\Connector\Model\Import\Ordererror $orderError Lengow orderError instance
     * @param \Lengow\Connector\Model\Connector $connector Lengow connector instance
     * @param \Magento\Backend\Model\Session $backendSession Backend session instance
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DateTime $dateTime,
        ScopeConfigInterface $scopeConfig,
        JsonHelper $jsonHelper,
        WebsiteFactory $websiteFactory,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        SyncHelper $syncHelper,
        Ordererror $orderError,
        Connector $connector,
        BackendSession $backendSession,
        StoreRepositoryInterface $storeRepository
    )
    {
        $this->_storeManager = $storeManager;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_importHelper = $importHelper;
        $this->_syncHelper = $syncHelper;
        $this->_jsonHelper = $jsonHelper;
        $this->_websiteFactory = $websiteFactory;
        $this->_dateTime = $dateTime;
        $this->_scopeConfig = $scopeConfig;
        $this->_orderError = $orderError;
        $this->_connector = $connector;
        $this->_backendSession = $backendSession;
        $this->_storeRepository = $storeRepository;
    }

    /**
     * Init a new import
     *
     * @param array $params optional options
     * string  marketplace_sku     lengow marketplace order id to import
     * string  marketplace_name    lengow marketplace name to import
     * string  type                type of current import
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
        // params for re-import order
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
            if (array_key_exists('delivery_address_id', $params) && $params['delivery_address_id'] != '') {
                $this->_deliveryAddressId = $params['delivery_address_id'];
            }
        } else {
            // recovering the time interval
            $this->_days = (isset($params['days']) ? (int)$params['days'] : null);
            $this->_limit = (isset($params['limit']) ? (int)$params['limit'] : 0);
        }
        // get other params
        $this->_preprodMode = (
        isset($params['preprod_mode'])
            ? (bool)$params['preprod_mode']
            : (bool)$this->_configHelper->get('preprod_mode_enable')
        );
        $this->_typeImport = (isset($params['type']) ? $params['type'] : 'manual');
        $this->_logOutput = (isset($params['log_output']) ? (bool)$params['log_output'] : false);
        $this->_storeId = (isset($params['store_id']) ? (int)$params['store_id'] : null);
    }

    /**
     * Execute import: fetch orders and import them
     *
     * @throws LengowException order not found
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
        // clean logs > 20 days
        $this->_dataHelper->cleanLog();
        if ($this->_importHelper->importIsInProcess() && !$this->_preprodMode && !$this->_importOneOrder) {
            $globalError = $this->_dataHelper->setLogMessage(
                'Import has already started. Please wait %1 seconds before re-importing orders',
                [$this->_importHelper->restTimeToImport()]
            );
            $this->_dataHelper->log('Import', $globalError, $this->_logOutput);
        } elseif (!$this->_checkCredentials()) {
            $globalError = $this->_dataHelper->setLogMessage('Account ID, token access or secret token are not valid');
            $this->_dataHelper->log('Import', $globalError, $this->_logOutput);
        } else {
            // to activate lengow shipping method
            $this->_backendSession->setIsFromlengow(1);
            // check Lengow catalogs for order synchronisation
            if (!$this->_preprodMode && !$this->_importOneOrder && $this->_typeImport === 'manual') {
                $this->_syncHelper->syncCatalog();
            }
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage('## start %1 import ##', [$this->_typeImport]),
                $this->_logOutput
            );
            if ($this->_preprodMode) {
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage('WARNING! Pre-production mode is activated'),
                    $this->_logOutput
                );
            }
            if (!$this->_importOneOrder) {
                $this->_importHelper->setImportInProcess();
                // update last import date
                $this->_importHelper->updateDateImport($this->_typeImport);
            }
            // get all store for import
            $storeCollection = $this->_storeManager->getStores();
            /** @var Store $store */
            foreach ($storeCollection as $store) {
                if ((!is_null($this->_storeId) && (int)$store->getId() != $this->_storeId) || !$store->isActive()) {
                    continue;
                }
                if ($this->_configHelper->get('store_enable', (int)$store->getId())) {
                    $this->_dataHelper->log(
                        'Import',
                        $this->_dataHelper->setLogMessage(
                            'start import in store %1 (%2)',
                            [$store->getName(), (int)$store->getId()]
                        ),
                        $this->_logOutput
                    );
                    try {
                        // check store catalog ids
                        if (!$this->_checkCatalogIds($store)) {
                            $errorCatalogIds = $this->_dataHelper->setLogMessage(
                                'No catalog ID valid for the store %1 (%2)e',
                                [$store->getName(), (int)$store->getId()]
                            );
                            $this->_dataHelper->log('Import', $errorCatalogIds, $this->_logOutput);
                            $errors[(int)$store->getId()] = $errorCatalogIds;
                            continue;
                        }
                        // get orders from Lengow API
                        $orders = $this->_getOrdersFromApi($store);
                        $totalOrders = count($orders);
                        if ($this->_importOneOrder) {
                            $this->_dataHelper->log(
                                'Import',
                                $this->_dataHelper->setLogMessage(
                                    '%1 order found for order ID: %2 and marketplace: %3 with account ID: %4',
                                    [
                                        $totalOrders,
                                        $this->_marketplaceSku,
                                        $this->_marketplaceName,
                                        $this->_accountId
                                    ]
                                ),
                                $this->_logOutput
                            );
                        } else {
                            $this->_dataHelper->log(
                                'Import',
                                $this->_dataHelper->setLogMessage(
                                    '%1 order(s) found with account ID: %2',
                                    [$totalOrders, $this->_accountId]
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
                            $this->_orderError->finishOrderErrors($this->_orderLengowId);
                        }
                        // import orders in Magento
                        //To see results
//                        var_dump($orders);
                        //TODO
//                        $result = $this->_importOrders( $orders, (int) $store->getId() );
//                        if ( ! $this->_importOneOrder ) {
//                            $orderNew += $result['order_new'];
//                            $orderUpdate += $result['order_update'];
//                            $orderError += $result['order_error'];
//                        }
                    } catch (LengowException $e) {
                        $errorMessage = $e->getMessage();
                    } catch (\Exception $e) {
                        $errorMessage = '[Magento error] "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
                    }
                    if (isset($errorMessage)) {
                        if (!is_null($this->_orderLengowId)) {
                            $this->_orderError->finishOrderErrors($this->_orderLengowId);
                            $this->_orderError->createOrderError(
                                [
                                    'order_lengow_id' => $this->_orderLengowId,
                                    'message' => $errorMessage,
                                    'type' => 'import'
                                ]
                            );
                        }
                        $decodedMessage = $this->_dataHelper->decodeLogMessage($errorMessage, 'en_GB');
                        $this->_dataHelper->log(
                            'Import',
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
                $this->_dataHelper->log('Import',
                    $this->_dataHelper->setLogMessage('%1 order(s) imported', [$orderNew]),
                    $this->_logOutput
                );
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage('%1 order(s) updated', [$orderUpdate]),
                    $this->_logOutput
                );
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage('%1 order(s) with errors', [$orderError]),
                    $this->_logOutput
                );
            }
            // finish import process
            $this->_importHelper->setImportEnd();
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage('## end %1 import ##', [$this->_typeImport]),
                $this->_logOutput
            );
            // sending email in error for orders
            if ($this->_configHelper->get('report_mail_enable') && !$this->_preprodMode && !$this->_importOneOrder) {
                //TODO
//                $this->_importHelper->sendMailAlert( $this->_logOutput );
            }
            //TODO
//            if ( ! $this->_preprodMode && ! $this->_importOneOrder && $this->_typeImport == 'manual' ) {
//                $action = Mage::getModel( 'lengow/import_action' );
//                $action->checkFinishAction();
//                $action->checkActionNotSent();
//                unset( $action );
//            }
        }
        // Clear session
        $this->_backendSession->setIsFromlengow(0);
        // save global error
        if ($globalError) {
            $errors[0] = $globalError;
            if (isset($this->_orderLengowId) && $this->_orderLengowId) {
                $this->_orderError->finishOrderErrors($this->_orderLengowId);
                $this->_orderError->createOrderError(
                    [
                        'order_lengow_id' => $this->_orderLengowId,
                        'message' => $globalError,
                        'type' => 'import'
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
                'error' => $errors
            ];
        }
    }

    /**
     * Check credentials and get Lengow connector
     *
     * @return boolean
     */
    protected function _checkCredentials()
    {
        if ($this->_connector->isValidAuth()) {
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
                    'Import',
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
        // get import period
        $days = (!is_null($this->_days) ? $this->_days : $this->_configHelper->get('days', $store->getId()));
        $dateFrom = date('c', strtotime(date('Y-m-d') . ' -' . $days . 'days'));
        $dateTo = date('c');
        if ($this->_importOneOrder) {
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage(
                    'get order with order ID: %1 and marketplace: %2',
                    [$this->_marketplaceSku, $this->_marketplaceName]
                ),
                $this->_logOutput
            );
        } else {
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage(
                    'get orders between %1 and %2 for catalogs ID: %3',
                    [
                        date('Y-m-d', strtotime((string)$dateFrom)),
                        date('Y-m-d', strtotime((string)$dateTo)),
                        $this->_accountId
                    ]
                ),
                $this->_logOutput
            );
        }
        do {
            if ($this->_importOneOrder) {
                $results = $this->_connector->get(
                    '/v3.0/orders',
                    [
                        'marketplace_order_id' => $this->_marketplaceSku,
                        'marketplace' => $this->_marketplaceName,
                        'account_id' => $this->_accountId,
                        'page' => $page
                    ],
                    'stream'
                );
            } else {
                $results = $this->_connector->get(
                    '/v3.0/orders',
                    [
                        'updated_from' => $dateFrom,
                        'updated_to' => $dateTo,
                        'account_id' => $this->_accountId,
                        'page' => $page
                    ],
                    'stream'
                );
            }
            if (is_null($results)) {
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
            if (isset($results->error)) {
                throw new LengowException(
                    $this->_dataHelper->setLogMessage(
                        'Lengow webservice : %1 - %2 in store %3 (%4)',
                        [
                            $results->error->code,
                            $results->error->message,
                            $store->getName(),
                            $store->getId()
                        ]
                    )
                );
            }
            // Construct array orders
            foreach ($results->results as $order) {
                $orders[] = $order;
            }
            $page++;
            $finish = (is_null($results->next) || $this->_importOneOrder) ? true : false;
        } while ($finish != true);
        return $orders;
    }
}
