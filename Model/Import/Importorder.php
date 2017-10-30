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

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
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
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;
use Lengow\Connector\Model\Import\Customer as LengowCustomer;
use Lengow\Connector\Model\Import\Quote as LengowQuote;
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
     * @var StockRegistryInterface
     */
    private $_stockRegistry;

    /**
     * @var \Magento\Shipping\Model\Config Magento shipping config
     */
    protected $_shippingConfig;

    /**
     * @var \Magento\Framework\DB\Transaction Magento transaction
     */
    protected $_transaction;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService Magento invoice service
     */
    protected $_invoiceService;

    /**
     * @var \Magento\Catalog\Model\ProductFactory Magento product factory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $_quoteManagement;

    /**
     * @var \Magento\Sales\Model\Order order magento instance
     */
    protected $_order;

    /**
     * @var \Magento\Tax\Model\Calculation calculation
     */
    protected $_calculation;

    /**
     * @var \Magento\Tax\Model\TaxCalculation tax calculation interface
     */
    protected $_taxCalculation;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface Scope config interface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Tax\Model\Config Tax configuration object
     */
    protected $_taxConfig;

    /**
     * @var \Magento\Quote\Model\Quote\Address
     */
    protected $_quoteAddress;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $_addressRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface Magento store manager
     */
    protected $_storeManager;

    /**
     * @var \Lengow\Connector\Model\Import\Ordererror Lengow ordererror instance
     */
    protected $_orderError;

    /**
     * @var \Lengow\Connector\Model\Import\Order Lengow order instance
     */
    protected $_lengowOrder;

    /**
     * @var \Lengow\Connector\Model\Payment\Lengow Lengow payment instance
     */
    protected $_lengowPayment;

    /**
     * @var \Lengow\Connector\Model\Import\Customer $lengowCustomer Lengow customer instance
     */
    protected $_lengowCustomer;

    /**
     * @var \Lengow\Connector\Model\Import\Quote $lengowQuote Lengow quote instance
     */
    protected $_lengowQuote;

    /**
     * @var \Lengow\Connector\Model\Import\OrderFactory Lengow order instance
     */
    protected $_lengowOrderFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface Magento order repository instance
     */
    protected $_orderRepository;

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
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context Magento context instance
     * @param \Magento\Framework\Registry $registry Magento registry instance
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository Lengow order instance
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Magento store manager
     * @param \Magento\Quote\Model\Quote\Address $quoteAddress Magento quote address
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Tax\Model\Config $taxConfig Tax configuration object
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig Scope config interface
     * @param \Magento\Tax\Model\TaxCalculation $taxCalculation tax calculation interface
     * @param \Magento\Tax\Model\Calculation $calculation calculation
     * @param \Magento\Sales\Model\Order $order order magento instance
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime Magento datetime instance
     * @param \Magento\Catalog\Model\ProductFactory $productFactory Magento product factory
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService Magento invoice service
     * @param \Magento\Framework\DB\Transaction $transaction Magento transaction
     * @param \Magento\Shipping\Model\Config $shippingConfig Magento shipping config
     * @param StockRegistryInterface $stockRegistry
     * @param \Lengow\Connector\Model\Import\Order $lengowOrder Lengow order instance
     * @param \Lengow\Connector\Model\Payment\Lengow $lengowPayment Lengow payment instance
     * @param \Lengow\Connector\Model\Import\OrderFactory $lengowOrderFactory Lengow order instance
     * @param \Lengow\Connector\Model\Import\Ordererror $orderError Lengow orderError instance
     * @param \Lengow\Connector\Model\Import\Customer $lengowCustomer Lengow customer instance
     * @param \Lengow\Connector\Model\Import\Quote $lengowQuote Lengow quote instance
     * @param \Lengow\Connector\Helper\Import $importHelper Lengow import helper instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
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
        QuoteAddress $quoteAddress,
        TaxCalculation $taxCalculation,
        Calculation $calculation,
        MagentoOrder $order,
        QuoteManagement $quoteManagement,
        DateTime $dateTime,
        ProductFactory $productFactory,
        InvoiceService $invoiceService,
        Transaction $transaction,
        ShippingConfig $shippingConfig,
        StockRegistryInterface $stockRegistry,
        LengowPayment $lengowPayment,
        LengowOrder $lengowOrder,
        LengowOrderFactory $lengowOrderFactory,
        Ordererror $orderError,
        LengowCustomer $lengowCustomer,
        LengowQuote $lengowQuote,
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
        $this->_quoteAddress = $quoteAddress;
        $this->_taxCalculation = $taxCalculation;
        $this->_calculation = $calculation;
        $this->_order = $order;
        $this->_quoteManagement = $quoteManagement;
        $this->_dateTime = $dateTime;
        $this->_productFactory = $productFactory;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_shippingConfig = $shippingConfig;
        $this->_stockRegistry = $stockRegistry;
        $this->_lengowOrder = $lengowOrder;
        $this->_lengowPayment = $lengowPayment;
        $this->_lengowOrderFactory = $lengowOrderFactory;
        $this->_orderError = $orderError;
        $this->_lengowCustomer = $lengowCustomer;
        $this->_lengowQuote = $lengowQuote;
        $this->_importHelper = $importHelper;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        parent::__construct($context, $registry);
    }

    /**
     * init a import order
     *
     * @param array $params optional options for load a import order
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
    }

    /**
     * Create or update order
     *
     * @throws LengowException order is empty
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
            $decodedMessage = $this->_dataHelper->decodeLogMessage($importLog['message'], 'en_GB');
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
            //TODO
//            $orderUpdated = $this->_checkAndUpdateOrder($orderId);
//            if ($orderUpdated && isset($orderUpdated['update'])) {
//                return $this->_returnResult('update', $orderUpdated['order_lengow_id'], $orderId);
//            }
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
        // if order is cancelled or new -> skip
        if (false/*!$this->_importHelper->checkState($this->_orderStateMarketplace, $this->_marketplace)*/) {
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage(
                    'current order status [%1] means it is not possible to import the order to the marketplace %2',
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
        // get a record in the lengow order table
        $this->_orderLengowId = $this->_lengowOrder->getLengowOrderId(
            $this->_marketplaceSku,
            $this->_deliveryAddressId
        );
        if (!$this->_orderLengowId) {
            //TODO
//            // created a record in the lengow order table
//            if (!$this->_createLengowOrder()) {
//                $this->_helper->log(
//                    'Import',
//                    $this->_helper->setLogMessage('log.import.lengow_order_not_saved'),
//                    $this->_logOutput,
//                    $this->_marketplaceSku
//                );
//                return false;
//            } else {
//                $this->_helper->log(
//                    'Import',
//                    $this->_helper->setLogMessage('log.import.lengow_order_saved'),
//                    $this->_logOutput,
//                    $this->_marketplaceSku
//                );
//            }
        }
        // load lengow order
//        $orderFactory = $this->_lengowOrderFactory->create();
//        $orderLengow = $orderFactory->load((int)$this->_orderLengowId);
        // checks if the required order data is present
        if (!$this->_checkOrderData()) {
            return $this->_returnResult('error', $this->_orderLengowId);
        }
        // get customer name and email
        $customerName = $this->_getCustomerName();
        $customerEmail = (!is_null($this->_orderData->billing_address->email)
            ? (string)$this->_orderData->billing_address->email
            : (string)$this->_packageData->delivery->email
        );

        // try to import order
        try {
            // check if the order is shipped by marketplace
            if ($this->_shippedByMp) {
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage(
                        'order shipped by %1',
                        [$this->_marketplace->name]
                    ),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
                //TODO
//                if (!$this->_configHelper->get('import_ship_mp_enabled', $this->_storeId)) {
//                    $orderLengow->updateOrder(
//                        array(
//                            'order_process_state' => 2,
//                            'extra' => Mage::helper('core')->jsonEncode($this->_orderData)
//                        )
//                    );
//                    return false;
//                }
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

        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (\Exception $e) {
            $errorMessage = '[Magento error]: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
        }

        if (isset($errorMessage)) {
            $this->_orderError->createOrderError(
                [
                    'order_lengow_id' => $this->_orderLengowId,
                    'message' => $errorMessage,
                    'type' => 'import'
                ]
            );
            $decodedMessage = $this->_dataHelper->decodeLogMessage($errorMessage, 'en_GB');
            $this->_dataHelper->log(
                'Import',
                $this->_dataHelper->setLogMessage(
                    'import order failed - %1',
                    [$decodedMessage]
                ),
                $this->_logOutput,
                $this->_marketplaceSku
            );
//            $orderLengow->updateOrder(
//                array(
//                    'extra' => Mage::helper('core')->jsonEncode($this->_orderData),
//                    'order_lengow_state' => $this->_orderStateLengow,
//                )
//            );
            return $this->_returnResult('error', $this->_orderLengowId);
        }
        return $this->_returnResult('new', $this->_orderLengowId, $order->getId());
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
        //TODO
        return false;
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
            $errorMessages[] = $this->_dataHelper->setLogMessage('Lengow error: no products in the order');
        }
        if (!isset($this->_orderData->currency->iso_a3)) {
            $errorMessages[] = $this->_dataHelper->setLogMessage('Lengow error: no currency in the order');
        }
        if ($this->_orderData->total_order == -1) {
            $errorMessages[] = $this->_dataHelper->setLogMessage('Lengow error: no exchange rates available for order prices');
        }
        if (is_null($this->_orderData->billing_address)) {
            $errorMessages[] = $this->_dataHelper->setLogMessage('Lengow error: no billing address in the order');
        } elseif (is_null($this->_orderData->billing_address->common_country_iso_a2)) {
            $errorMessages[] = $this->_dataHelper->setLogMessage("Lengow error: billing address doesn't contain the country");
        }
        if (is_null($this->_packageData->delivery->common_country_iso_a2)) {
            $errorMessages[] = $this->_dataHelper->setLogMessage("Lengow error: delivery address doesn't contain the country");
        }
        if (count($errorMessages) > 0) {
            foreach ($errorMessages as $errorMessage) {
                $this->_orderError->createOrderError(
                    [
                        'order_lengow_id' => $this->_orderLengowId,
                        'message' => $errorMessage,
                        'type' => 'import'
                    ]
                );
                $decodedMessage = $this->_dataHelper->decodeLogMessage($errorMessage, 'en_GB');
                $this->_dataHelper->log(
                    'Import',
                    $this->_dataHelper->setLogMessage(
                        'import order failed - %1',
                        [$decodedMessage]
                    ),
                    $this->_logOutput,
                    $this->_marketplaceSku
                );
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
            'order_new' => ($typeResult == 'new' ? true : false),
            'order_update' => ($typeResult == 'update' ? true : false),
            'order_error' => ($typeResult == 'error' ? true : false)
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
     * @return LengowQuote
     */
    protected function _createQuote(\Magento\Customer\Model\Customer $customer)
    {
        $customerRepo = $this->_customerRepository->getById($customer->getId());
        $quote = $this->_lengowQuote
            ->setIsMultiShipping(false)
            ->setStore($this->_storeManager->getStore($this->_storeId))
            ->setIsSuperMode(true); // don't care about stock verification, don't work ? set for each product

        // import customer addresses into quote
        // Set billing Address
        $customerBillingAddress = $this->_addressRepository->getById($customerRepo->getDefaultBilling());

        $billingAddress = $this->_quoteAddress
            ->setShouldIgnoreValidation(true)
            ->importCustomerAddressData($customerBillingAddress)
            ->setSaveInAddressBook(0);

        $customerShippingAddress = $this->_addressRepository->getById($customerRepo->getDefaultShipping());

        $shippingAddress = $this->_quoteAddress
            ->setShouldIgnoreValidation(true)
            ->importCustomerAddressData($customerShippingAddress)
            ->setSaveInAddressBook(0)
            ->setSameAsBilling(0);
        $quote->assignCustomerWithAddressChange($customerRepo, $billingAddress, $shippingAddress);

        // check if store include tax (Product and shipping cost)
        $priceIncludeTax = $this->_taxConfig->priceIncludesTax($quote->getStore());
        $shippingIncludeTax = $this->_taxConfig->shippingPriceIncludesTax($quote->getStore());
        // add product in quote
        //        $quote->addLengowProducts(
        //            $this->_packageData->cart,
        //            $this->_marketplace,
        //            $this->_marketplaceSku,
        //            $this->_logOutput,
        //            $priceIncludeTax
        //        );
//        $product1 = $this->_productFactory->create()->load(1);
//        $stockItem1 = $this->_stockRegistry->getStockItem($product1->getId());
//        $stockItem1->setAdminArea(true);
//        $quote->addProduct($product1, intval(1));
//        $product2 = $this->_productFactory->create()->load(2);
//        $stockItem2 = $this->_stockRegistry->getStockItem($product2->getId());
//        $stockItem2->setAdminArea(true);
//        $quote->addProduct($product2, intval(2));

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

        // Re-ajuste cents for item quote
        // Conversion Tax Include > Tax Exclude > Tax Include maybe make 0.01 amount error
//        if (!$priceIncludeTax) {
//            if ($quote->getGrandTotal() != $this->_orderAmount) {
//                $quoteItems = $quote->getAllItems();
//                foreach ($quoteItems as $item) {
//                    $lengowProduct = $quote->getLengowProducts((string)$item->getProduct()->getId());
//                    if ($lengowProduct['amount'] != $item->getRowTotalInclTax()) {
//                        $diff = $lengowProduct['amount'] - $item->getRowTotalInclTax();
//                        $item->setPriceInclTax($item->getPriceInclTax() + ($diff / $item->getQty()));
//                        $item->setBasePriceInclTax($item->getPriceInclTax());
//                        $item->setPrice($item->getPrice() + ($diff / $item->getQty()));
//                        $item->setOriginalPrice($item->getPrice());
//                        $item->setRowTotal($item->getRowTotal() + $diff);
//                        $item->setBaseRowTotal($item->getRowTotal());
//                        $item->setRowTotalInclTax($lengowProduct['amount']);
//                        $item->setBaseRowTotalInclTax($item->getRowTotalInclTax());
//                    }
//                }
//            }
//        }

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
        $quote->save();

        return $quote;
    }

    /**
     * Create order
     *
     * @param Quote $quote Lengow quote instance
     *
     * @throws LengowException order failed with quote
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

        $order = $this->_quoteManagement->submit($quote, $additionalDatas);
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
        // Re-ajuste cents for total and shipping cost
        // Conversion Tax Include > Tax Exclude > Tax Include maybe make 0.01 amount error
//        $priceIncludeTax = Mage::helper('tax')->priceIncludesTax($quote->getStore());
//        $shippingIncludeTax = Mage::helper('tax')->shippingPriceIncludesTax($quote->getStore());
//        if (!$priceIncludeTax || !$shippingIncludeTax) {
//            if ($order->getGrandTotal() != $this->_orderAmount) {
//                // check Grand Total
//                $diff = $this->_orderAmount - $order->getGrandTotal();
//                $order->setGrandTotal($this->_orderAmount);
//                $order->setBaseGrandTotal($order->getGrandTotal());
//                // if the difference is only on the grand total, removing the difference of shipping cost
//                if (($order->getSubtotalInclTax() + $order->getShippingInclTax()) == $this->_orderAmount) {
//                    $order->setShippingAmount($order->getShippingAmount() + $diff);
//                    $order->setBaseShippingAmount($order->getShippingAmount());
//                } else {
//                    // check Shipping Cost
//                    $diffShipping = 0;
//                    $shippingCost = $this->_processingFee + $this->_shippingCost;
//                    if ($order->getShippingInclTax() != $shippingCost) {
//                        $diffShipping = ($shippingCost - $order->getShippingInclTax());
//                        $order->setShippingAmount($order->getShippingAmount() + $diffShipping);
//                        $order->setBaseShippingAmount($order->getShippingAmount());
//                        $order->setShippingInclTax($shippingCost);
//                        $order->setBaseShippingInclTax($order->getShippingInclTax());
//                    }
//                    // update Subtotal without shipping cost
//                    $order->setSubtotalInclTax($order->getSubtotalInclTax() + ($diff - $diffShipping));
//                    $order->setBaseSubtotalInclTax($order->getSubtotalInclTax());
//                    $order->setSubtotal($order->getSubtotal() + ($diff - $diffShipping));
//                    $order->setBaseSubtotal($order->getSubtotal());
//                }
//            }
//            $order->save();
//        }
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
            $this->_dataHelper->setLogMessage('the chosen shipping method is not available for this order. Lengow has assigned a shipping method'),
            $this->_logOutput,
            $this->_marketplaceSku
        );
        return $this->_updateRates($rates, $shippingCost, 'lengow_lengow', false);
    }


}
