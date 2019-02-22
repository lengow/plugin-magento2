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

namespace Lengow\Connector\Model\Import;

use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\CatalogInventory\Api\StockManagementInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Model\Quote\AddressFactory as QuoteAddressFactory;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Tax\Model\TaxCalculation;
use Magento\Tax\Model\Calculation;
use Magento\Quote\Model\QuoteManagement;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Catalog\Model\ProductFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Shipping\Model\Config as ShippingConfig;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Quote\Model\QuoteFactory as MagentoQuoteFactory;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\Orderline as LengowOrderline;
use Lengow\Connector\Model\Import\OrderlineFactory as LengowOrderlineFactory;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;
use Lengow\Connector\Model\Import\Customer as LengowCustomer;
use Lengow\Connector\Model\Import\QuoteFactory as LengowQuoteFactory;
use Lengow\Connector\Model\Payment\Lengow as LengowPayment;
use Lengow\Connector\Model\Exception as LengowException;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;

/**
 * Model import importorder
 */
class Importorder extends AbstractModel
{
    /**
     * @var \Magento\Quote\Model\QuoteFactory Magento quote factory instance
     */
    protected $_quoteMagentoFactory;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface Magento cart management instance
     */
    protected $_cartManagementInterface;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface Magento cart repository instance
     */
    protected $_cartRepositoryInterface;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface Magento order repository instance
     */
    protected $_orderRepository;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $_addressRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Tax\Model\Config Tax configuration object
     */
    protected $_taxConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface Scope config interface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface Magento store manager
     */
    protected $_storeManager;

    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    protected $_quoteAddressFactory;

    /**
     * @var \Magento\Tax\Model\TaxCalculation tax calculation interface
     */
    protected $_taxCalculation;

    /**
     * @var \Magento\Tax\Model\Calculation calculation
     */
    protected $_calculation;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $_quoteManagement;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var \Magento\Catalog\Model\ProductFactory Magento product factory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService Magento invoice service
     */
    protected $_invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction Magento transaction
     */
    protected $_transaction;

    /**
     * @var \Magento\Shipping\Model\Config Magento shipping config
     */
    protected $_shippingConfig;

    /**
     * @var StockRegistryInterface
     */
    protected $_stockRegistry;

    /**
     * @var StockManagementInterface
     */
    protected $_stockManagement;

    /**
     * @var \Lengow\Connector\Model\Payment\Lengow Lengow payment instance
     */
    protected $_lengowPayment;

    /**
     * @var \Lengow\Connector\Model\Import\Order Lengow order instance
     */
    protected $_lengowOrder;

    /**
     * @var \Lengow\Connector\Model\Import\OrderFactory Lengow order instance
     */
    protected $_lengowOrderFactory;

    /**
     * @var \Lengow\Connector\Model\Import\OrdererrorFactory Lengow ordererrorFactory instance
     */
    protected $_orderErrorFactory;

    /**
     * @var \Lengow\Connector\Model\Import\Customer Lengow customer instance
     */
    protected $_lengowCustomer;

    /**
     * @var \Lengow\Connector\Model\Import\QuoteFactory Lengow quote instance
     */
    protected $_lengowQuoteFactory;

    /**
     * @var \Lengow\Connector\Model\Import\Orderline Lengow orderline instance
     */
    protected $_lengowOrderline;

    /**
     * @var \Lengow\Connector\Model\Import\OrderlineFactory
     */
    protected $_lengowOrderLineFactory;

    /**
     * @var \Lengow\Connector\Helper\Import Lengow import helper instance
     */
    protected $_importHelper;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var integer order items
     */
    protected $_orderItems;

    /**
     * @var string id lengow of current order
     */
    protected $_marketplaceSku;

    /**
     * @var integer id of delivery address for current order
     */
    protected $_deliveryAddressId;

    /**
     * @var integer Magento store id
     */
    protected $_storeId = null;

    /**
     * @var boolean use preprod mode
     */
    protected $_preprodMode = false;

    /**
     * @var boolean display log messages
     */
    protected $_logOutput = false;

    /**
     * @var mixed order data
     */
    protected $_orderData;

    /**
     * @var mixed package data
     */
    protected $_packageData;

    /**
     * @var boolean is first package
     */
    protected $_firstPackage;

    /**
     * @var \Lengow\Connector\Model\Import\Marketplace Lengow marketplace instance
     */
    protected $_marketplace;

    /**
     * @var string marketplace label
     */
    protected $_marketplaceLabel;

    /**
     * @var boolean re-import order
     */
    protected $_isReimported = false;

    /**
     * @var string Lengow order state
     */
    protected $_orderStateLengow;

    /**
     * @var string marketplace order state
     */
    protected $_orderStateMarketplace;

    /**
     * @var integer id of the record Lengow order table
     */
    protected $_orderLengowId;

    /**
     * @var float order processing fees
     */
    protected $_processingFee;

    /**
     * @var float order shipping costs
     */
    protected $_shippingCost;

    /**
     * @var float order amount
     */
    protected $_orderAmount;

    /**
     * @var string carrier name
     */
    protected $_carrierName = null;

    /**
     * @var string carrier method
     */
    protected $_carrierMethod = null;

    /**
     * @var boolean order shipped by marketplace
     */
    protected $_shippedByMp = false;

    /**
     * @var string carrier tracking number
     */
    protected $_trackingNumber = null;

    /**
     * @var string carrier relay id
     */
    protected $_relayId = null;

    /**
     * Constructor
     *
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement Magento cart management instance
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository Magento cart repository instance
     * @param \Magento\Framework\Model\Context $context Magento context instance
     * @param \Magento\Framework\Registry $registry Magento registry instance
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository Lengow order instance
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Magento store manager
     * @param \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory Magento quote factory address
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Tax\Model\Config $taxConfig Tax configuration object
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig Scope config interface
     * @param \Magento\Tax\Model\TaxCalculation $taxCalculation tax calculation interface
     * @param \Magento\Tax\Model\Calculation $calculation calculation
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime Magento datetime instance
     * @param \Magento\Catalog\Model\ProductFactory $productFactory Magento product factory
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService Magento invoice service
     * @param \Magento\Framework\DB\Transaction $transaction Magento transaction
     * @param \Magento\Shipping\Model\Config $shippingConfig Magento shipping config
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry Magento stock registry instance
     * @param \Magento\CatalogInventory\Api\StockManagementInterface $stockManagement
     * @param \Magento\Quote\Model\QuoteFactory $quoteMagentoFactory
     * @param \Lengow\Connector\Model\Import\Order $lengowOrder Lengow order instance
     * @param \Lengow\Connector\Model\Payment\Lengow $lengowPayment Lengow payment instance
     * @param \Lengow\Connector\Model\Import\OrderFactory $lengowOrderFactory Lengow order instance
     * @param \Lengow\Connector\Model\Import\OrdererrorFactory $orderErrorFactory Lengow orderErrorFactory instance
     * @param \Lengow\Connector\Model\Import\Customer $lengowCustomer Lengow customer instance
     * @param \Lengow\Connector\Model\Import\QuoteFactory $lengowQuoteFactory Lengow quote instance
     * @param \Lengow\Connector\Model\Import\Orderline $lengowOrderline Lengow orderline instance
     * @param \Lengow\Connector\Model\Import\OrderlineFactory $lengowOrderLineFactory
     * @param \Lengow\Connector\Helper\Import $importHelper Lengow import helper instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     */
    public function __construct(
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        Context $context,
        Registry $registry,
        OrderRepositoryInterface $orderRepository,
        AddressRepositoryInterface $addressRepository,
        CustomerRepositoryInterface $customerRepository,
        TaxConfig $taxConfig,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        QuoteAddressFactory $quoteAddressFactory,
        TaxCalculation $taxCalculation,
        Calculation $calculation,
        QuoteManagement $quoteManagement,
        DateTime $dateTime,
        ProductFactory $productFactory,
        InvoiceService $invoiceService,
        Transaction $transaction,
        ShippingConfig $shippingConfig,
        StockRegistryInterface $stockRegistry,
        StockManagementInterface $stockManagement,
        MagentoQuoteFactory $quoteMagentoFactory,
        LengowPayment $lengowPayment,
        LengowOrder $lengowOrder,
        LengowOrderFactory $lengowOrderFactory,
        OrdererrorFactory $orderErrorFactory,
        LengowCustomer $lengowCustomer,
        LengowQuoteFactory $lengowQuoteFactory,
        LengowOrderline $lengowOrderline,
        LengowOrderlineFactory $lengowOrderLineFactory,
        ImportHelper $importHelper,
        DataHelper $dataHelper,
        ConfigHelper $configHelper
    )
    {
        $this->_cartManagementInterface = $cartManagement;
        $this->_cartRepositoryInterface = $cartRepository;
        $this->_orderRepository = $orderRepository;
        $this->_addressRepository = $addressRepository;
        $this->_customerRepository = $customerRepository;
        $this->_taxConfig = $taxConfig;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_quoteAddressFactory = $quoteAddressFactory;
        $this->_taxCalculation = $taxCalculation;
        $this->_calculation = $calculation;
        $this->_quoteManagement = $quoteManagement;
        $this->_dateTime = $dateTime;
        $this->_productFactory = $productFactory;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_shippingConfig = $shippingConfig;
        $this->_stockRegistry = $stockRegistry;
        $this->_stockManagement = $stockManagement;
        $this->_quoteMagentoFactory = $quoteMagentoFactory;
        $this->_lengowPayment = $lengowPayment;
        $this->_lengowOrder = $lengowOrder;
        $this->_lengowOrderFactory = $lengowOrderFactory;
        $this->_orderErrorFactory = $orderErrorFactory;
        $this->_lengowCustomer = $lengowCustomer;
        $this->_lengowQuoteFactory = $lengowQuoteFactory;
        $this->_lengowOrderline = $lengowOrderline;
        $this->_lengowOrderLineFactory = $lengowOrderLineFactory;
        $this->_importHelper = $importHelper;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        parent::__construct($context, $registry);
    }

    /**
     * init a import order
     *
     * @param array $params optional options for load a import order
     *
     * @throws LengowException
     *
     * @return Importorder
     */
    public function init($params)
    {
        $this->_storeId = $params['store_id'];
        $this->_preprodMode = $params['preprod_mode'];
        $this->_logOutput = $params['log_output'];
        $this->_marketplaceSku = $params['marketplace_sku'];
        $this->_deliveryAddressId = $params['delivery_address_id'];
        $this->_orderData = $params['order_data'];
        $this->_packageData = $params['package_data'];
        $this->_firstPackage = $params['first_package'];
        $this->_marketplace = $this->_importHelper->getMarketplaceSingleton((string)$this->_orderData->marketplace);
        $this->_marketplaceLabel = $this->_marketplace->labelName;
        $this->_orderStateMarketplace = (string)$this->_orderData->marketplace_status;
        $this->_orderStateLengow = $this->_marketplace->getStateLengow($this->_orderStateMarketplace);
        return $this;
    }

    /**
     * Create or update order
     *
     * @return array|false
     */
    public function importOrder()
    {
        // if log import exist and not finished
        $importLog = $this->_lengowOrder->orderIsInError(
            $this->_marketplaceSku,
            $this->_deliveryAddressId,
            'import'
        );
        if ($importLog) {
            $decodedMessage = $this->_dataHelper->decodeLogMessage($importLog['message'], false);
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage(
                    '%1 (created on the %2)',
                    [$decodedMessage, $importLog['created_at']]
                ),
                $this->_logOutput,
                $this->_marketplaceSku
            );
            return false;
        }
        // recovery id if the command has already been imported
        $orderId = $this->_lengowOrder->getOrderIdIfExist(
            $this->_marketplaceSku,
            $this->_marketplace->name,
            $this->_deliveryAddressId
        );
        // update order state if already imported
        if ($orderId) {
            $orderUpdated = $this->_checkAndUpdateOrder($orderId);
            if ($orderUpdated && isset($orderUpdated['update'])) {
                return $this->_returnResult('update', $orderUpdated['order_lengow_id'], $orderId);
            }
            if (!$this->_isReimported) {
                return false;
            }
        }
        // checks if an external id already exists
        $orderMagentoId = $this->_checkExternalIds($this->_orderData->merchant_order_id);
        if ($orderMagentoId && !$this->_preprodMode && !$this->_isReimported) {
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage(
                    'already imported in Magento with the order ID %1',
                    [$orderMagentoId]
                ),
                $this->_logOutput,
                $this->_marketplaceSku
            );
            return false;
        }
        // get a record in the lengow order table
        $this->_orderLengowId = $this->_lengowOrder->getLengowOrderId(
            $this->_marketplaceSku,
            $this->_deliveryAddressId
        );
        // if order is cancelled or new -> skip
        if (!$this->_importHelper->checkState($this->_orderStateMarketplace, $this->_marketplace)) {
            $orderProcessState = $this->_lengowOrder->getOrderProcessState($this->_orderStateLengow);
            // check and complete an order not imported if it is canceled or refunded
            if ($this->_orderLengowId && $orderProcessState === LengowOrder::PROCESS_STATE_FINISH) {
                $this->_orderErrorFactory->create()->finishOrderErrors($this->_orderLengowId);
                $orderLengow = $this->_lengowOrderFactory->create()->load((int)$this->_orderLengowId);
                $orderLengow->updateOrder(
                    [
                        'is_in_error' => 0,
                        'order_lengow_state' => $this->_orderStateLengow,
                        'order_process_state' => $orderProcessState
                    ]
                );

            }
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage(
                    'current order status %1 means it is not possible to import the order to the marketplace %2',
                    [
                        $this->_orderStateMarketplace,
                        $this->_marketplace->name
                    ]
                ),
                $this->_logOutput,
                $this->_marketplaceSku
            );
            return false;
        }
        // create a new record in lengow order table if not exist
        if (!$this->_orderLengowId) {
            // created a record in the lengow order table
            if (!$this->_createLengowOrder()) {
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage('WARNING! Order could NOT be saved in Lengow orders table'),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
                return false;
            } else {
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage('order saved in Lengow orders table'),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
            }
        }
        // load lengow order
        $orderFactory = $this->_lengowOrderFactory->create();
        $orderLengow = $orderFactory->load((int)$this->_orderLengowId);
        // checks if the required order data is present
        if (!$this->_checkOrderData()) {
            return $this->_returnResult('error', $this->_orderLengowId);
        }
        // get order amount and load processing fees and shipping cost
        $this->_orderAmount = $this->_getOrderAmount();
        // load tracking data
        $this->_loadTrackingData();
        // get customer name and email
        $customerName = $this->_getCustomerName();
        $customerEmail = (!is_null($this->_orderData->billing_address->email)
            ? (string)$this->_orderData->billing_address->email
            : (string)$this->_packageData->delivery->email
        );
        // update Lengow order with new informations
        $orderLengow->updateOrder(
            [
                'currency' => $this->_orderData->currency->iso_a3,
                'total_paid' => $this->_orderAmount,
                'order_item' => $this->_orderItems,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'commission' => (float)$this->_orderData->commission,
                'carrier' => $this->_carrierName,
                'carrier_method' => $this->_carrierMethod,
                'carrier_tracking' => $this->_trackingNumber,
                'carrier_id_relay' => $this->_relayId,
                'sent_marketplace' => $this->_shippedByMp,
                'delivery_country_iso' => $this->_packageData->delivery->common_country_iso_a2,
                'order_lengow_state' => $this->_orderStateLengow
            ]
        );
        // try to import order
        try {
            // check if the order is shipped by marketplace
            if ($this->_shippedByMp) {
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage('order shipped by %1', [$this->_marketplace->name]),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
                if (!$this->_configHelper->get('import_ship_mp_enabled', $this->_storeId)) {
                    $orderLengow->updateOrder(
                        [
                            'is_in_error' => 0,
                            'order_process_state' => 2,
                            'extra' => json_encode($this->_orderData)
                        ]
                    );
                    return false;
                }
            }
            // Create or Update customer with addresses
            $customer = $this->_lengowCustomer->createCustomer(
                $this->_orderData,
                $this->_packageData->delivery,
                $this->_storeId,
                $this->_marketplaceSku,
                $this->_logOutput
            );
            // Create Magento Quote
            $quote = $this->_createQuote($customer);
            // Create Magento order
            $order = $this->_makeOrder($quote);
            // If order is successfully imported
            if ($order) {
                // Save order line id in lengow_order_line table
                $orderLineSaved = $this->_saveLengowOrderLine($order, $quote);
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage(
                        'save order lines product: %1',
                        [$orderLineSaved]
                    ),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage(
                        'order successfully imported (ORDER ID %1)',
                        [$order->getIncrementId()]
                    ),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
                // Update state to shipped
                if ($this->_orderStateLengow == 'shipped' || $this->_orderStateLengow == 'closed') {
                    $this->_lengowOrder->toShip(
                        $order,
                        $this->_carrierName,
                        $this->_carrierMethod,
                        $this->_trackingNumber
                    );
                    $this->_dataHelper->log(
                        'Import',
                        $this->_dataHelper->setLogMessage(
                            "order's status has been updated to %1",
                            ['Complete']
                        ),
                        $this->_logOutput,
                        $this->_marketplaceSku
                    );
                }
                // Update Lengow order record
                $orderLengow->updateOrder(
                    [
                        'order_id' => $order->getId(),
                        'order_sku' => $order->getIncrementId(),
                        'order_process_state' => $this->_lengowOrder->getOrderProcessState($this->_orderStateLengow),
                        'extra' => json_encode($this->_orderData),
                        'order_lengow_state' => $this->_orderStateLengow,
                        'is_in_error' => 0,
                        'is_reimported' => 0,
                    ]
                );
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage('order updated in Lengow orders table'),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
            } else {
                throw new LengowException(
                    $this->_dataHelper->setLogMessage('order could not be saved')
                );
            }
            // add quantity back for re-import order and order shipped by marketplace
            if ($this->_isReimported
                || ($this->_shippedByMp && !$this->_configHelper->get('import_stock_ship_mp', $this->_storeId))
            ) {
                if ($this->_isReimported) {
                    $logMessage = $this->_dataHelper->setLogMessage(
                        'adding quantity back to stock count (order is re-imported)'
                    );
                } else {
                    $logMessage = $this->_dataHelper->setLogMessage(
                        'adding quantity back to stock count (order shipped by marketplace)'
                    );
                }
                $this->_dataHelper->log('Import', $logMessage, $this->_logOutput, $this->_marketplaceSku);
                $this->_addQuantityBack($quote);
            }
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (\Exception $e) {
            $errorMessage = 'Magento error: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            $orderError = $this->_orderErrorFactory->create();
            $orderError->createOrderError(
                [
                    'order_lengow_id' => $this->_orderLengowId,
                    'message' => $errorMessage,
                    'type' => 'import'
                ]
            );
            $decodedMessage = $this->_dataHelper->decodeLogMessage($errorMessage, false);
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage(
                    'import order failed - %1',
                    [$decodedMessage]
                ),
                $this->_logOutput,
                $this->_marketplaceSku
            );
            $orderLengow->updateOrder(
                [
                    'extra' => json_encode($this->_orderData),
                    'order_lengow_state' => $this->_orderStateLengow,
                ]
            );
            unset($orderError);
            return $this->_returnResult('error', $this->_orderLengowId);
        }
        return $this->_returnResult('new', $this->_orderLengowId, isset($order) ? $order->getId() : null);
    }

    /**
     * Get tracking data and update Lengow order record
     */
    protected function _loadTrackingData()
    {
        $trackings = $this->_packageData->delivery->trackings;
        if (count($trackings) > 0) {
            $this->_carrierName = !is_null($trackings[0]->carrier) ? (string)$trackings[0]->carrier : null;
            $this->_carrierMethod = !is_null($trackings[0]->method) ? (string)$trackings[0]->method : null;
            $this->_trackingNumber = !is_null($trackings[0]->number) ? (string)$trackings[0]->number : null;
            $this->_relayId = !is_null($trackings[0]->relay->id) ? (string)$trackings[0]->relay->id : null;
            if (!is_null($trackings[0]->is_delivered_by_marketplace) && $trackings[0]->is_delivered_by_marketplace) {
                $this->_shippedByMp = true;
            }
        }
    }

    /**
     * Get order amount
     *
     * @return float
     */
    protected function _getOrderAmount()
    {
        $this->_processingFee = (float)$this->_orderData->processing_fee;
        $this->_shippingCost = (float)$this->_orderData->shipping;
        // rewrite processing fees and shipping cost
        if ($this->_firstPackage == false) {
            $this->_processingFee = 0;
            $this->_shippingCost = 0;
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage('rewrite amount without processing fee'),
                $this->_logOutput,
                $this->_marketplaceSku
            );
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage('rewrite amount without shipping cost'),
                $this->_logOutput,
                $this->_marketplaceSku
            );
        }
        // get total amount and the number of items
        $nbItems = 0;
        $totalAmount = 0;
        foreach ($this->_packageData->cart as $product) {
            // check whether the product is canceled for amount
            if (!is_null($product->marketplace_status)) {
                $stateProduct = $this->_marketplace->getStateLengow((string)$product->marketplace_status);
                if ($stateProduct == 'canceled' || $stateProduct == 'refused') {
                    continue;
                }
            }
            $nbItems += (int)$product->quantity;
            $totalAmount += (float)$product->amount;
        }
        $this->_orderItems = $nbItems;
        $orderAmount = $totalAmount + $this->_processingFee + $this->_shippingCost;
        return $orderAmount;
    }

    /**
     * Add quantity back to stock
     *
     * @param Quote $quote Lengow quote instance
     *
     * @return Importorder
     */
    protected function _addQuantityBack($quote)
    {
        $lengowProducts = $quote->getLengowProducts();
        foreach ($lengowProducts as $productId => $product) {
            $this->_stockManagement->backItemQty($productId, $product['quantity']);
        }
        return $this;
    }

    /**
     * Check the command and updates data if necessary
     *
     * @param integer $orderId Magento order id
     *
     * @return array|false
     */
    protected function _checkAndUpdateOrder($orderId)
    {
        $order = $this->_orderRepository->get($orderId);
        $this->_dataHelper->log(
            'Import',
            $this->_dataHelper->setLogMessage('order already imported (ORDER ID %1)', [$order->getIncrementId()]),
            $this->_logOutput,
            $this->_marketplaceSku
        );
        $orderLengowId = $this->_lengowOrder->getLengowOrderIdWithOrderId($orderId);
        $lengowOrder = $this->_lengowOrderFactory->create()->load($orderLengowId);
        $result = ['order_lengow_id' => $lengowOrder->getId()];
        // Lengow -> Cancel and reimport order
        if ($lengowOrder->getData('is_reimported') == 1) {
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage(
                    'order ready to be re-imported (ORDER ID %1)',
                    [$order->getIncrementId()]
                ),
                $this->_logOutput,
                $this->_marketplaceSku
            );
            $this->_isReimported = true;
            return false;
        } else {
            // try to update magento order, lengow order and finish actions if necessary
            $orderUpdated = $this->_lengowOrder->updateState(
                $order,
                $lengowOrder,
                $this->_orderStateLengow,
                $this->_orderData,
                $this->_packageData
            );
            if ($orderUpdated) {
                $result['update'] = true;
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage("order's status has been updated to %1", [$orderUpdated]),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
            }
        }
        unset($order, $lengowOrder);
        return $result;
    }

    /**
     * Checks if an external id already exists
     *
     * @param array $externalIds API external ids
     *
     * @return integer|false
     */
    protected function _checkExternalIds($externalIds)
    {
        $orderMagentoId = false;
        if (!is_null($externalIds) && count($externalIds) > 0) {
            foreach ($externalIds as $externalId) {
                $lineId = $this->_lengowOrder->getOrderIdWithDeliveryAddress(
                    (int)$externalId,
                    (int)$this->_deliveryAddressId
                );
                if ($lineId) {
                    $orderMagentoId = $externalId;
                    break;
                }
            }
        }
        return $orderMagentoId;
    }

    /**
     * Checks if order data are present
     *
     * @return boolean
     */
    protected function _checkOrderData()
    {
        $errorMessages = [];
        if (count($this->_packageData->cart) == 0) {
            $errorMessages[] = $this->_dataHelper->setLogMessage('Lengow error: no product in the order');
        }
        if (!isset($this->_orderData->currency->iso_a3)) {
            $errorMessages[] = $this->_dataHelper->setLogMessage('Lengow error: no currency in the order');
        }
        if ($this->_orderData->total_order == -1) {
            $errorMessages[] = $this->_dataHelper->setLogMessage(
                'Lengow error: no exchange rates available for order prices'
            );
        }
        if (is_null($this->_orderData->billing_address)) {
            $errorMessages[] = $this->_dataHelper->setLogMessage('Lengow error: no billing address in the order');
        } elseif (is_null($this->_orderData->billing_address->common_country_iso_a2)) {
            $errorMessages[] = $this->_dataHelper->setLogMessage(
                "Lengow error: billing address doesn't contain the country"
            );
        }
        if (is_null($this->_packageData->delivery->common_country_iso_a2)) {
            $errorMessages[] = $this->_dataHelper->setLogMessage(
                "Lengow error: delivery address doesn't contain the country"
            );
        }
        if (count($errorMessages) > 0) {
            foreach ($errorMessages as $errorMessage) {
                $orderError = $this->_orderErrorFactory->create();
                $orderError->createOrderError(
                    [
                        'order_lengow_id' => $this->_orderLengowId,
                        'message' => $errorMessage,
                        'type' => 'import'
                    ]
                );
                $decodedMessage = $this->_dataHelper->decodeLogMessage($errorMessage, false);
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage(
                        'import order failed - %1',
                        [$decodedMessage]
                    ),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
                unset($orderError);
            };
            return false;
        }
        return true;
    }

    /**
     * Return an array of result for each order
     *
     * @param string $typeResult Type of result (new, update, error)
     * @param integer $orderLengowId Lengow order id
     * @param integer $orderId Magento order id
     *
     * @return array
     */
    protected function _returnResult($typeResult, $orderLengowId, $orderId = null)
    {
        $result = [
            'order_id' => $orderId,
            'order_lengow_id' => $orderLengowId,
            'marketplace_sku' => $this->_marketplaceSku,
            'marketplace_name' => (string)$this->_marketplace->name,
            'lengow_state' => $this->_orderStateLengow,
            'order_new' => $typeResult == 'new' ? true : false,
            'order_update' => $typeResult == 'update' ? true : false,
            'order_error' => $typeResult == 'error' ? true : false
        ];
        return $result;
    }

    /**
     * Get customer name
     *
     * @return string
     */
    protected function _getCustomerName()
    {
        $firstname = (string)$this->_orderData->billing_address->first_name;
        $lastname = (string)$this->_orderData->billing_address->last_name;
        $firstname = ucfirst(strtolower($firstname));
        $lastname = ucfirst(strtolower($lastname));
        if (empty($firstname) && empty($lastname)) {
            return (string)$this->_orderData->billing_address->full_name;
        } else {
            return $firstname . ' ' . $lastname;
        }
    }

    /**
     * Create quote
     *
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @throws \Exception
     *
     * @return LengowQuoteFactory
     */
    protected function _createQuote(\Magento\Customer\Model\Customer $customer)
    {
        $customerRepo = $this->_customerRepository->getById($customer->getId());
        $quote = $this->_lengowQuoteFactory->create()
            ->setIsMultiShipping(false)
            ->setStore($this->_storeManager->getStore($this->_storeId))
            ->setInventoryProcessed(false);
        // import customer addresses into quote
        // Set billing Address
        $customerBillingAddress = $this->_addressRepository->getById($customerRepo->getDefaultBilling());
        $billingAddress = $this->_quoteAddressFactory->create()
            ->setShouldIgnoreValidation(true)
            ->importCustomerAddressData($customerBillingAddress)
            ->setSaveInAddressBook(0);
        $customerShippingAddress = $this->_addressRepository->getById($customerRepo->getDefaultShipping());
        $shippingAddress = $this->_quoteAddressFactory->create()
            ->setShouldIgnoreValidation(true)
            ->importCustomerAddressData($customerShippingAddress)
            ->setSaveInAddressBook(0)
            ->setSameAsBilling(0);
        $quote->assignCustomerWithAddressChange($customerRepo, $billingAddress, $shippingAddress);
        // check if store include tax (Product and shipping cost)
        $priceIncludeTax = $this->_taxConfig->priceIncludesTax($quote->getStore());
        $shippingIncludeTax = $this->_taxConfig->shippingPriceIncludesTax($quote->getStore());
        // add product in quote
        $quote->addLengowProducts(
            $this->_packageData->cart,
            $this->_marketplace,
            $this->_marketplaceSku,
            $this->_logOutput,
            $priceIncludeTax
        );
        // Get shipping cost with tax
        $shippingCost = $this->_processingFee + $this->_shippingCost;
        // if shipping cost not include tax -> get shipping cost without tax
        if (!$shippingIncludeTax) {
            $shippingTaxClass = $this->_scopeConfig->getValue(
                TaxConfig::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
                'store',
                $quote->getStore()
            );
            $taxRate = (float)$this->_taxCalculation->getCalculatedRate(
                $shippingTaxClass,
                $customer->getId(),
                $quote->getStore()
            );
            $taxShippingCost = (float)$this->_calculation->calcTaxAmount($shippingCost, $taxRate, true);
            $shippingCost = $shippingCost - $taxShippingCost;
        }
        $quoteShippingAddress = $quote->getShippingAddress();
        // update shipping rates for current order
        $quoteShippingAddress->setCollectShippingRates(true);
        $quoteShippingAddress->setTotalsCollectedFlag(false)->collectShippingRates();
        $rates = $quoteShippingAddress->getShippingRatesCollection();
        $shippingMethod = $this->_updateRates($rates, $shippingCost);
        // set shipping price and shipping method for current order
        $quoteShippingAddress
            ->setShippingPrice($shippingCost)
            ->setShippingMethod($shippingMethod);
        // get payment informations
        $paymentInfo = '';
        if (count($this->_orderData->payments) > 0) {
            $payment = $this->_orderData->payments[0];
            $paymentInfo .= ' - ' . (string)$payment->type;
            if (isset($payment->payment_terms->external_transaction_id)) {
                $paymentInfo .= ' - ' . (string)$payment->payment_terms->external_transaction_id;
            }
        }
        // set payment method lengow
        $quote->getPayment()->setMethod('lengow')->setAdditionnalInformation(
            ['marketplace' => (string)$this->_orderData->marketplace . $paymentInfo]
        );
        $quote->collectTotals()->save();
        // stop order creation when a quote is empty
        if (!$quote->getAllVisibleItems()) {
            $quote->setIsActive(false);
            throw new LengowException(
                $this->_dataHelper->setLogMessage('quote does not contain any valid products')
            );
        }
        $quote->save();
        return $quote;
    }

    /**
     * Create order
     *
     * @param Quote $quote Lengow quote instance
     *
     * @throws \Exception|LengowException order failed with quote
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function _makeOrder(Quote $quote)
    {
        $additionalDatas = [
            'from_lengow' => true,
            'global_currency_code' => (string)$this->_orderData->currency->iso_a3,
            'base_currency_code' => (string)$this->_orderData->currency->iso_a3,
            'store_currency_code' => (string)$this->_orderData->currency->iso_a3,
            'order_currency_code' => (string)$this->_orderData->currency->iso_a3
        ];
        try {
            $order = $this->_quoteManagement->submit($quote, $additionalDatas);
        } catch (\Exception $e) {
            // try to generate order with quote factory for "Cart does not contain item" Magento bug
            $magentoQuote = $this->_quoteMagentoFactory->create()->load($quote->getId());
            $order = $this->_quoteManagement->submit($magentoQuote, $additionalDatas);
        }
        if (!$order) {
            throw new LengowException(
                $this->_dataHelper->setLogMessage('unable to create order based on given quote')
            );
        }
        $order->addData($additionalDatas);
        // modify order dates to use actual dates
        // Get all params to create order
        if (!is_null($this->_orderData->marketplace_order_date)) {
            $orderDate = (string)$this->_orderData->marketplace_order_date;
        } else {
            $orderDate = (string)$this->_orderData->imported_at;
        }
        $order->setCreatedAt($this->_dateTime->date('Y-m-d H:i:s', strtotime($orderDate)));
        $order->setUpdatedAt($this->_dateTime->date('Y-m-d H:i:s', strtotime($orderDate)));
        $order->save();
        // generate invoice for order
        if ($order->canInvoice()) {
            $this->_lengowOrder->toInvoice($order);
        }
        $carrierName = $this->_carrierName;
        if (is_null($carrierName) || $carrierName == 'None') {
            $carrierName = $this->_carrierMethod;
        }
        $order->setShippingDescription(
            $order->getShippingDescription() . ' [marketplace shipping method : ' . $carrierName . ']'
        );
        $order->save();
        return $order;
    }

    /**
     * Update Rates with shipping cost
     *
     * @param \Magento\Quote\Model\Quote\Address\Rate $rates Magento rates
     * @param float $shippingCost shipping cost
     * @param string $shippingMethod Magento shipping method
     * @param boolean $first stop recursive effect
     *
     * @return boolean
     */
    protected function _updateRates($rates, $shippingCost, $shippingMethod = null, $first = true)
    {
        if (!$shippingMethod) {
            $shippingMethod = $this->_configHelper->get('import_shipping_method', $this->_storeId);
        }
        if (empty($shippingMethod)) {
            $shippingMethod = 'lengow_lengow';
        }
        foreach ($rates as &$rate) {
            // make sure the chosen shipping method is correct
            if ($rate->getCode() == $shippingMethod) {
                if ($rate->getPrice() != $shippingCost) {
                    $rate->setPrice($shippingCost);
                    $rate->setCost($shippingCost);
                }
                return $rate->getCode();
            }
        }
        // stop recursive effect
        if (!$first) {
            return 'lengow_lengow';
        }
        // get lengow shipping method if selected shipping method is unavailable
        $this->_dataHelper->log(
            'Import',
            $this->_dataHelper->setLogMessage(
                'the chosen shipping method is not available for this order. Lengow has assigned a shipping method'
            ),
            $this->_logOutput,
            $this->_marketplaceSku
        );
        return $this->_updateRates($rates, $shippingCost, 'lengow_lengow', false);
    }

    /**
     * Create a order in lengow orders table
     *
     * @return boolean
     */
    protected function _createLengowOrder()
    {
        // Get all params to create order
        if (!is_null($this->_orderData->marketplace_order_date)) {
            $orderDate = (string)$this->_orderData->marketplace_order_date;
        } else {
            $orderDate = (string)$this->_orderData->imported_at;
        }
        $params = [
            'store_id' => (int)$this->_storeId,
            'marketplace_sku' => $this->_marketplaceSku,
            'marketplace_name' => strtolower((string)$this->_orderData->marketplace),
            'marketplace_label' => (string)$this->_marketplaceLabel,
            'delivery_address_id' => (int)$this->_deliveryAddressId,
            'order_lengow_state' => $this->_orderStateLengow,
            'order_date' => $this->_dateTime->date('Y-m-d H:i:s', strtotime($orderDate)),
            'is_in_error' => 1
        ];
        if (isset($this->_orderData->comments) && is_array($this->_orderData->comments)) {
            $params['message'] = join(',', $this->_orderData->comments);
        } else {
            $params['message'] = (string)$this->_orderData->comments;
        }
        // Create lengow order
        $lengowOrder = $this->_lengowOrderFactory->create();
        $lengowOrder->createOrder($params);
        // Get lengow order id
        $this->_orderLengowId = $lengowOrder->getLengowOrderId(
            $this->_marketplaceSku,
            $this->_deliveryAddressId
        );
        if (!$this->_orderLengowId) {
            return false;
        }
        return true;
    }

    /**
     * Save order line in lengow orders line table
     *
     * @param Order $order Magento order instance
     * @param Quote $quote Lengow quote instance
     *
     * @return string
     */
    protected function _saveLengowOrderLine($order, $quote)
    {
        $orderLineSaved = false;
        $lengowProducts = $quote->getLengowProducts();
        foreach ($lengowProducts as $productId => $product) {
            foreach ($product['order_line_ids'] as $idOrderLine) {
                $orderline = $this->_lengowOrderLineFactory->create();
                $orderline->createOrderLine(
                    [
                        'order_id' => (int)$order->getId(),
                        'product_id' => $productId,
                        'order_line_id' => $idOrderLine
                    ]
                );
                $orderLineSaved .= !$orderLineSaved ? $idOrderLine : ' / ' . $idOrderLine;
            }
        }
        return $orderLineSaved;
    }
}
