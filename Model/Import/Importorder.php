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

use Lengow\Connector\Model\Import;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\StockManagementInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\QuoteFactory as MagentoQuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\Quote\AddressFactory as QuoteAddressFactory;
use Magento\Quote\Model\Quote\Address\Rate as QuoteAddressRate;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\Tax\Model\TaxCalculation;
use Magento\Tax\Model\Calculation;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Model\Exception as LengowException;
use Lengow\Connector\Model\Import\Customer as LengowCustomer;
use Lengow\Connector\Model\Import\Marketplace as LengowMarketplace;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;
use Lengow\Connector\Model\Import\OrdererrorFactory as LengowOrderErrorFactory;
use Lengow\Connector\Model\Import\OrderlineFactory as LengowOrderLineFactory;
use Lengow\Connector\Model\Import\QuoteFactory as LengowQuoteFactory;

/**
 * Model import importorder
 */
class Importorder extends AbstractModel
{
    /**
     * @var string result for order imported
     */
    const RESULT_NEW = 'new';

    /**
     * @var string result for order updated
     */
    const RESULT_UPDATE = 'update';

    /**
     * @var string result for order in error
     */
    const RESULT_ERROR = 'error';

    /**
     * @var QuoteFactory Magento quote factory instance
     */
    protected $_quoteMagentoFactory;

    /**
     * @var OrderRepositoryInterface Magento order repository instance
     */
    protected $_orderRepository;

    /**
     * @var AddressRepositoryInterface Magento address repository instance
     */
    protected $_addressRepository;

    /**
     * @var CustomerRepositoryInterface Magento customer repository instance
     */
    protected $_customerRepository;

    /**
     * @var TaxConfig Magento Tax configuration instance
     */
    protected $_taxConfig;

    /**
     * @var ScopeConfigInterface Magento cope config instance
     */
    protected $_scopeConfig;

    /**
     * @var StoreManagerInterface Magento store manager instance
     */
    protected $_storeManager;

    /**
     * @var QuoteAddressFactory Magento quote address factory instance
     */
    protected $_quoteAddressFactory;

    /**
     * @var TaxCalculation Magento tax calculation instance
     */
    protected $_taxCalculation;

    /**
     * @var Calculation Magento calculation instance
     */
    protected $_calculation;

    /**
     * @var QuoteManagement Magento quote management instance
     */
    protected $_quoteManagement;

    /**
     * @var DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var TimezoneInterface Magento datetime timezone instance
     */
    protected $_timezone;

    /**
     * @var ProductFactory Magento product factory instance
     */
    protected $_productFactory;

    /**
     * @var StockManagementInterface Magento stock management instance
     */
    protected $_stockManagement;

    /**
     * @var LengowOrder Lengow order instance
     */
    protected $_lengowOrder;

    /**
     * @var LengowOrderFactory Lengow order factory instance
     */
    protected $_lengowOrderFactory;

    /**
     * @var LengowOrderErrorFactory Lengow ordererror Factory instance
     */
    protected $_orderErrorFactory;

    /**
     * @var LengowCustomer Lengow customer instance
     */
    protected $_lengowCustomer;

    /**
     * @var LengowQuoteFactory Lengow quote instance
     */
    protected $_lengowQuoteFactory;

    /**
     * @var LengowOrderLineFactory Lengow orderline factory instance
     */
    protected $_lengowOrderLineFactory;

    /**
     * @var ImportHelper Lengow import helper instance
     */
    protected $_importHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var ConfigHelper Lengow config helper instance
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
     * @var integer|null Magento store id
     */
    protected $_storeId = null;

    /**
     * @var boolean use debug mode
     */
    protected $_debugMode = false;

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
     * @var boolean import one order var from lengow import
     */
    protected $_importOneOrder;

    /**
     * @var LengowMarketplace Lengow marketplace instance
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
     * @var string|null carrier name
     */
    protected $_carrierName = null;

    /**
     * @var string|null carrier method
     */
    protected $_carrierMethod = null;

    /**
     * @var boolean order shipped by marketplace
     */
    protected $_shippedByMp = false;

    /**
     * @var string|null carrier tracking number
     */
    protected $_trackingNumber = null;

    /**
     * @var string|null carrier relay id
     */
    protected $_relayId = null;

    /**
     * Constructor
     *
     * @param Context $context Magento context instance
     * @param Registry $registry Magento registry instance
     * @param OrderRepositoryInterface $orderRepository Magento order instance
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param TaxConfig $taxConfig Tax configuration object
     * @param ScopeConfigInterface $scopeConfig Scope config interface
     * @param StoreManagerInterface $storeManager Magento store manager
     * @param QuoteAddressFactory $quoteAddressFactory Magento quote factory address instance
     * @param TaxCalculation $taxCalculation tax calculation interface
     * @param Calculation $calculation calculation
     * @param QuoteManagement $quoteManagement Magen
     * @param DateTime $dateTime Magento datetime instance
     * @param TimezoneInterface $timezone Magento datetime timezone instance
     * @param ProductFactory $productFactory Magento product factory
     * @param StockManagementInterface $stockManagement Magento stock management instance
     * @param MagentoQuoteFactory $quoteMagentoFactory Magento quote factory instance
     * @param LengowOrder $lengowOrder Lengow order instance
     * @param LengowOrderFactory $lengowOrderFactory Lengow order instance
     * @param LengowOrderErrorFactory $orderErrorFactory Lengow orderErrorFactory instance
     * @param LengowCustomer $lengowCustomer Lengow customer instance
     * @param LengowQuoteFactory $lengowQuoteFactory Lengow quote instance
     * @param LengowOrderLineFactory $lengowOrderLineFactory Lengow orderline factory instance
     * @param ImportHelper $importHelper Lengow import helper instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     */
    public function __construct(
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
        TimezoneInterface $timezone,
        ProductFactory $productFactory,
        StockManagementInterface $stockManagement,
        MagentoQuoteFactory $quoteMagentoFactory,
        LengowOrder $lengowOrder,
        LengowOrderFactory $lengowOrderFactory,
        LengowOrderErrorFactory $orderErrorFactory,
        LengowCustomer $lengowCustomer,
        LengowQuoteFactory $lengowQuoteFactory,
        LengowOrderLineFactory $lengowOrderLineFactory,
        ImportHelper $importHelper,
        DataHelper $dataHelper,
        ConfigHelper $configHelper
    )
    {
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
        $this->_timezone = $timezone;
        $this->_productFactory = $productFactory;
        $this->_stockManagement = $stockManagement;
        $this->_quoteMagentoFactory = $quoteMagentoFactory;
        $this->_lengowOrder = $lengowOrder;
        $this->_lengowOrderFactory = $lengowOrderFactory;
        $this->_orderErrorFactory = $orderErrorFactory;
        $this->_lengowCustomer = $lengowCustomer;
        $this->_lengowQuoteFactory = $lengowQuoteFactory;
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
        $this->_debugMode = $params['debug_mode'];
        $this->_logOutput = $params['log_output'];
        $this->_marketplaceSku = $params['marketplace_sku'];
        $this->_deliveryAddressId = $params['delivery_address_id'];
        $this->_orderData = $params['order_data'];
        $this->_packageData = $params['package_data'];
        $this->_firstPackage = $params['first_package'];
        $this->_importOneOrder = $params['import_one_order'];
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
            $dateMessage = $this->_timezone->date(strtotime($importLog['created_at']))->format('Y-m-d H:i:s');
            $decodedMessage = $this->_dataHelper->decodeLogMessage($importLog['message'], false);
            $this->_dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->_dataHelper->setLogMessage(
                    '%1 (created on the %2)',
                    [
                        $decodedMessage,
                        $dateMessage,
                    ]
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
                return $this->_returnResult(self::RESULT_UPDATE, $orderUpdated['order_lengow_id'], $orderId);
            }
            if (!$this->_isReimported) {
                return false;
            }
        }
        if (!$this->_importOneOrder) {
            // skip import if the order is anonymized
            if ($this->_orderData->anonymized) {
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->_dataHelper->setLogMessage('Order is anonymized and has not been imported'),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
                return false;
            }
            // skip import if the order is older than 3 months
            $dateTimeOrder = new \DateTime($this->_orderData->marketplace_order_date);
            $interval = $dateTimeOrder->diff(new \DateTime());
            $monthInterval = $interval->m + ($interval->y * 12);
            if ($monthInterval >= Import::MONTH_INTERVAL_TIME) {
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->_dataHelper->setLogMessage('Order is older than 3 months and has not been imported'),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
                return false;
            }
        }
        // checks if an external id already exists
        $orderMagentoId = $this->_checkExternalIds($this->_orderData->merchant_order_id);
        if ($orderMagentoId && !$this->_debugMode && !$this->_isReimported) {
            $this->_dataHelper->log(
                DataHelper::CODE_IMPORT,
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
            $this->_marketplace->name,
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
                        'order_process_state' => $orderProcessState,
                    ]
                );

            }
            $this->_dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->_dataHelper->setLogMessage(
                    'current order status %1 means it is not possible to import the order to the marketplace %2',
                    [
                        $this->_orderStateMarketplace,
                        $this->_marketplace->name,
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
                    DataHelper::CODE_IMPORT,
                    $this->_dataHelper->setLogMessage('WARNING! Order could NOT be saved in Lengow orders table'),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
                return false;
            } else {
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
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
            return $this->_returnResult(self::RESULT_ERROR, $this->_orderLengowId);
        }
        // get order amount and load processing fees and shipping cost
        $this->_orderAmount = $this->_getOrderAmount();
        // load tracking data
        $this->_loadTrackingData();
        // get customer name and email
        $customerName = $this->_getCustomerName();
        $customerEmail = $this->_orderData->billing_address->email !== null
            ? (string)$this->_orderData->billing_address->email
            : (string)$this->_packageData->delivery->email;
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
                'order_lengow_state' => $this->_orderStateLengow,
                'extra' => json_encode($this->_orderData),
            ]
        );
        // try to import order
        try {
            // check if the order is shipped by marketplace
            if ($this->_shippedByMp) {
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->_dataHelper->setLogMessage('order shipped by %1', [$this->_marketplace->name]),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
                if (!$this->_configHelper->get('import_ship_mp_enabled', $this->_storeId)) {
                    $orderLengow->updateOrder(
                        [
                            'is_in_error' => 0,
                            'order_process_state' => 2,
                        ]
                    );
                    return false;
                }
            }
            // create or update customer with addresses
            $customer = $this->_lengowCustomer->createCustomer(
                $this->_orderData,
                $this->_packageData->delivery,
                $this->_storeId,
                $this->_marketplaceSku,
                $this->_logOutput
            );
            // create Magento Quote
            $quote = $this->_createQuote($customer);
            // create Magento order
            $order = $this->_makeOrder($quote, $orderLengow);
            // if order is successfully imported
            if ($order) {
                // save order line id in lengow_order_line table
                $orderLineSaved = $this->_saveLengowOrderLine($order, $quote);
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->_dataHelper->setLogMessage(
                        'save order lines product: %1',
                        [$orderLineSaved]
                    ),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $this->_dataHelper->setLogMessage(
                        'order successfully imported (ORDER ID %1)',
                        [$order->getIncrementId()]
                    ),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
                // update state to shipped
                if ($this->_orderStateLengow === LengowOrder::STATE_SHIPPED
                    || $this->_orderStateLengow === LengowOrder::STATE_CLOSED
                ) {
                    $this->_lengowOrder->toShip(
                        $order,
                        $this->_carrierName,
                        $this->_carrierMethod,
                        $this->_trackingNumber
                    );
                    $this->_dataHelper->log(
                        DataHelper::CODE_IMPORT,
                        $this->_dataHelper->setLogMessage(
                            "order's status has been updated to %1",
                            ['Complete']
                        ),
                        $this->_logOutput,
                        $this->_marketplaceSku
                    );
                }
            } else {
                throw new LengowException($this->_dataHelper->setLogMessage('order could not be saved'));
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
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
                    $logMessage,
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
                $this->_addQuantityBack($quote);
            }
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (\Exception $e) {
            $errorMessage = 'Magento error: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            if ((bool)$orderLengow->getData('is_in_error')) {
                $orderError = $this->_orderErrorFactory->create();
                $orderError->createOrderError(
                    [
                        'order_lengow_id' => $this->_orderLengowId,
                        'message' => $errorMessage,
                        'type' => 'import',
                    ]
                );
                unset($orderError);
            }
            $decodedMessage = $this->_dataHelper->decodeLogMessage($errorMessage, false);
            $this->_dataHelper->log(
                DataHelper::CODE_IMPORT,
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
            return $this->_returnResult(self::RESULT_ERROR, $this->_orderLengowId);
        }
        return $this->_returnResult(self::RESULT_NEW, $this->_orderLengowId, isset($order) ? $order->getId() : null);
    }

    /**
     * Get tracking data and update Lengow order record
     */
    protected function _loadTrackingData()
    {
        $tracks = $this->_packageData->delivery->trackings;
        if (!empty($tracks)) {
            $tracking = $tracks[0];
            $this->_carrierName = $tracking->carrier !== null ? (string)$tracking->carrier : null;
            $this->_carrierMethod = $tracking->method !== null ? (string)$tracking->method : null;
            $this->_trackingNumber = $tracking->number !== null ? (string)$tracking->number : null;
            $this->_relayId = $tracking->relay->id !== null ? (string)$tracking->relay->id : null;
            if ($tracking->is_delivered_by_marketplace !== null && $tracking->is_delivered_by_marketplace) {
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
        if (!$this->_firstPackage) {
            $this->_processingFee = 0;
            $this->_shippingCost = 0;
            $this->_dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->_dataHelper->setLogMessage('rewrite amount without processing fee'),
                $this->_logOutput,
                $this->_marketplaceSku
            );
            $this->_dataHelper->log(
                DataHelper::CODE_IMPORT,
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
            if ($product->marketplace_status !== null) {
                $stateProduct = $this->_marketplace->getStateLengow((string)$product->marketplace_status);
                if ($stateProduct === LengowOrder::STATE_CANCELED || $stateProduct === LengowOrder::STATE_REFUSED) {
                    continue;
                }
            }
            $nbItems += (int)$product->quantity;
            $totalAmount += (float)$product->amount;
        }
        $this->_orderItems = $nbItems;
        return $totalAmount + $this->_processingFee + $this->_shippingCost;
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
            $this->_stockManagement->backItemQty($productId, $product['quantity'], $this->_storeId);
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
            DataHelper::CODE_IMPORT,
            $this->_dataHelper->setLogMessage('order already imported (ORDER ID %1)', [$order->getIncrementId()]),
            $this->_logOutput,
            $this->_marketplaceSku
        );
        $orderLengowId = $this->_lengowOrder->getLengowOrderIdWithOrderId($orderId);
        $lengowOrder = $this->_lengowOrderFactory->create()->load($orderLengowId);
        $result = ['order_lengow_id' => $lengowOrder->getId()];
        // Lengow -> Cancel and reimport order
        if ((bool)$lengowOrder->getData('is_reimported')) {
            $this->_dataHelper->log(
                DataHelper::CODE_IMPORT,
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
                $this->_packageData
            );
            if ($orderUpdated) {
                $result['update'] = true;
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
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
        if ($externalIds !== null && !empty($externalIds)) {
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
        if (empty($this->_packageData->cart)) {
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
        if ($this->_orderData->billing_address === null) {
            $errorMessages[] = $this->_dataHelper->setLogMessage('Lengow error: no billing address in the order');
        } elseif ($this->_orderData->billing_address->common_country_iso_a2 === null) {
            $errorMessages[] = $this->_dataHelper->setLogMessage(
                "Lengow error: billing address doesn't contain the country"
            );
        }
        if ($this->_packageData->delivery->common_country_iso_a2 === null) {
            $errorMessages[] = $this->_dataHelper->setLogMessage(
                "Lengow error: delivery address doesn't contain the country"
            );
        }
        if (!empty($errorMessages)) {
            foreach ($errorMessages as $errorMessage) {
                $orderError = $this->_orderErrorFactory->create();
                $orderError->createOrderError(
                    [
                        'order_lengow_id' => $this->_orderLengowId,
                        'message' => $errorMessage,
                        'type' => 'import',
                    ]
                );
                $decodedMessage = $this->_dataHelper->decodeLogMessage($errorMessage, false);
                $this->_dataHelper->log(
                    DataHelper::CODE_IMPORT,
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
     * @param integer|null $orderId Magento order id
     *
     * @return array
     */
    protected function _returnResult($typeResult, $orderLengowId, $orderId = null)
    {
        return [
            'order_id' => $orderId,
            'order_lengow_id' => $orderLengowId,
            'marketplace_sku' => $this->_marketplaceSku,
            'marketplace_name' => (string)$this->_marketplace->name,
            'lengow_state' => $this->_orderStateLengow,
            'order_new' => $typeResult === self::RESULT_NEW ? true : false,
            'order_update' => $typeResult === self::RESULT_UPDATE ? true : false,
            'order_error' => $typeResult === self::RESULT_ERROR ? true : false,
        ];
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
     * @param MagentoCustomer $customer
     *
     * @throws \Exception
     *
     * @return LengowQuoteFactory
     */
    protected function _createQuote(MagentoCustomer $customer)
    {
        $customerRepo = $this->_customerRepository->getById($customer->getId());
        $quote = $this->_lengowQuoteFactory->create()
            ->setIsMultiShipping(false)
            ->setStore($this->_storeManager->getStore($this->_storeId))
            ->setInventoryProcessed(false);
        // import customer addresses into quote
        // set billing Address
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
        // get shipping cost with tax
        $shippingCost = $this->_processingFee + $this->_shippingCost;
        // if shipping cost not include tax -> get shipping cost without tax
        if (!$shippingIncludeTax) {
            $shippingTaxClass = $this->_scopeConfig->getValue(
                TaxConfig::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
                'store',
                $quote->getStore()
            );
            $taxRate = $this->_taxCalculation->getCalculatedRate(
                $shippingTaxClass,
                $customer->getId(),
                $quote->getStore()
            );
            $taxShippingCost = $this->_calculation->calcTaxAmount($shippingCost, $taxRate, true);
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
        // get payment data
        $paymentInfo = '';
        if (!empty($this->_orderData->payments)) {
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
     * @param LengowOrder $orderLengow Lengow order instance
     *
     * @throws \Exception|LengowException
     *
     * @return MagentoOrder
     */
    protected function _makeOrder(Quote $quote, $orderLengow)
    {
        $additionalData = [
            'from_lengow' => true,
            'global_currency_code' => (string)$this->_orderData->currency->iso_a3,
            'base_currency_code' => (string)$this->_orderData->currency->iso_a3,
            'store_currency_code' => (string)$this->_orderData->currency->iso_a3,
            'order_currency_code' => (string)$this->_orderData->currency->iso_a3,
        ];
        try {
            $order = $this->_quoteManagement->submit($quote, $additionalData);
        } catch (\Exception $e) {
            // try to generate order with quote factory for "Cart does not contain item" Magento bug
            $magentoQuote = $this->_quoteMagentoFactory->create()->load($quote->getId());
            $order = $this->_quoteManagement->submit($magentoQuote, $additionalData);
        }
        if (!$order) {
            throw new LengowException(
                $this->_dataHelper->setLogMessage('unable to create order based on given quote')
            );
        }
        $order->addData($additionalData);
        // modify order dates to use actual dates
        // get all params to create order
        if ($this->_orderData->marketplace_order_date !== null) {
            $orderDate = (string)$this->_orderData->marketplace_order_date;
        } else {
            $orderDate = (string)$this->_orderData->imported_at;
        }
        $order->setCreatedAt($this->_dateTime->gmtDate('Y-m-d H:i:s', strtotime($orderDate)));
        $order->setUpdatedAt($this->_dateTime->gmtDate('Y-m-d H:i:s', strtotime($orderDate)));
        $order->save();
        // update Lengow order record
        $orderLengow->updateOrder(
            [
                'order_id' => $order->getId(),
                'order_sku' => $order->getIncrementId(),
                'order_process_state' => $this->_lengowOrder->getOrderProcessState($this->_orderStateLengow),
                'order_lengow_state' => $this->_orderStateLengow,
                'is_in_error' => 0,
                'is_reimported' => 0,
            ]
        );
        $this->_dataHelper->log(
            DataHelper::CODE_IMPORT,
            $this->_dataHelper->setLogMessage('order updated in Lengow orders table'),
            $this->_logOutput,
            $this->_marketplaceSku
        );
        // generate invoice for order
        if ($order->canInvoice()) {
            $this->_lengowOrder->toInvoice($order);
        }
        $carrierName = $this->_carrierName;
        if ($carrierName === null || $carrierName === 'None') {
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
     * @param QuoteAddressRate $rates Magento rates
     * @param float $shippingCost shipping cost
     * @param string|null $shippingMethod Magento shipping method
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
            DataHelper::CODE_IMPORT,
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
        // get all params to create order
        $orderDate = $this->_orderData->marketplace_order_date !== null
            ? (string)$this->_orderData->marketplace_order_date
            : (string)$this->_orderData->imported_at;
        $message = (isset($this->_orderData->comments) && is_array($this->_orderData->comments))
            ? join(',', $this->_orderData->comments)
            : (string)$this->_orderData->comments;
        $params = [
            'store_id' => (int)$this->_storeId,
            'marketplace_sku' => $this->_marketplaceSku,
            'marketplace_name' => $this->_marketplace->name,
            'marketplace_label' => (string)$this->_marketplaceLabel,
            'delivery_address_id' => (int)$this->_deliveryAddressId,
            'order_lengow_state' => $this->_orderStateLengow,
            'order_date' => $this->_dateTime->gmtDate('Y-m-d H:i:s', strtotime($orderDate)),
            'message' => $message,
            'is_in_error' => 1,
        ];
        // create lengow order
        $lengowOrder = $this->_lengowOrderFactory->create();
        $lengowOrder->createOrder($params);
        // get lengow order id
        $this->_orderLengowId = $lengowOrder->getLengowOrderId(
            $this->_marketplaceSku,
            $this->_marketplace->name,
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
     * @param MagentoOrder $order Magento order instance
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
                $orderLine = $this->_lengowOrderLineFactory->create();
                $orderLine->createOrderLine(
                    [
                        'order_id' => (int)$order->getId(),
                        'product_id' => $productId,
                        'order_line_id' => $idOrderLine,
                    ]
                );
                $orderLineSaved .= !$orderLineSaved ? $idOrderLine : ' / ' . $idOrderLine;
            }
        }
        return $orderLineSaved;
    }
}
