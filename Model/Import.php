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

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\CatalogInventory\Model\Configuration as CatalogInventoryConfiguration;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Store\Model\WebsiteFactory;
use Magento\Backend\Model\Session as BackendSession;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Model\Export\Feed;
use Lengow\Connector\Model\Export\Product;
use Lengow\Connector\Model\Import\Ordererror;
use Lengow\Connector\Model\Connector;
use Magento\Store\Api\StoreRepositoryInterface;
use Lengow\Connector\Model\Exception as LengowException;

/**
 * Lengow import
 */
class Import {
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
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status Magento product status instance
     */
    protected $_productStatus;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory Magento product collection factory
     */
    protected $_productCollectionFactory;

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
     * @var \Lengow\Connector\Model\Export\Feed Lengow feed instance
     */
    protected $_feed;

    /**
     * @var \Lengow\Connector\Model\Connector Lengow connector instance
     */
    protected $_connector;

    /**
     * @var \Lengow\Connector\Model\Export\Product Lengow product instance
     */
    protected $_product;

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
     * @var array export product types
     */
    protected $_productTypes;

    /**
     * @var array product ids to be exported
     */
    protected $_productIds;

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
     * @var Magento\Backend\Model\Session $_backendSession Backend session instance
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
    protected $_accountIds = array();

    /**
     * Constructor
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Magento store manager instance
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime Magento datetime instance
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig Magento scope config instance
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus Magento product status instance
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper Magento json helper instance
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory Magento website factory instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Helper\Import $importHelper Lengow config helper instance
     * @param \Lengow\Connector\Helper\Sync $syncHelper Lengow sync helper instance
     * @param \Lengow\Connector\Model\Export\Feed $feed Lengow feed instance
     * @param \Lengow\Connector\Model\Export\Product $product Lengow product instance
     * @param \Lengow\Connector\Model\Import\Ordererror $orderError Lengow orderError instance
     * @param \Lengow\Connector\Model\Connector $connector Lengow connector instance
     * @param \Magento\Backend\Model\Session $backendSession Backend session instance
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DateTime $dateTime,
        ScopeConfigInterface $scopeConfig,
        ProductStatus $productStatus,
        ProductCollectionFactory $productCollectionFactory,
        JsonHelper $jsonHelper,
        WebsiteFactory $websiteFactory,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        SyncHelper $syncHelper,
        Feed $feed,
        Product $product,
        Ordererror $orderError,
        Connector $connector,
        BackendSession $backendSession,
        StoreRepositoryInterface $storeRepository

    ) {
        $this->_storeManager             = $storeManager;
        $this->_dataHelper               = $dataHelper;
        $this->_configHelper             = $configHelper;
        $this->_importHelper             = $importHelper;
        $this->_syncHelper               = $syncHelper;
        $this->_feed                     = $feed;
        $this->_product                  = $product;
        $this->_jsonHelper               = $jsonHelper;
        $this->_websiteFactory           = $websiteFactory;
        $this->_dateTime                 = $dateTime;
        $this->_scopeConfig              = $scopeConfig;
        $this->_productStatus            = $productStatus;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_orderError               = $orderError;
        $this->_connector                = $connector;
        $this->_backendSession           = $backendSession;
        $this->_storeRepository          = $storeRepository;
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
    public function init( $params ) {
        // params for re-import order
        if ( array_key_exists( 'marketplace_sku', $params )
             && array_key_exists( 'marketplace_name', $params )
             && array_key_exists( 'store_id', $params )
        ) {
            if ( isset( $params['order_lengow_id'] ) ) {
                $this->_orderLengowId = (int) $params['order_lengow_id'];
            }
            $this->_importOneOrder  = true;
            $this->_limit           = 1;
            $this->_marketplaceSku  = (string) $params['marketplace_sku'];
            $this->_marketplaceName = (string) $params['marketplace_name'];
            if ( array_key_exists( 'delivery_address_id', $params ) && $params['delivery_address_id'] != '' ) {
                $this->_deliveryAddressId = $params['delivery_address_id'];
            }
        } else {
            // recovering the time interval
            $this->_days  = ( isset( $params['days'] ) ? (int) $params['days'] : null );
            $this->_limit = ( isset( $params['limit'] ) ? (int) $params['limit'] : 0 );
        }
        // get other params
        $this->_preprodMode = (
        isset( $params['preprod_mode'] )
            ? (bool) $params['preprod_mode']
            : (bool) $this->_configHelper->get( 'preprod_mode_enable' )
        );
        $this->_typeImport  = ( isset( $params['type'] ) ? $params['type'] : 'manual' );
        $this->_logOutput   = ( isset( $params['log_output'] ) ? (bool) $params['log_output'] : false );
        $this->_storeId     = ( isset( $params['store_id'] ) ? (int) $params['store_id'] : null );
    }

    /**
     * Execute import: fetch orders and import them
     *
     * @throws Lengow_Connector_Model_Exception order not found
     *
     * @return array
     */
    public function exec() {

        echo 'plop0';
        $orderNew    = 0;
        $orderUpdate = 0;
        $orderError  = 0;
        $errors      = array();
        $globalError = false;
        // clean logs > 20 days
        $this->_dataHelper->cleanLog();
        if ( $this->_importHelper->importIsInProcess() && ! $this->_preprodMode && ! $this->_importOneOrder ) {
            //TODO logs
            $globalError = $this->_dataHelper->setLogMessage(
                'lengow_log.error.rest_time_to_import',
            // TODO array
                array( 'rest_time' => $this->_importHelper->restTimeToImport() )
            );
            $this->_dataHelper->log( 'Import', $globalError, $this->_logOutput );
        } elseif (!$this->_checkCredentials()) {
            $globalError = $this->_dataHelper->setLogMessage('lengow_log.error.credentials_not_valid');
            $this->_dataHelper->log('Import', $globalError, $this->_logOutput);
        } else {
            // to activate lengow shipping method
            $this->_backendSession->setIsFromlengow( 1 );
            // check Lengow catalogs for order synchronisation
            if (!$this->_preprodMode && !$this->_importOneOrder && $this->_typeImport === 'manual') {
                //$this->_syncHelper->syncCatalog();TODO
            }
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage( 'log.import.start', array( 'type' => $this->_typeImport ) ),
                $this->_logOutput
            );
            if ( $this->_preprodMode ) {
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage( 'log.import.preprod_mode_active' ),
                    $this->_logOutput
                );
            }
            if ( ! $this->_importOneOrder ) {
                $this->_importHelper->setImportInProcess();
                // udpate last import date
                $this->_importHelper->updateDateImport( $this->_typeImport );
            }
            // get all store for import
            $storeCollection = $this->_storeManager->getStores();
            foreach ( $storeCollection as $store ) {
                if ( ( ! is_null( $this->_storeId ) && (int) $store->getId() != $this->_storeId ) || ! $store->isActive() ) {
                    continue;
                }
                echo "<br />" . $store->getFrontendName();
                if ( $this->_configHelper->get( 'store_enable', (int) $store->getId() ) ) {
                    echo "<br />" . $store->getFrontendName() . $store->getId();
                    $this->_dataHelper->log(
                        'Import',
                        $this->_dataHelper->setLogMessage(
                            'log.import.start_for_store',
                            array(
                                'store_name' => $store->getName(),
                                'store_id'   => (int) $store->getId()
                            )
                        ),
                        $this->_logOutput
                    );
                    try {
                        // check store catalog ids
//                        if (!$this->_checkCatalogIds($store)) {//TODO
//                            $errorCatalogIds = $this->_dataHelper->setLogMessage(
//                                'lengow_log.error.no_catalog_for_store',
//                                array(
//                                    'store_name' => $store->getName(),
//                                    'store_id' => (int)$store->getId(),
//                                )
//                            );
//                            $this->_dataHelper->log('Import', $errorCatalogIds, $this->_logOutput);
//                            $errors[(int)$store->getId()] = $errorCatalogIds;
//                            continue;
//                        }
                        // get orders from Lengow API
                        $orders = $this->_getOrdersFromApi( $store );
                        var_dump( $orders );
                        $totalOrders = count( $orders );
                        if ( $this->_importOneOrder ) {
                            $this->_dataHelper->log(
                                'Import',
                                $this->_dataHelper->setLogMessage(
                                    'log.import.find_one_order',
                                    array(
                                        'nb_order'         => $totalOrders,
                                        'marketplace_sku'  => $this->_marketplaceSku,
                                        'marketplace_name' => $this->_marketplaceName,
                                        'account_id'       => $this->_accountId
                                    )
                                ),
                                $this->_logOutput
                            );
                        } else {
                            $this->_dataHelper->log(
                                'Import',
                                $this->_dataHelper->setLogMessage(
                                    'log.import.find_all_orders',
                                    array(
                                        'nb_order'   => $totalOrders,
                                        'account_id' => $this->_accountId
                                    )
                                ),
                                $this->_logOutput
                            );
                        }
//                        if ( $totalOrders <= 0 && $this->_importOneOrder ) {
//                            throw new Lengow_Connector_Model_Exception( 'lengow_log.error.order_not_found' );
//                        } elseif ( $totalOrders <= 0 ) {
//                            continue;
//                        }
//                        if ( ! is_null( $this->_orderLengowId ) ) {
//                            $lengowOrderError = Mage::getModel( 'lengow/import_ordererror' );
//                            $lengowOrderError->finishOrderErrors( $this->_orderLengowId );
//                        }
//                        // import orders in Magento
//                        $result = $this->_importOrders( $orders, (int) $store->getId() );
//                        if ( ! $this->_importOneOrder ) {
//                            $orderNew += $result['order_new'];
//                            $orderUpdate += $result['order_update'];
//                            $orderError += $result['order_error'];
//                        }
//                    } catch ( Lengow_Connector_Model_Exception $e ) {
//                        $errorMessage = $e->getMessage();
                    } catch ( Exception $e ) {
                        $errorMessage = '[Magento error] "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
                    }
                    if ( isset( $errorMessage ) ) {
                        if ( ! is_null( $this->_orderLengowId ) ) {
                            $this->_orderError->finishOrderErrors( $this->_orderLengowId );
                            $this->_orderError->createOrderError(
                                array(
                                    'order_lengow_id' => $this->_orderLengowId,
                                    'message'         => $errorMessage,
                                    'type'            => 'import'
                                )
                            );
                            unset( $lengowOrderError );
                        }
                        $decodedMessage = $this->_dataHelper->decodeLogMessage( $errorMessage, 'en_GB' );
                        $this->_dataHelper->log(
                            'Import',
                            $this->_dataHelper->setLogMessage(
                                'log.import.import_failed',
                                array( 'decoded_message' => $decodedMessage )
                            ),
                            $this->_logOutput
                        );
                        $errors[ (int) $store->getId() ] = $errorMessage;
                        unset( $errorMessage );
                        continue;
                    }
                }
                unset( $store );
            }
            if ( ! $this->_importOneOrder ) {
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage(
                        'lengow_log.error.nb_order_imported',
                        array( 'nb_order' => $orderNew )
                    ),
                    $this->_logOutput
                );
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage(
                        'lengow_log.error.nb_order_updated',
                        array( 'nb_order' => $orderUpdate )
                    ),
                    $this->_logOutput
                );
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage(
                        'lengow_log.error.nb_order_with_error',
                        array( 'nb_order' => $orderError )
                    ),
                    $this->_logOutput
                );
            }
            // finish import process
            $this->_importHelper->setImportEnd();
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage( 'log.import.end', array( 'type' => $this->_typeImport ) ),
                $this->_logOutput
            );
            // sending email in error for orders
            if ( $this->_configHelper->get( 'report_mail_enable' ) && ! $this->_preprodMode && ! $this->_importOneOrder ) {
                $this->_importHelper->sendMailAlert( $this->_logOutput );
            }
//            if ( ! $this->_preprodMode && ! $this->_importOneOrder && $this->_typeImport == 'manual' ) {
//                $action = Mage::getModel( 'lengow/import_action' );
//                $action->checkFinishAction();
//                $action->checkActionNotSent();
//                unset( $action );
//            }
        }
        // Clear session
        $this->_backendSession->setIsFromlengow( 0 );
        if ( $this->_importOneOrder ) {
            $result['error'] = $errors;

            return $result;
        } else {
            return array(
                'order_new'    => $orderNew,
                'order_update' => $orderUpdate,
                'order_error'  => $orderError,
                'error'        => $errors
            );
        }
    }

    /**
     * Check credentials and get Lengow connector
     *
     * @return boolean
     */
    protected function _checkCredentials() {
        if ( $this->_connector->isValidAuth() ) {
            list( $this->_accountId, $this->_accessToken, $this->_secretToken ) = $this->_configHelper->getAccessIds();
            $this->_connector->init( $this->_accessToken, $this->_secretToken );

            return true;
        }

        return false;
    }

    /**
     * Call Lengow order API
     *
     * @param StoreManagerInterface\ $store Magento store instance
     *
     * @throws LengowException no connection with webservices / credentials not valid
     *
     * @return array
     */
    protected function _getOrdersFromApi( $store ) {
        $page   = 1;
        $orders = array();
        // get import period
        $days = (!is_null($this->_days) ? $this->_days : $this->_configHelper->get('days', $store->getId()));
        $dateFrom = date('c', strtotime(date('Y-m-d') . ' -' . $days . 'days'));
        $dateTo = date('c');
        if ( $this->_importOneOrder ) {
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage(
                    'log.import.connector_get_order',
                    array(
                        'marketplace_sku'  => $this->_marketplaceSku,
                        'marketplace_name' => $this->_marketplaceName
                    )
                ),
                $this->_logOutput
            );
        } else {
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage(
                    'log.import.connector_get_all_order',
                    array(
                        'date_from'  => date( 'Y-m-d', strtotime( (string) $dateFrom ) ),
                        'date_to'    => date( 'Y-m-d', strtotime( (string) $dateTo ) ),
                        'account_id' => $this->_accountId
                    )
                ),
                $this->_logOutput
            );
        }
        do {
            if ( $this->_importOneOrder ) {
                $results = $this->_connector->get(
                    '/v3.0/orders',
                    array(
                        'marketplace_order_id' => $this->_marketplaceSku,
                        'marketplace'          => $this->_marketplaceName,
                        'account_id'           => $this->_accountId,
                        'page'                 => $page
                    ),
                    'stream'
                );
            } else {
                $results = $this->_connector->get(
                    '/v3.0/orders',
                    array(
                        'updated_from' => $dateFrom,
                        'updated_to'   => $dateTo,
                        'account_id'   => $this->_accountId,
                        'page'         => $page
                    ),
                    'stream'
                );
            }
            if ( is_null( $results ) ) {
                throw new LengowException(
                    $this->_dataHelper->setLogMessage(
                        'lengow_log.exception.no_connection_webservice',
                        array(
                            'store_name' => $store->getName(),
                            'store_id'   => $store->getId()
                        )
                    )
                );
            }
            $results = json_decode( $results );
            if ( ! is_object( $results ) ) {
                throw new LengowException(
                    $this->_dataHelper->setLogMessage(
                        'lengow_log.exception.no_connection_webservice',
                        array(
                            'store_name' => $store->getName(),
                            'store_id'   => $store->getId()
                        )
                    )
                );
            }
            if ( isset( $results->error ) ) {
                throw new LengowException(
                    $this->_dataHelper->setLogMessage(
                        'lengow_log.exception.error_lengow_webservice',
                        array(
                            'error_code'    => $results->error->code,
                            'error_message' => $results->error->message,
                            'store_name'    => $store->getName(),
                            'store_id'      => $store->getId()
                        )
                    )
                );
            }
            // Construct array orders
            foreach ( $results->results as $order ) {
                $orders[] = $order;
            }
            $page ++;
            $finish = (is_null($results->next) || $this->_importOneOrder) ? true : false;
        } while ($finish != true);

        return $orders;
    }
}
