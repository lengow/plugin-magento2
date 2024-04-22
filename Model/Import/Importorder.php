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

use Exception;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Attribute\Repository as ProductAttribute;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Api\StockManagementInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Quote\Model\ResourceModel\Quote\Address\Rate\Collection;
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
use Lengow\Connector\Model\Import as LengowImport;
use Lengow\Connector\Model\Import\Customer as LengowCustomer;
use Lengow\Connector\Model\Import\Marketplace as LengowMarketplace;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;
use Lengow\Connector\Model\Import\Ordererror as LengowOrderError;
use Lengow\Connector\Model\Import\OrdererrorFactory as LengowOrderErrorFactory;
use Lengow\Connector\Model\Import\Orderline as LengowOrderLine;
use Lengow\Connector\Model\Import\OrderlineFactory as LengowOrderLineFactory;
use Lengow\Connector\Model\Import\QuoteFactory as LengowQuoteFactory;

/**
 * Model import importorder
 */
class Importorder extends AbstractModel
{
    /* Import Order construct params */
    public const PARAM_STORE_ID = 'store_id';
    public const PARAM_FORCE_SYNC = 'force_sync';
    public const PARAM_DEBUG_MODE = 'debug_mode';
    public const PARAM_LOG_OUTPUT = 'log_output';
    public const PARAM_MARKETPLACE_SKU = 'marketplace_sku';
    public const PARAM_DELIVERY_ADDRESS_ID = 'delivery_address_id';
    public const PARAM_ORDER_DATA = 'order_data';
    public const PARAM_PACKAGE_DATA = 'package_data';
    public const PARAM_FIRST_PACKAGE = 'first_package';
    public const PARAM_IMPORT_ONE_ORDER = 'import_one_order';

    /* Import Order data */
    public const MERCHANT_ORDER_ID = 'merchant_order_id';
    public const MERCHANT_ORDER_REFERENCE = 'merchant_order_reference';
    public const LENGOW_ORDER_ID = 'lengow_order_id';
    public const MARKETPLACE_SKU = 'marketplace_sku';
    public const MARKETPLACE_NAME = 'marketplace_name';
    public const DELIVERY_ADDRESS_ID = 'delivery_address_id';
    public const SHOP_ID = 'shop_id';
    public const CURRENT_ORDER_STATUS = 'current_order_status';
    public const PREVIOUS_ORDER_STATUS = 'previous_order_status';
    public const ERRORS = 'errors';
    public const RESULT_TYPE = 'result_type';

    /* Synchronisation results */
    public const RESULT_CREATED = 'created';
    public const RESULT_UPDATED = 'updated';
    public const RESULT_FAILED = 'failed';
    public const RESULT_IGNORED = 'ignored';

    /**
     * @var QuoteFactory Magento quote factory instance
     */
    private $quoteMagentoFactory;

    /**
     * @var OrderRepositoryInterface Magento order repository instance
     */
    private $orderRepository;

    /**
     * @var AddressRepositoryInterface Magento address repository instance
     */
    private $addressRepository;

    /**
     * @var CustomerRepositoryInterface Magento customer repository instance
     */
    private $customerRepository;

    /**
     * @var TaxConfig Magento Tax configuration instance
     */
    private $taxConfig;

    /**
     * @var ScopeConfigInterface Magento cope config instance
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface Magento store manager instance
     */
    private $storeManager;

    /**
     * @var QuoteAddressFactory Magento quote address factory instance
     */
    private $quoteAddressFactory;

    /**
     * @var TaxCalculation Magento tax calculation instance
     */
    private $taxCalculation;

    /**
     * @var Calculation Magento calculation instance
     */
    private $calculation;

    /**
     * @var QuoteManagement Magento quote management instance
     */
    private $quoteManagement;

    /**
     * @var DateTime Magento datetime instance
     */
    private $dateTime;

    /**
     * @var TimezoneInterface Magento datetime timezone instance
     */
    private $timezone;

    /**
     * @var ProductFactory Magento product factory instance
     */
    private $productFactory;

    /**
     * @var StockManagementInterface Magento stock management instance
     */
    private $stockManagement;

    /**
     * @var ProductCollectionFactory Magento product collection factory instance
     */
    private $productCollection;

    /**
     * @var ProductAttribute Magento product attribute instance
     */
    private $productAttribute;

    /**
     * @var BackendSession $backendSession Magento Backend session instance
     */
    private $backendSession;

    /**
     * @var LengowOrder Lengow order instance
     */
    private $lengowOrder;

    /**
     * @var LengowOrderFactory Lengow order factory instance
     */
    private $lengowOrderFactory;

    /**
     * @var LengowOrderErrorFactory Lengow order error Factory instance
     */
    private $lengowOrderErrorFactory;

    /**
     * @var LengowCustomer Lengow customer instance
     */
    private $lengowCustomer;

    /**
     * @var LengowQuoteFactory Lengow quote instance
     */
    private $lengowQuoteFactory;

    /**
     * @var LengowOrderLineFactory Lengow order line factory instance
     */
    private $lengowOrderLineFactory;

    /**
     * @var ImportHelper Lengow import helper instance
     */
    private $importHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var integer order items
     */
    private $orderItems;

    /**
     * @var string id lengow of current order
     */
    private $marketplaceSku;

    /**
     * @var integer id of delivery address for current order
     */
    private $deliveryAddressId;

    /**
     * @var integer|null Magento store id
     */
    private $storeId;

    /**
     * @var boolean force import order even if there are errors
     */
    private $forceSync;

    /**
     * @var boolean use debug mode
     */
    private $debugMode = false;

    /**
     * @var boolean display log messages
     */
    private $logOutput = false;

    /**
     * @var mixed order data
     */
    private $orderData;

    /**
     * @var mixed package data
     */
    private $packageData;

    /**
     * @var boolean is first package
     */
    private $firstPackage;

    /**
     * @var boolean import one order var from lengow import
     */
    private $importOneOrder;

    /**
     * @var LengowMarketplace Lengow marketplace instance
     */
    private $marketplace;

    /**
     * @var string marketplace label
     */
    private $marketplaceLabel;

    /**
     * @var boolean re-import order
     */
    private $isReimported = false;

    /**
     * @var string Lengow order state
     */
    private $orderStateLengow;

    /**
     * @var string Previous Lengow order state
     */
    private $previousOrderStateLengow;

    /**
     * @var string marketplace order state
     */
    private $orderStateMarketplace;

    /**
     * @var integer id of the record Lengow order table
     */
    private $orderLengowId;

    /**
     * @var integer id of the record Magento order table
     */
    private $orderId;

    /**
     * @var integer Magento order reference
     */
    private $orderReference;

    /**
     * @var string order types data
     */
    private $orderTypes;

    /**
     * @var string order date in GMT format
     */
    private $orderDate;

    /**
     * @var float order processing fees
     */
    private $processingFee;

    /**
     * @var float order shipping costs
     */
    private $shippingCost;

    /**
     * @var float order amount
     */
    private $orderAmount;

    /**
     * @var string|null carrier name
     */
    private $carrierName;

    /**
     * @var string|null carrier method
     */
    private $carrierMethod;

    /**
     * @var boolean order shipped by marketplace
     */
    private $shippedByMp = false;

    /**
     * @var string|null carrier tracking number
     */
    private $trackingNumber;

    /**
     * @var string|null carrier relay id
     */
    private $relayId;

    /**
     * @var array order errors
     */
    private $errors = [];

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
     * @param QuoteManagement $quoteManagement Magento quote management instance
     * @param DateTime $dateTime Magento datetime instance
     * @param TimezoneInterface $timezone Magento datetime timezone instance
     * @param ProductFactory $productFactory Magento product factory
     * @param StockManagementInterface $stockManagement Magento stock management instance
     * @param MagentoQuoteFactory $quoteMagentoFactory Magento quote factory instance
     * @param ProductAttribute $productAttribute Magento product attribute instance
     * @param BackendSession $backendSession Backend session instance
     * @param ProductCollectionFactory $productCollection Magento product collection factory instance
     * @param LengowOrder $lengowOrder Lengow order instance
     * @param LengowOrderFactory $lengowOrderFactory Lengow order instance
     * @param LengowOrderErrorFactory $lengowOrderErrorFactory Lengow order error factory instance
     * @param LengowCustomer $lengowCustomer Lengow customer instance
     * @param LengowQuoteFactory $lengowQuoteFactory Lengow quote instance
     * @param LengowOrderLineFactory $lengowOrderLineFactory Lengow order line factory instance
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
        ProductAttribute $productAttribute,
        BackendSession $backendSession,
        ProductCollectionFactory $productCollection,
        LengowOrder $lengowOrder,
        LengowOrderFactory $lengowOrderFactory,
        LengowOrderErrorFactory $lengowOrderErrorFactory,
        LengowCustomer $lengowCustomer,
        LengowQuoteFactory $lengowQuoteFactory,
        LengowOrderLineFactory $lengowOrderLineFactory,
        ImportHelper $importHelper,
        DataHelper $dataHelper,
        ConfigHelper $configHelper
    ) {
        $this->orderRepository = $orderRepository;
        $this->addressRepository = $addressRepository;
        $this->customerRepository = $customerRepository;
        $this->taxConfig = $taxConfig;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->taxCalculation = $taxCalculation;
        $this->calculation = $calculation;
        $this->quoteManagement = $quoteManagement;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->productFactory = $productFactory;
        $this->stockManagement = $stockManagement;
        $this->quoteMagentoFactory = $quoteMagentoFactory;
        $this->productAttribute = $productAttribute;
        $this->backendSession = $backendSession;
        $this->productCollection = $productCollection;
        $this->lengowOrder = $lengowOrder;
        $this->lengowOrderFactory = $lengowOrderFactory;
        $this->lengowOrderErrorFactory = $lengowOrderErrorFactory;
        $this->lengowCustomer = $lengowCustomer;
        $this->lengowQuoteFactory = $lengowQuoteFactory;
        $this->lengowOrderLineFactory = $lengowOrderLineFactory;
        $this->importHelper = $importHelper;
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        parent::__construct($context, $registry);
    }

    /**
     * Init an import order
     *
     * @param array $params optional options
     *
     * integer  store_id            Id store for current order
     * boolean  force_sync          force import order even if there are errors
     * boolean  debug_mode          debug mode
     * boolean  log_output          display log messages
     * string   marketplace_sku     order marketplace sku
     * integer  delivery_address_id order delivery address id
     * mixed    order_data          order data
     * mixed    package_data        package data
     * boolean  first_package       it is the first package
     * boolean  import_one_order    synchronisation process for only one order
     *
     * @return Importorder
     */
    public function init(array $params): Importorder
    {
        $this->storeId = $params[self::PARAM_STORE_ID];
        $this->forceSync = $params[self::PARAM_FORCE_SYNC];
        $this->debugMode = $params[self::PARAM_DEBUG_MODE];
        $this->logOutput = $params[self::PARAM_LOG_OUTPUT];
        $this->marketplaceSku = $params[self::PARAM_MARKETPLACE_SKU];
        $this->deliveryAddressId = $params[self::PARAM_DELIVERY_ADDRESS_ID];
        $this->orderData = $params[self::PARAM_ORDER_DATA];
        $this->packageData = $params[self::PARAM_PACKAGE_DATA];
        $this->firstPackage = $params[self::PARAM_FIRST_PACKAGE];
        $this->importOneOrder = $params[self::PARAM_IMPORT_ONE_ORDER];
        return $this;
    }

    /**
     * Create or update order
     *
     * @throws Exception
     *
     * @return array
     */
    public function importOrder(): array
    {
        // load marketplace singleton and marketplace data
        if (!$this->loadMarketplaceData()) {
            return $this->returnResult(self::RESULT_IGNORED);
        }
        // checks if a record already exists in the lengow order table
        $this->orderLengowId = $this->lengowOrder->getLengowOrderId(
            $this->marketplaceSku,
            $this->marketplace->name
        );
        // checks if an order already has an error in progress
        if ($this->orderLengowId && $this->orderErrorAlreadyExist()) {
            return $this->returnResult(self::RESULT_IGNORED);
        }
        // recovery id if the command has already been imported
        $orderId = $this->lengowOrder->getOrderIdIfExist(
            $this->marketplaceSku,
            $this->marketplace->name
        );
        // update order state if already imported
        if ($orderId) {
            $orderUpdated = $this->checkAndUpdateOrder($orderId);
            if ($orderUpdated) {
                return $this->returnResult(self::RESULT_UPDATED);
            }
            if (!$this->isReimported) {
                return $this->returnResult(self::RESULT_IGNORED);
            }
        }
        // checks if the order is not anonymized or too old
        if (!$this->orderLengowId && !$this->canCreateOrder()) {
            return $this->returnResult(self::RESULT_IGNORED);
        }
        // checks if an external id already exists
        if (!$this->orderLengowId && $this->externalIdAlreadyExist()) {
            return $this->returnResult(self::RESULT_IGNORED);
        }
        // checks if the order status is valid for order creation
        if (!$this->orderStatusIsValid()) {
            return $this->returnResult(self::RESULT_IGNORED);
        }
        // load data and create a new record in lengow order table if not exist
        if (!$this->createLengowOrder()) {
            return $this->returnResult(self::RESULT_IGNORED);
        }
        // load lengow order
        $orderLengow = $this->lengowOrderFactory->create()->load($this->orderLengowId);
        // checks if the required order data is present and update Lengow order record
        if (!$this->checkAndUpdateLengowOrderData($orderLengow)) {
            return $this->returnResult(self::RESULT_FAILED);
        }
        // checks if an order sent by the marketplace must be created or not
        if (!$this->canCreateOrderShippedByMarketplace($orderLengow)) {
            return $this->returnResult(self::RESULT_IGNORED);
        }
        // create Magento order
        if (!$this->createOrder($orderLengow)) {
            return $this->returnResult(self::RESULT_FAILED);
        }
        return $this->returnResult(self::RESULT_CREATED);
    }

    /**
     * Load marketplace singleton and marketplace data
     *
     * @return boolean
     */
    private function loadMarketplaceData(): bool
    {
        try {
            // get marketplace and Lengow order state
            $this->marketplace = $this->importHelper->getMarketplaceSingleton((string) $this->orderData->marketplace);
            $this->marketplaceLabel = $this->marketplace->labelName;
            $this->orderStateMarketplace = (string) $this->orderData->marketplace_status;
            $this->orderStateLengow = $this->marketplace->getStateLengow($this->orderStateMarketplace);
            $this->previousOrderStateLengow = $this->orderStateLengow;
            return true;
        } catch (LengowException $e) {
            $this->errors[] = $this->dataHelper->decodeLogMessage($e->getMessage(), false);
            $this->dataHelper->log(DataHelper::CODE_IMPORT, $e->getMessage(), $this->logOutput, $this->marketplaceSku);
        }
        return false;
    }

    /**
     * Return an array of result for each order
     *
     * @param string $resultType Type of result (created, updated, failed or ignored)
     *
     * @return array
     */
    private function returnResult(string $resultType): array
    {
        return [
            self::MERCHANT_ORDER_ID => $this->orderId,
            self::MERCHANT_ORDER_REFERENCE => $this->orderReference,
            self::LENGOW_ORDER_ID => $this->orderLengowId,
            self::MARKETPLACE_SKU => $this->marketplaceSku,
            self::MARKETPLACE_NAME => $this->marketplace->name ?? null,
            self::DELIVERY_ADDRESS_ID => $this->deliveryAddressId,
            self::SHOP_ID => $this->storeId,
            self::CURRENT_ORDER_STATUS => $this->orderStateLengow,
            self::PREVIOUS_ORDER_STATUS => $this->previousOrderStateLengow,
            self::ERRORS => $this->errors,
            self::RESULT_TYPE => $resultType,
        ];
    }

    /**
     * Checks if an order already has an error in progress
     *
     * @return boolean
     */
    private function orderErrorAlreadyExist(): bool
    {
        // if log import exist and not finished
        $orderError = $this->lengowOrder->orderIsInError($this->marketplaceSku, $this->marketplace->name);
        if (!$orderError) {
            return false;
        }
        // force order synchronization by removing pending errors
        if ($this->forceSync) {
            $this->lengowOrderErrorFactory->create()->finishOrderErrors($this->orderLengowId);
            return false;
        }
        $dateMessage = $this->timezone->date(strtotime($orderError[LengowOrderError::FIELD_CREATED_AT]))
            ->format(DataHelper::DATE_FULL);
        $decodedMessage = $this->dataHelper->decodeLogMessage($orderError[LengowOrderError::FIELD_MESSAGE], false);
        $message = $this->dataHelper->setLogMessage(
            '%1 (created on the %2)',
            [
                $decodedMessage,
                $dateMessage,
            ]
        );
        $this->errors[] = $this->dataHelper->decodeLogMessage($message, false);
        $this->dataHelper->log(DataHelper::CODE_IMPORT, $message, $this->logOutput, $this->marketplaceSku);
        return true;
    }

    /**
     * Check the command and updates data if necessary
     *
     * @param integer $orderId Magento order id
     *
     * @return boolean
     */
    private function checkAndUpdateOrder(int $orderId): bool
    {
        $order = $this->orderRepository->get($orderId);
        $this->dataHelper->log(
            DataHelper::CODE_IMPORT,
            $this->dataHelper->setLogMessage('order already imported (ORDER ID %1)', [$order->getIncrementId()]),
            $this->logOutput,
            $this->marketplaceSku
        );
        $orderLengowId = $this->lengowOrder->getLengowOrderIdWithOrderId($orderId);
        $lengowOrder = $this->lengowOrderFactory->create()->load($orderLengowId);
        // Lengow -> Cancel and reimport order
        if ($lengowOrder->getData(LengowOrder::FIELD_IS_REIMPORTED)) {
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage(
                    'order ready to be re-imported (ORDER ID %1)',
                    [$order->getIncrementId()]
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            $this->isReimported = true;
            return false;
        }
        // load data for return
        $this->orderId = (int) $orderId;
        $this->orderReference = $order->getIncrementId();
        $this->previousOrderStateLengow = $lengowOrder->getData(LengowOrder::FIELD_ORDER_LENGOW_STATE);
        // try to update magento order, lengow order and finish actions if necessary
        $orderUpdated = $this->lengowOrder->updateState(
            $order,
            $lengowOrder,
            $this->orderStateLengow,
            $this->packageData
        );
        if ($orderUpdated) {
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage("order's status has been updated to %1", [$orderUpdated]),
                $this->logOutput,
                $this->marketplaceSku
            );
            $orderUpdated = true;
        }
        $vatNumberData = $this->getVatNumberFromOrderData();
        if ($vatNumberData !== $order->getCustomerTaxvat()) {
            $this->checkAndUpdateLengowOrderData($lengowOrder);
            $this->lengowCustomer->updateCustomerVatNumber(
                $order->getCustomerEmail(),
                (int) $order->getStoreId(),
                (string) $vatNumberData
            );
            $orderBillingAddress = $order->getBillingAddress();
            $orderBillingAddress->setVatId($vatNumberData);
            $orderBillingAddress->save();
            $orderUpdated = true;
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage("%1 order(s) updated", [$orderUpdated]),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
        unset($order, $lengowOrder);
        return $orderUpdated;
    }

    /**
     * Checks if the order is not anonymized or too old
     *
     * @return boolean
     */
    private function canCreateOrder(): bool
    {
        if ($this->importOneOrder) {
            return true;
        }
        // skip import if the order is anonymized
        if ($this->orderData->anonymized) {
            $message = $this->dataHelper->setLogMessage('order is anonymized and has not been imported');
            $this->errors[] = $this->dataHelper->decodeLogMessage($message, false);
            $this->dataHelper->log(DataHelper::CODE_IMPORT, $message, $this->logOutput, $this->marketplaceSku);
            return false;
        }
        // skip import if the order is older than 3 months
        try {
            $dateTimeOrder = new \DateTime($this->orderData->marketplace_order_date);
            $interval = $dateTimeOrder->diff(new \DateTime());
            $monthInterval = $interval->m + ($interval->y * 12);
            if ($monthInterval >= LengowImport::MONTH_INTERVAL_TIME) {
                $message = $this->dataHelper->setLogMessage('order is older than 3 months and has not been imported');
                $this->errors[] = $this->dataHelper->decodeLogMessage($message, false);
                $this->dataHelper->log(DataHelper::CODE_IMPORT, $message, $this->logOutput, $this->marketplaceSku);
                return false;
            }
        } catch (Exception $e) {
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage('unable to check if the order is too old'),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
        return true;
    }

    /**
     * Checks if an external id already exists
     *
     * @return boolean
     */
    private function externalIdAlreadyExist(): bool
    {
        if (empty($this->orderData->merchant_order_id) || $this->debugMode || $this->isReimported) {
            return false;
        }
        foreach ($this->orderData->merchant_order_id as $externalId) {
            if ($this->lengowOrder->getOrderIdWithDeliveryAddress((int) $externalId, $this->deliveryAddressId)) {
                $message = $this->dataHelper->setLogMessage(
                    'already imported in Magento with the order ID %1',
                    [$externalId]
                );
                $this->errors[] = $this->dataHelper->decodeLogMessage($message, false);
                $this->dataHelper->log(DataHelper::CODE_IMPORT, $message, $this->logOutput, $this->marketplaceSku);
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the order status is valid for order creation
     *
     * @return boolean
     */
    private function orderStatusIsValid(): bool
    {
        if ($this->importHelper->checkState($this->orderStateMarketplace, $this->marketplace)) {
            return true;
        }
        $orderProcessState = $this->lengowOrder->getOrderProcessState($this->orderStateLengow);
        // check and complete an order not imported if it is canceled or refunded
        if ($this->orderLengowId && $orderProcessState === LengowOrder::PROCESS_STATE_FINISH) {
            $this->lengowOrderErrorFactory->create()->finishOrderErrors($this->orderLengowId);
            $orderLengow = $this->lengowOrderFactory->create()->load((int) $this->orderLengowId);
            $orderLengow->updateOrder(
                [
                    LengowOrder::FIELD_IS_IN_ERROR => 0,
                    LengowOrder::FIELD_ORDER_LENGOW_STATE => $this->orderStateLengow,
                    LengowOrder::FIELD_ORDER_PROCESS_STATE => $orderProcessState,
                ]
            );

        }
        $message = $this->dataHelper->setLogMessage(
            'current order status %1 means it is not possible to import the order to the marketplace %2',
            [
                $this->orderStateMarketplace,
                $this->marketplace->name,
            ]
        );
        $this->errors[] = $this->dataHelper->decodeLogMessage($message, false);
        $this->dataHelper->log(DataHelper::CODE_IMPORT, $message, $this->logOutput, $this->marketplaceSku);
        return false;
    }

    /**
     * Create an order in lengow orders table
     *
     * @return boolean
     */
    private function createLengowOrder(): bool
    {
        // load order date
        $this->loadOrderDate();
        // load order types data
        $this->loadOrderTypesData();
        // If the Lengow order already exists do not recreate it
        if ($this->orderLengowId) {
            return true;
        }
        $params = [
            LengowOrder::FIELD_STORE_ID => (int) $this->storeId,
            LengowOrder::FIELD_MARKETPLACE_SKU => $this->marketplaceSku,
            LengowOrder::FIELD_MARKETPLACE_NAME => $this->marketplace->name,
            LengowOrder::FIELD_MARKETPLACE_LABEL => $this->marketplaceLabel,
            LengowOrder::FIELD_DELIVERY_ADDRESS_ID => $this->deliveryAddressId,
            LengowOrder::FIELD_ORDER_LENGOW_STATE => $this->orderStateLengow,
            LengowOrder::FIELD_ORDER_TYPES => $this->orderTypes,
            LengowOrder::FIELD_CUSTOMER_VAT_NUMBER => $this->getVatNumberFromOrderData(),
            LengowOrder::FIELD_ORDER_DATE => $this->orderDate,
            LengowOrder::FIELD_MESSAGE => $this->getOrderComment(),
            LengowOrder::FIELD_EXTRA => json_encode($this->orderData),
            LengowOrder::FIELD_IS_IN_ERROR => 1,
        ];
        // create lengow order
        $lengowOrder = $this->lengowOrderFactory->create();
        $lengowOrder->createOrder($params);
        // get lengow order id
        $this->orderLengowId = $lengowOrder->getLengowOrderId(
            $this->marketplaceSku,
            $this->marketplace->name,
            $this->deliveryAddressId
        );
        if ($this->orderLengowId) {
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage('order saved in Lengow orders table'),
                $this->logOutput,
                $this->marketplaceSku
            );
            return true;
        }
        $message = $this->dataHelper->setLogMessage('WARNING! Order could NOT be saved in Lengow orders table');
        $this->errors[] = $this->dataHelper->decodeLogMessage($message, false);
        $this->dataHelper->log(DataHelper::CODE_IMPORT, $message, $this->logOutput, $this->marketplaceSku);
        return false;
    }

    /**
     * Load order date in GMT format
     */
    private function loadOrderDate(): void
    {
        $orderDate = $this->orderData->marketplace_order_date !== null
            ? (string) $this->orderData->marketplace_order_date
            : (string) $this->orderData->imported_at;
        $this->orderDate = $this->dateTime->gmtDate(DataHelper::DATE_FULL, strtotime($orderDate));
    }

    /**
     * Load order types data and update Lengow order record
     */
    private function loadOrderTypesData(): void
    {
        $orderTypes = [];
        if ($this->orderData->order_types !== null && !empty($this->orderData->order_types)) {
            foreach ($this->orderData->order_types as $orderType) {
                $orderTypes[$orderType->type] = $orderType->label;
                if ($orderType->type === LengowOrder::TYPE_DELIVERED_BY_MARKETPLACE) {
                    $this->shippedByMp = true;
                }
            }
        }
        $this->orderTypes = json_encode($orderTypes);
    }

    /**
     * Get order comment from marketplace
     *
     * @return string
     */
    private function getOrderComment(): string
    {
        if (isset($this->orderData->comments) && is_array($this->orderData->comments)) {
            return implode(',', $this->orderData->comments);
        }
        return (string) $this->orderData->comments;
    }

    /**
     * Get vat_number from lengow order data
     *
     * @return string|null
     */
    private function getVatNumberFromOrderData(): ?string
    {

        return $this->orderData->billing_address->vat_number ?? $this->packageData->delivery->vat_number ?? null;
    }

    /**
     * Checks if the required order data is present and update Lengow order record
     *
     * @param LengowOrder $orderLengow Lengow order instance
     *
     * @return boolean
     */
    private function checkAndUpdateLengowOrderData(Order $orderLengow): bool
    {
        // checks if all necessary order data are present
        if (!$this->checkOrderData()) {
            return false;
        }
        // load order amount, processing fees and shipping costs
        $this->loadOrderAmount();
        // load tracking data
        $this->loadTrackingData();
        // update Lengow order record with new data
        $orderLengow->updateOrder(
            [
                LengowOrder::FIELD_CURRENCY => $this->orderData->currency->iso_a3,
                LengowOrder::FIELD_TOTAL_PAID => $this->orderAmount,
                LengowOrder::FIELD_ORDER_ITEM => $this->orderItems,
                LengowOrder::FIELD_CUSTOMER_NAME => $this->getCustomerName(),
                LengowOrder::FIELD_CUSTOMER_EMAIL => $this->getCustomerEmail(),
                LengowOrder::FIELD_CUSTOMER_VAT_NUMBER => $this->getVatNumberFromOrderData(),
                LengowOrder::FIELD_COMMISSION => (float) $this->orderData->commission,
                LengowOrder::FIELD_CARRIER => $this->carrierName,
                LengowOrder::FIELD_CARRIER_METHOD => $this->carrierMethod,
                LengowOrder::FIELD_CARRIER_TRACKING => $this->trackingNumber,
                LengowOrder::FIELD_CARRIER_RELAY_ID => $this->relayId,
                LengowOrder::FIELD_SENT_MARKETPLACE => $this->shippedByMp,
                LengowOrder::FIELD_DELIVERY_COUNTRY_ISO => $this->packageData->delivery->common_country_iso_a2,
                LengowOrder::FIELD_ORDER_LENGOW_STATE => $this->orderStateLengow,
                LengowOrder::FIELD_EXTRA => json_encode($this->orderData),
            ]
        );
        return true;
    }

    /**
     * Checks if all necessary order data are present
     *
     * @return boolean
     */
    private function checkOrderData(): bool
    {
        $errorMessages = [];
        if (empty($this->packageData->cart)) {
            $errorMessages[] = $this->dataHelper->setLogMessage('Lengow error: no product in the order');
        }
        if (!isset($this->orderData->currency->iso_a3)) {
            $errorMessages[] = $this->dataHelper->setLogMessage('Lengow error: no currency in the order');
        }
        if ($this->orderData->total_order == -1) {
            $errorMessages[] = $this->dataHelper->setLogMessage(
                'Lengow error: no exchange rates available for order prices'
            );
        }
        if ($this->orderData->billing_address === null) {
            $errorMessages[] = $this->dataHelper->setLogMessage('Lengow error: no billing address in the order');
        } elseif ($this->orderData->billing_address->common_country_iso_a2 === null) {
            $errorMessages[] = $this->dataHelper->setLogMessage(
                "Lengow error: billing address doesn't contain the country"
            );
        }
        if ($this->packageData->delivery->common_country_iso_a2 === null) {
            $errorMessages[] = $this->dataHelper->setLogMessage(
                "Lengow error: delivery address doesn't contain the country"
            );
        }
        if (empty($errorMessages)) {
            return true;
        }
        foreach ($errorMessages as $errorMessage) {
            $orderError = $this->lengowOrderErrorFactory->create();
            $orderError->createOrderError(
                [
                    LengowOrderError::FIELD_ORDER_LENGOW_ID => $this->orderLengowId,
                    LengowOrderError::FIELD_MESSAGE => $errorMessage,
                    LengowOrderError::FIELD_TYPE => LengowOrderError::TYPE_ERROR_IMPORT,
                ]
            );
            $decodedMessage = $this->dataHelper->decodeLogMessage($errorMessage, false);
            $this->errors[] = $decodedMessage;
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage(
                    'import order failed - %1',
                    [$decodedMessage]
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            unset($orderError);
        }
        return false;
    }

    /**
     * Load order amount, processing fees and shipping costs
     */
    private function loadOrderAmount(): void
    {
        $this->processingFee = (float) $this->orderData->processing_fee;
        $this->shippingCost = (float) $this->orderData->shipping;
        // rewrite processing fees and shipping cost
        if (!$this->firstPackage) {
            $this->processingFee = 0;
            $this->shippingCost = 0;
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage('rewrite amount without processing fee'),
                $this->logOutput,
                $this->marketplaceSku
            );
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage('rewrite amount without shipping cost'),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
        // get total amount and the number of items
        $nbItems = 0;
        $totalAmount = 0;
        foreach ($this->packageData->cart as $product) {
            // check whether the product is canceled for amount
            if ($product->marketplace_status !== null) {
                $stateProduct = $this->marketplace->getStateLengow((string) $product->marketplace_status);
                if ($stateProduct === LengowOrder::STATE_CANCELED || $stateProduct === LengowOrder::STATE_REFUSED) {
                    continue;
                }
            }
            $nbItems += (int) $product->quantity;
            $totalAmount += (float) $product->amount;
        }
        $this->orderItems = $nbItems;
        $this->orderAmount = $totalAmount + $this->processingFee + $this->shippingCost;
    }

    /**
     * Get tracking data and update Lengow order record
     */
    private function loadTrackingData(): void
    {
        $tracks = $this->packageData->delivery->trackings;
        if (!empty($tracks)) {
            $tracking = $tracks[0];
            $this->carrierName = $tracking->carrier;
            $this->carrierMethod = $tracking->method;
            $this->trackingNumber = $tracking->number;
            $this->relayId = $tracking->relay->id;
        }
    }

    /**
     * Get customer name
     *
     * @return string
     */
    private function getCustomerName(): string
    {
        $firstname = (string) $this->orderData->billing_address->first_name;
        $lastname = (string) $this->orderData->billing_address->last_name;
        $firstname = ucfirst(strtolower($firstname));
        $lastname = ucfirst(strtolower($lastname));
        if (empty($firstname) && empty($lastname)) {
            return (string) $this->orderData->billing_address->full_name;
        }
        if (empty($firstname)) {
            return $lastname;
        }
        if (empty($lastname)) {
            return $firstname;
        }
        return $firstname . ' ' . $lastname;
    }

    /**
     * Get customer email
     *
     * @return string
     */
    private function getCustomerEmail(): string
    {
        return $this->orderData->billing_address->email !== null
            ? (string) $this->orderData->billing_address->email
            : (string) $this->packageData->delivery->email;
    }

    /**
     * Checks if an order sent by the marketplace must be created or not
     *
     * @param LengowOrder $orderLengow Lengow order instance
     *
     * @return boolean
     */
    private function canCreateOrderShippedByMarketplace(Order $orderLengow): bool
    {
        // check if the order is shipped by marketplace
        if ($this->shippedByMp) {
            $message = $this->dataHelper->setLogMessage('order shipped by %1', [$this->marketplace->name]);
            $this->dataHelper->log(DataHelper::CODE_IMPORT, $message, $this->logOutput, $this->marketplaceSku);
            if (!$this->configHelper->get(ConfigHelper::SHIPPED_BY_MARKETPLACE_ENABLED)) {
                $this->errors[] = $this->dataHelper->decodeLogMessage($message, false);
                $orderLengow->updateOrder(
                    [
                        LengowOrder::FIELD_IS_IN_ERROR => 0,
                        LengowOrder::FIELD_ORDER_PROCESS_STATE => 2,
                    ]
                );
                return false;
            }
        }
        return true;
    }

    /**
     * Create a Magento order
     *
     * @param LengowOrder $orderLengow Lengow order instance
     *
     * @return boolean
     */
    private function createOrder(Order $orderLengow): bool
    {
        try {
            // search and get all products
            $products = $this->getProducts();
            // create or update customer with addresses
            $customer = $this->lengowCustomer->createCustomer(
                $this->orderData,
                $this->packageData->delivery,
                $this->storeId,
                $this->marketplaceSku,
                $this->logOutput
            );
            // if this order is B2B activate B2bTaxesApplicator
            $orderTotalTaxLengow = (float) $this->orderData->total_tax ?? 0;
            if ($orderTotalTaxLengow == 0
                    && $this->configHelper->isB2bWithoutTaxEnabled($this->storeId)
                    && $orderLengow->isBusiness()) {
                $this->backendSession->setIsLengowB2b(1);
            } else {
                $this->backendSession->setIsLengowB2b(0);
            }
            // create Magento Quote
            $quote = $this->createQuote($customer, $products);
            // create a Magento Order from a Quote
            $order = $this->makeOrder($quote, $orderLengow);



            // if no Magento order created
            if (!$order) {
                throw new LengowException($this->dataHelper->setLogMessage('order could not be saved'));
            }
            // load order data for return
            $this->orderId = (int) $order->getId();
            $this->orderReference = $order->getIncrementId();
            // save order line id in lengow_order_line table
            $this->saveLengowOrderLine($order, $products);
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage(
                    'order successfully imported (ORDER ID %1)',
                    [$order->getIncrementId()]
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
            // checks and places the order in complete status in Magento
            $this->updateStateToShip($order);
            // add quantity back for re-imported order and order shipped by marketplace
            $this->addQuantityBack($products);
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Magento error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
        }
        if (!isset($errorMessage)) {
            return true;
        }
        if ($orderLengow->getData(LengowOrder::FIELD_IS_IN_ERROR)) {
            $orderError = $this->lengowOrderErrorFactory->create();
            $orderError->createOrderError(
                [
                    LengowOrderError::FIELD_ORDER_LENGOW_ID => $this->orderLengowId,
                    LengowOrderError::FIELD_MESSAGE => $errorMessage,
                    LengowOrderError::FIELD_TYPE => LengowOrderError::TYPE_ERROR_IMPORT,
                ]
            );
            unset($orderError);
        }
        $decodedMessage = $this->dataHelper->decodeLogMessage($errorMessage, false);
        $this->errors[] = $decodedMessage;
        $this->dataHelper->log(
            DataHelper::CODE_IMPORT,
            $this->dataHelper->setLogMessage(
                'import order failed - %1',
                [$decodedMessage]
            ),
            $this->logOutput,
            $this->marketplaceSku
        );
        $orderLengow->updateOrder(
            [
                LengowOrder::FIELD_EXTRA => json_encode($this->orderData),
                LengowOrder::FIELD_ORDER_LENGOW_STATE => $this->orderStateLengow,
            ]
        );
        return false;
    }

    /**
     * Get products from API data
     *
     * @throws LengowException
     *
     * @return array
     */
    private function getProducts(): array
    {
        $lengowProducts = [];
        foreach ($this->packageData->cart as $product) {
            $found = false;
            $magentoProduct = false;
            $orderLineId = (string) $product->marketplace_order_line_id;
            // check whether the product is canceled
            if ($product->marketplace_status !== null) {
                $stateProduct = $this->marketplace->getStateLengow((string) $product->marketplace_status);
                if ($stateProduct === LengowOrder::STATE_CANCELED || $stateProduct === LengowOrder::STATE_REFUSED) {
                    $productId = $product->merchant_product_id->id !== null
                        ? (string) $product->merchant_product_id->id
                        : (string) $product->marketplace_product_id;
                    $this->dataHelper->log(
                        DataHelper::CODE_IMPORT,
                        $this->dataHelper->setLogMessage(
                            'product %1 could not be added to cart - status: %2',
                            [
                                $productId,
                                $stateProduct,
                            ]
                        ),
                        $this->logOutput,
                        $this->marketplaceSku
                    );
                    continue;
                }
            }
            $productIds = [
                'merchant_product_id' => $product->merchant_product_id->id,
                'marketplace_product_id' => $product->marketplace_product_id,
            ];

            $productField = $product->merchant_product_id->field !== null
                ? strtolower((string) $product->merchant_product_id->field)
                : false;
            // search product foreach value
            foreach ($productIds as $attributeName => $attributeValue) {
                // remove _FBA from product id
                $attributeValue = preg_replace('/_FBA$/', '', $attributeValue);
                if (empty($attributeValue)) {
                    continue;
                }
                // search by field if exists
                if ($productField) {
                    try {
                        $attributeModel = $this->productAttribute->get($productField);
                    } catch (Exception $e) {
                        $attributeModel = false;
                    }
                    if ($attributeModel) {
                        $collection = $this->productCollection->create()
                            ->setStoreId($this->storeId)
                            ->addAttributeToSelect($productField)
                            ->addAttributeToFilter($productField, $attributeValue)
                            ->setPage(1, 1)
                            ->getData();
                        if (is_array($collection) && !empty($collection)) {
                            $magentoProduct = $this->productFactory
                                ->create()
                                ->setStoreId($this->storeId)
                                ->load($collection[0]['entity_id']);
                        }
                    }
                }
                // search by id or sku
                if (!$magentoProduct || !$magentoProduct->getId()) {
                    if (preg_match('/^[0-9]*$/', $attributeValue)) {
                        $magentoProduct = $this->productFactory
                            ->create()
                            ->setStoreId($this->storeId)
                            ->load((int) $attributeValue);
                    }
                    if (!$magentoProduct || !$magentoProduct->getId()) {
                        $attributeValue = str_replace('\_', '_', $attributeValue);
                        $magentoProduct = $this->productFactory->create()->setStoreId($this->storeId)->load(
                            $this->productFactory->create()->getIdBySku($attributeValue)
                        );
                    }
                }
                if ($magentoProduct && $magentoProduct->getId()) {
                    $magentoProductId = $magentoProduct->getId();
                    // save total row Lengow for each product
                    if (array_key_exists($magentoProductId, $lengowProducts)) {
                        $lengowProducts[$magentoProductId]['quantity'] += (int) $product->quantity;
                        $lengowProducts[$magentoProductId]['amount'] += (float) $product->amount;
                        $lengowProducts[$magentoProductId]['order_line_ids'][] = $orderLineId;
                    } else {
                        $lengowProducts[$magentoProductId] = [
                            'magento_product' => $magentoProduct,
                            'sku' => $magentoProduct->getSku(),
                            'title' => (string) $product->title,
                            'amount' => (float) $product->amount,
                            'price_unit' => (float) ($product->amount / $product->quantity),
                            'quantity' => (int) $product->quantity,
                            'order_line_ids' => [$orderLineId],
                            'tax_amount' => (float) $product->tax,
                            'tax_unit' => (float)  ($product->tax / $product->quantity)
                        ];
                    }
                    $this->dataHelper->log(
                        DataHelper::CODE_IMPORT,
                        $this->dataHelper->setLogMessage(
                            'product id %1 found with field %2 (%3)',
                            [
                                $magentoProduct->getId(),
                                $attributeName,
                                $attributeValue,
                            ]
                        ),
                        $this->logOutput,
                        $this->marketplaceSku
                    );
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $productId = $product->merchant_product_id->id !== null
                    ? (string) $product->merchant_product_id->id
                    : (string) $product->marketplace_product_id;
                throw new LengowException(
                    $this->dataHelper->setLogMessage('product %1 could not be found', [$productId])
                );
            }
            if ($magentoProduct->getTypeId() === Configurable::TYPE_CODE) {
                throw new LengowException(
                    $this->dataHelper->setLogMessage(
                        'product %1 is a parent ID. Product variation is needed',
                        [$magentoProduct->getId()]
                    )
                );
            }
        }
        return $lengowProducts;
    }

    /**
     * Create quote
     *
     * @param MagentoCustomer $customer Magento customer instance
     * @param array $products Lengow products from Api
     *
     * @return Quote
     *
     * @throws Exception
     */
    private function createQuote(MagentoCustomer $customer, array $products): Quote
    {
        $customerRepo = $this->customerRepository->getById($customer->getId());
        $currentStore = $this->storeManager->getStore($this->storeId);
        $quote = $this->lengowQuoteFactory->create()
            ->setIsMultiShipping(false)
            ->setStore($currentStore)
            ->setInventoryProcessed(false);
        // import customer addresses into quote
        // set billing Address
        $customerBillingAddress = $this->addressRepository->getById($customerRepo->getDefaultBilling());
        $billingAddress = $this->quoteAddressFactory->create()
            ->setShouldIgnoreValidation(true)
            ->importCustomerAddressData($customerBillingAddress)
            ->setSaveInAddressBook(0);
        $customerShippingAddress = $this->addressRepository->getById($customerRepo->getDefaultShipping());
        $shippingAddress = $this->quoteAddressFactory->create()
            ->setShouldIgnoreValidation(true)
            ->importCustomerAddressData($customerShippingAddress)
            ->setSaveInAddressBook(0)
            ->setSameAsBilling(0);
        $quote->assignCustomerWithAddressChange($customerRepo, $billingAddress, $shippingAddress);
        // check if store include tax (Product and shipping cost)
        $priceIncludeTax = ($this->taxConfig->priceIncludesTax($quote->getStore())
                && $this->taxConfig->displayCartPricesInclTax($quote->getStore())
                && $this->taxConfig->displaySalesPricesInclTax($quote->getStore()));

        $shippingIncludeTax = ($this->taxConfig->shippingPriceIncludesTax($quote->getStore())
            && $this->taxConfig->displayCartShippingInclTax($quote->getSotre())
            && $this->taxConfig->displaySalesShippingInclTax($quote->getStore()));
        // if this order is b2b
        if ((int) $this->backendSession->getIsLengowB2b() === 1) {
            $priceIncludeTax = true;
            $shippingIncludeTax = true;
        }
        // add product in quote
        $quote->addLengowProducts($products, $priceIncludeTax);
        // get shipping cost with tax
        $shippingCost = $this->processingFee + $this->shippingCost;
        $taxShippingCost = 0.0;
        // if shipping cost not include tax -> get shipping cost without tax
        if (!$shippingIncludeTax) {
            $shippingTaxClass = $this->scopeConfig->getValue(
                TaxConfig::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
                $quote->getStore()->getWebsiteId()
            );

            $taxRate = $this->taxCalculation->getCalculatedRate(
                $shippingTaxClass,
                $customer->getId(),
                $currentStore->getId()
            );
            $taxShippingCost = $this->calculation->calcTaxAmount($shippingCost, $taxRate, true);
        }
        $shippingCost -= $taxShippingCost;
        $quoteShippingAddress = $quote->getShippingAddress();
        // update shipping rates for current order
        $quoteShippingAddress->setCollectShippingRates(true);
        $quoteShippingAddress->setTotalsCollectedFlag(false)->collectShippingRates();
        $rates = $quoteShippingAddress->getShippingRatesCollection();
        $shippingMethod = $this->updateRates($rates, round($shippingCost, 3));
        // set shipping price and shipping method for current order
        $quoteShippingAddress
            ->setShippingPrice(round($shippingCost, 3))
            ->setShippingMethod($shippingMethod);
        // get payment data
        $paymentInfo = '';
        if (!empty($this->orderData->payments)) {
            $payment = $this->orderData->payments[0];
            $paymentInfo .= ' - ' . $payment->type;
            if (isset($payment->payment_terms->external_transaction_id)) {
                $paymentInfo .= ' - ' . $payment->payment_terms->external_transaction_id;
            }
        }
        // set payment method lengow
        $quote->getPayment()->setMethod('lengow')->setAdditionnalInformation([
            'marketplace' => $this->orderData->marketplace . $paymentInfo,
        ]);
        $quote->collectTotals()->save();
        // stop order creation when a quote is empty
        if (!$quote->getAllVisibleItems()) {
            $quote->setIsActive(false);
            throw new LengowException(
                $this->dataHelper->setLogMessage('quote does not contain any valid products')
            );
        }
        if ($this->configHelper->get(ConfigHelper::CHECK_ROUNDING_ENABLED, $this->storeId)) {

            $hasAdjustedTaxes = $this->hasAdjustedQuoteTaxes($quote, $products);
            if ($hasAdjustedTaxes) {
                $this->dataHelper->setLogMessage('quote taxes has been adjusted');
            }
            $shippingQuoteCost = $quote->getShippingAddress()->getShippingInclTax();
            $shippingCostLengow = (float) $this->orderData->shipping ?? 0;
            if ($shippingCostLengow && $shippingCostLengow !== $shippingQuoteCost) {
                $deltaCost = $shippingCostLengow - $shippingQuoteCost;
                $quote->getShippingAddress()->setShippingPrice($shippingCost+ $deltaCost);
                $grandTotalQuote = $quote->getShippingAddress()->getGrandTotal() ;
                $baseGrandTotalQuote = $quote->getShippingAddress()->getBaseGrandTotal();
                // set shipping price and shipping method for current order
                $quote->getShippingAddress()
                    ->setShippingInclTax($shippingQuoteCost + $deltaCost)
                    ->setShippingAmount($shippingCost + $deltaCost)
                    ->setBaseGrandTotal($baseGrandTotalQuote + $deltaCost)
                    ->setGrandTotal($grandTotalQuote + $deltaCost);
                $quote->collectTotals()->save();
                $this->dataHelper->setLogMessage('quote shipping amount has been adjusted');
            }
        }

        $quote->save();
        return $quote;
    }

    /**
     * check taxes amount quote adjustment between lengow and magento
     *
     * @param Quote $quote
     * @param array $products
     *
     * @return bool
     */
    private function hasAdjustedQuoteTaxes($quote, $products): bool
    {

        $shippingAddress = $quote->getShippingAddress();
        $totalTaxQuote = (float) $shippingAddress->getTaxAmount();
        $totalTaxLengow = 0;
        $totalProducts = 0;
        $taxDiff = false;

        foreach ($quote->getAllVisibleItems() as $item) {
            if (isset($products[$item->getProductId()])) {

                if (!isset($products[$item->getProductId()])) {
                    $taxDiff = false;
                    continue;
                }

                $product = $products[$item->getProductId()];
                $totalTaxLengow += $product['tax_amount'];
                $totalProducts += $product['amount'];

                if (!$item->getTaxAmount() || !$product['tax_amount']) {
                    $taxDiff = false;
                    continue;
                }

                if (
                    $product['tax_amount'] === (float) $item->getTaxAmount()
                    && $product['amount'] === $item->getRowTotalInclTax()
                ) {
                    $taxDiff = false;
                    continue;
                }

                $taxDiff = true;
                $item->setTaxAmount($product['tax_amount']);
                $item->setBaseTaxAmount($product['tax_amount']);
                $item->setBaseRowTotal($product['amount'] - $product['tax_amount']);
                $item->setRowTotal($product['amount'] - $product['tax_amount']);
                $item->setRowTotalInclTax($product['amount']);
                $item->setPrice($product['price_unit']);
                $item->setPriceInclTax($product['amount']);
                $item->setBasePriceInclTax($product['amount']);
                $item->setCustomPrice($product['amount'] - $product['tax_amount']);
                $item->setOriginalCustomPrice($product['amount'] - $product['tax_amount']);
                $item->setBasePrice($product['amount'] - $product['tax_amount']);
                $item->setOriginalPrice($product['amount'] - $product['tax_amount']);
                $item->setBaseOriginalPrice($product['amount'] - $product['tax_amount']);
                $item->setBaseRowTotalInclTax($product['amount']);
                $item->save();
            }
        }

        if (!$taxDiff) {
            return false;
        }

        if (
            $totalTaxQuote === $totalTaxLengow
            && $totalProducts === $shippingAddress->getSubtotal()
        ) {
            return false;
        }
        $quote->collectTotals()->save();

        return true;
    }

    /**
     * Update Rates with shipping cost
     *
     * @param Collection $rates Magento rates
     * @param float $shippingCost shipping cost
     * @param string|null $shippingMethod Magento shipping method
     * @param boolean $first stop recursive effect
     *
     * @return boolean
     */
    private function updateRates(
        Collection $rates,
        float $shippingCost,
        string $shippingMethod = null,
        bool $first = true
    ) {
        if (!$shippingMethod) {
            $shippingMethod = $this->configHelper->get(ConfigHelper::DEFAULT_IMPORT_CARRIER_ID, $this->storeId);
        }
        if (empty($shippingMethod)) {
            $shippingMethod = 'lengow_lengow';
        }
        /** @var QuoteAddressRate $rate */
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
        $this->dataHelper->log(
            DataHelper::CODE_IMPORT,
            $this->dataHelper->setLogMessage(
                'the chosen shipping method is not available for this order. Lengow has assigned a shipping method'
            ),
            $this->logOutput,
            $this->marketplaceSku
        );
        return $this->updateRates($rates, $shippingCost, 'lengow_lengow', false);
    }

    /**
     * Create a Magento order from a quote
     *
     * @param Quote $quote Lengow quote instance
     * @param LengowOrder $orderLengow Lengow order instance
     *
     * @return MagentoOrder
     *
     * @throws Exception|LengowException
     */
    private function makeOrder(Quote $quote, Order $orderLengow): MagentoOrder
    {
        $currencyIsoA3 = (string) $this->orderData->currency->iso_a3;
        $additionalData = [
            'from_lengow' => true,
            'global_currency_code' => $currencyIsoA3,
            'base_currency_code' => $currencyIsoA3,
            'store_currency_code' => $currencyIsoA3,
            'order_currency_code' => $currencyIsoA3,
            'marketplace' => $orderLengow->getMarketplaceName(),
            'marketplace_number' =>  $orderLengow->getMarketplaceSku()

        ];
        try {
            $order = $this->quoteManagement->submit($quote, $additionalData);
        } catch (Exception $e) {
            // try to generate order with quote factory for "Cart does not contain item" Magento bug
            $magentoQuote = $this->quoteMagentoFactory->create()->load($quote->getId());
            $order = $this->quoteManagement->submit($magentoQuote, $additionalData);
        }
        if (!$order) {
            throw new LengowException(
                $this->dataHelper->setLogMessage('unable to create order based on given quote')
            );
        }
        $order->addData($additionalData);
        // modify order dates to use actual dates
        $order->setCreatedAt($this->orderDate);
        $order->setUpdatedAt($this->orderDate);
        $order->save();
        // update Lengow order record
        $orderLengow->updateOrder(
            [
                LengowOrder::FIELD_ORDER_ID => (int) $order->getId(),
                LengowOrder::FIELD_ORDER_SKU => $order->getIncrementId(),
                LengowOrder::FIELD_ORDER_PROCESS_STATE => $this->lengowOrder->getOrderProcessState(
                    $this->orderStateLengow
                ),
                LengowOrder::FIELD_ORDER_LENGOW_STATE => $this->orderStateLengow,
                LengowOrder::FIELD_IS_IN_ERROR => 0,
                LengowOrder::FIELD_IS_REIMPORTED => 0,
            ]
        );
        $this->dataHelper->log(
            DataHelper::CODE_IMPORT,
            $this->dataHelper->setLogMessage('order updated in Lengow orders table'),
            $this->logOutput,
            $this->marketplaceSku
        );
        // generate invoice for order
        if ($order->canInvoice()) {
            $this->lengowOrder->toInvoice($order);
        }
        $carrierName = $this->carrierName;
        if ($carrierName === null || $carrierName === 'None') {
            $carrierName = $this->carrierMethod;
        }
        $order->setShippingDescription(
            $order->getShippingDescription() . ' [marketplace shipping method : ' . $carrierName . ']'
        );

        $order->save();
        return $order;
    }

    /**
     * Save order line in lengow orders line table
     *
     * @param MagentoOrder $order Magento order instance
     * @param array $products Lengow products from Api
     */
    private function saveLengowOrderLine(MagentoOrder $order, array $products): void
    {
        $orderLineSaved = false;
        foreach ($products as $productId => $product) {
            foreach ($product['order_line_ids'] as $idOrderLine) {
                $orderLine = $this->lengowOrderLineFactory->create();
                $orderLine->createOrderLine(
                    [
                        LengowOrderLine::FIELD_ORDER_ID => (int) $order->getId(),
                        LengowOrderLine::FIELD_PRODUCT_ID => $productId,
                        LengowOrderLine::FIELD_ORDER_LINE_ID => $idOrderLine,
                    ]
                );
                $orderLineSaved .= !$orderLineSaved ? $idOrderLine : ' / ' . $idOrderLine;
            }
        }
        if ($orderLineSaved) {
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage('save order lines product: %1', [$orderLineSaved]),
                $this->logOutput,
                $this->marketplaceSku
            );
        } else {
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage(
                    'WARNING ! Order lines could NOT be saved in Lengow order lines table'
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
    }

    /**
     * Checks and places the order in complete status in Magento
     *
     * @param MagentoOrder $order Magento order instance
     *
     * @throws Exception
     */
    private function updateStateToShip(MagentoOrder $order): void
    {
        if ($this->orderStateLengow === LengowOrder::STATE_SHIPPED
            || $this->orderStateLengow === LengowOrder::STATE_CLOSED
        ) {
            $this->lengowOrder->toShip($order, $this->carrierName, $this->carrierMethod, $this->trackingNumber);
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage(
                    "order's status has been updated to %1",
                    [MagentoOrder::STATE_COMPLETE]
                ),
                $this->logOutput,
                $this->marketplaceSku
            );
        }
    }

    /**
     * Add quantity back to stock
     *
     * @param array $products Lengow products from Api
     */
    private function addQuantityBack(array $products): void
    {
        // add quantity back for re-imported order and order shipped by marketplace
        if ($this->isReimported
            || ($this->shippedByMp && !$this->configHelper->get(ConfigHelper::SHIPPED_BY_MARKETPLACE_STOCK_ENABLED))
        ) {
            $messageKey = $this->isReimported
                ? 'adding quantity back to stock count (order is re-imported)'
                : 'adding quantity back to stock count (order shipped by marketplace)';
            $this->dataHelper->log(
                DataHelper::CODE_IMPORT,
                $this->dataHelper->setLogMessage($messageKey),
                $this->logOutput,
                $this->marketplaceSku
            );
            foreach ($products as $productId => $product) {
                $this->stockManagement->backItemQty($productId, $product['quantity'], $this->storeId);
            }
        }
    }
}




