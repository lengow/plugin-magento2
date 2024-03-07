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
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Sales\Model\OrderFactory as MagentoOrderFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Model\Connector as LengowConnector;
use Lengow\Connector\Model\Exception as LengowException;
use Lengow\Connector\Model\ImportFactory as LengowImportFactory;
use Lengow\Connector\Model\Import as LengowImport;
use Lengow\Connector\Model\Import\Action as LengowAction;
use Lengow\Connector\Model\Import\ActionFactory as LengowActionFactory;
use Lengow\Connector\Model\Import\Importorder as LengowImportOrder;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;
use Lengow\Connector\Model\Import\Ordererror as LengowOrderError;
use Lengow\Connector\Model\Import\OrdererrorFactory as LengowOrderErrorFactory;
use Lengow\Connector\Model\Import\Orderline as LengowOrderLine;
use Lengow\Connector\Model\Import\OrderlineFactory as LengowOrderLineFactory;
use Lengow\Connector\Model\ResourceModel\Order as LengowOrderResource;
use Lengow\Connector\Model\ResourceModel\Order\CollectionFactory as LengowOrderCollectionFactory;
use Lengow\Connector\Model\ResourceModel\Ordererror\CollectionFactory as LengowOrderErrorCollectionFactory;

/**
 * Model import order
 */
class Order extends AbstractModel
{
    /**
     * @var string Lengow order table name
     */
    public const TABLE_ORDER = 'lengow_order';

    /* Order fields */
    public const FIELD_ID = 'id';
    public const FIELD_ORDER_ID = 'order_id';
    public const FIELD_ORDER_SKU = 'order_sku';
    public const FIELD_STORE_ID = 'store_id';
    public const FIELD_DELIVERY_ADDRESS_ID = 'delivery_address_id';
    public const FIELD_DELIVERY_COUNTRY_ISO = 'delivery_country_iso';
    public const FIELD_MARKETPLACE_SKU = 'marketplace_sku';
    public const FIELD_MARKETPLACE_NAME = 'marketplace_name';
    public const FIELD_MARKETPLACE_LABEL = 'marketplace_label';
    public const FIELD_ORDER_LENGOW_STATE = 'order_lengow_state';
    public const FIELD_ORDER_PROCESS_STATE = 'order_process_state';
    public const FIELD_ORDER_DATE = 'order_date';
    public const FIELD_ORDER_ITEM = 'order_item';
    public const FIELD_ORDER_TYPES = 'order_types';
    public const FIELD_CURRENCY = 'currency';
    public const FIELD_TOTAL_PAID = 'total_paid';
    public const FIELD_COMMISSION = 'commission';
    public const FIELD_CUSTOMER_NAME = 'customer_name';
    public const FIELD_CUSTOMER_EMAIL = 'customer_email';
    public const FIELD_CUSTOMER_VAT_NUMBER = 'customer_vat_number';
    public const FIELD_CARRIER = 'carrier';
    public const FIELD_CARRIER_METHOD = 'carrier_method';
    public const FIELD_CARRIER_TRACKING = 'carrier_tracking';
    public const FIELD_CARRIER_RELAY_ID = 'carrier_id_relay';
    public const FIELD_SENT_MARKETPLACE = 'sent_marketplace';
    public const FIELD_IS_IN_ERROR = 'is_in_error';
    public const FIELD_IS_REIMPORTED = 'is_reimported';
    public const FIELD_MESSAGE = 'message';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';
    public const FIELD_EXTRA = 'extra';
    public const FIELD_B2B_VALUE='B2B';

    /* Order process states */
    public const PROCESS_STATE_NEW = 0;
    public const PROCESS_STATE_IMPORT = 1;
    public const PROCESS_STATE_FINISH = 2;

    /* Order states */
    public const STATE_NEW = 'new';
    public const STATE_WAITING_ACCEPTANCE = 'waiting_acceptance';
    public const STATE_ACCEPTED = 'accepted';
    public const STATE_WAITING_SHIPMENT = 'waiting_shipment';
    public const STATE_SHIPPED = 'shipped';
    public const STATE_CLOSED = 'closed';
    public const STATE_REFUSED = 'refused';
    public const STATE_CANCELED = 'canceled';
    public const STATE_REFUNDED = 'refunded';
    public const STATE_PARTIALLY_REFUNDED = 'partial_refunded';

    /* Order types */
    public const TYPE_PRIME = 'is_prime';
    public const TYPE_EXPRESS = 'is_express';
    public const TYPE_BUSINESS = 'is_business';
    public const TYPE_DELIVERED_BY_MARKETPLACE = 'is_delivered_by_marketplace';

    /**
     * @var string label fulfillment for old orders without order type
     */
    public const LABEL_FULFILLMENT = 'Fulfillment';

    /**
     * @const number of tries to sync order num
     */
    public const SYNCHRONIZE_TRIES = 5;

    /**
     * @var MagentoOrderFactory Magento order factory instance
     */
    private $orderFactory;

    /**
     * @var InvoiceService Magento invoice service
     */
    private $invoiceService;

    /**
     * @var Transaction Magento transaction
     */
    private $transaction;

    /**
     * @var DateTime Magento datetime instance
     */
    private $dateTime;

    /**
     * @var ConvertOrder Magento convert order instance
     */
    private $convertOrder;

    /**
     * @var JsonHelper Magento json helper
     */
    private $jsonHelper;

    /**
     * @var TrackFactory Magento shipment track instance
     */
    protected $trackFactory;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var ImportHelper Lengow import helper instance
     */
    private $importHelper;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var LengowAction Lengow action instance
     */
    private $lengowAction;

    /**
     * @var LengowActionFactory Lengow action factory instance
     */
    private $lengowActionFactory;

    /**
     * @var LengowConnector Lengow connector instance
     */
    private $lengowConnector;

    /**
     * @var LengowImportFactory Lengow import factory instance
     */
    private $lengowImportFactory;

    /**
     * @var LengowOrderFactory Lengow order instance
     */
    private $lengowOrderFactory;

    /**
     * @var LengowOrderCollectionFactory Lengow order collection factory
     */
    private $lengowOrderCollection;

    /**
     * @var LengowOrderErrorFactory Lengow order error factory instance
     */
    private $lengowOrderErrorFactory;

    /**
     * @var LengowOrderErrorCollectionFactory Lengow order error collection factory
     */
    private $lengowOrderErrorCollection;

    /**
     * @var LengowOrderLineFactory Lengow order line factory instance
     */
    private $lengowOrderLineFactory;

    /**
     * @var SecurityHelper Lengow security helper instance
     */
    private $securityHelper;

    /**
     * @var array field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    private $fieldList = [
        self::FIELD_ORDER_ID => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_ORDER_SKU => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_STORE_ID => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_DELIVERY_ADDRESS_ID => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_DELIVERY_COUNTRY_ISO => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_MARKETPLACE_SKU => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_MARKETPLACE_NAME => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_MARKETPLACE_LABEL => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_ORDER_LENGOW_STATE => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_ORDER_PROCESS_STATE => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_ORDER_DATE => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_ORDER_ITEM => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_ORDER_TYPES => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_CURRENCY => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_TOTAL_PAID => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_COMMISSION => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_CUSTOMER_NAME => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_CUSTOMER_EMAIL => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_CUSTOMER_VAT_NUMBER => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_CARRIER => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_CARRIER_METHOD => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_CARRIER_TRACKING => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_CARRIER_RELAY_ID => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_SENT_MARKETPLACE => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_IS_IN_ERROR => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_IS_REIMPORTED => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_MESSAGE => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_EXTRA => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
    ];

    /**
     * Constructor
     *
     * @param MagentoOrderFactory $orderFactory Magento order factory instance
     * @param Context $context Magento context instance
     * @param Registry $registry Magento registry instance
     * @param InvoiceService $invoiceService Magento invoice service
     * @param Transaction $transaction Magento transaction
     * @param DateTime $dateTime Magento datetime instance
     * @param ConvertOrder $convertOrder Magento convert order instance
     * @param TrackFactory $trackFactory Magento shipment track factory instance
     * @param JsonHelper $jsonHelper Magento json helper
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ImportHelper $importHelper Lengow import helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param LengowOrderErrorFactory $lengowOrderErrorFactory Lengow order error factory instance
     * @param LengowOrderErrorCollectionFactory $lengowOrderErrorCollection Lengow order error collection instance
     * @param LengowOrderFactory $lengowOrderFactory Lengow order factory instance
     * @param LengowOrderCollectionFactory $lengowOrderCollection Lengow order collection factory instance
     * @param LengowOrderLineFactory $lengowOrderLineFactory Lengow order line factory instance
     * @param LengowActionFactory $lengowActionFactory Lengow action factory instance
     * @param LengowConnector $lengowConnector Lengow connector instance
     * @param LengowAction $lengowAction Lengow action instance
     * @param LengowImportFactory $lengowImportFactory Lengow import factory instance
     * @param SecurityHelper $securityHelper Lengow security helper
     * @param AbstractResource|null $resource Magento abstract resource instance
     * @param AbstractDb|null $resourceCollection Magento abstract db instance
     */
    public function __construct(
        MagentoOrderFactory $orderFactory,
        Context $context,
        Registry $registry,
        InvoiceService $invoiceService,
        Transaction $transaction,
        DateTime $dateTime,
        ConvertOrder $convertOrder,
        TrackFactory $trackFactory,
        JsonHelper $jsonHelper,
        DataHelper $dataHelper,
        ImportHelper $importHelper,
        ConfigHelper $configHelper,
        LengowOrderErrorFactory $lengowOrderErrorFactory,
        LengowOrderErrorCollectionFactory $lengowOrderErrorCollection,
        LengowOrderFactory $lengowOrderFactory,
        LengowOrderCollectionFactory $lengowOrderCollection,
        LengowOrderLineFactory $lengowOrderLineFactory,
        LengowActionFactory $lengowActionFactory,
        LengowConnector $lengowConnector,
        LengowAction $lengowAction,
        LengowImportFactory $lengowImportFactory,
        SecurityHelper $securityHelper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null
    ) {
        $this->orderFactory = $orderFactory;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->dateTime = $dateTime;
        $this->convertOrder = $convertOrder;
        $this->trackFactory = $trackFactory;
        $this->jsonHelper = $jsonHelper;
        $this->dataHelper = $dataHelper;
        $this->importHelper = $importHelper;
        $this->configHelper = $configHelper;
        $this->lengowOrderErrorFactory = $lengowOrderErrorFactory;
        $this->lengowOrderErrorCollection = $lengowOrderErrorCollection;
        $this->lengowOrderFactory = $lengowOrderFactory;
        $this->lengowOrderCollection = $lengowOrderCollection;
        $this->lengowOrderLineFactory = $lengowOrderLineFactory;
        $this->lengowActionFactory = $lengowActionFactory;
        $this->lengowConnector = $lengowConnector;
        $this->lengowAction = $lengowAction;
        $this->lengowImportFactory = $lengowImportFactory;
        $this->securityHelper = $securityHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection);
    }

    /**
     * Initialize order model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(LengowOrderResource::class);
    }

    /**
     * Create Lengow order
     *
     * @param array $params order parameters
     *
     * @return Order|false
     */
    public function createOrder(array $params = [])
    {
        foreach ($this->fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value[DataHelper::FIELD_REQUIRED]) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        if (!array_key_exists(self::FIELD_ORDER_PROCESS_STATE, $params)) {
            $this->setData(self::FIELD_ORDER_PROCESS_STATE, self::PROCESS_STATE_NEW);
        }
        if (!$this->getCreatedAt()) {
            $this->setData(self::FIELD_CREATED_AT, $this->dateTime->gmtDate(DataHelper::DATE_FULL));
        }
        try {
            return $this->save();
        } catch (Exception $e) {
            $errorMessage = '[Orm error]: "' . $e->getMessage() . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            $this->dataHelper->log(
                DataHelper::CODE_ORM,
                $this->dataHelper->setLogMessage('Error while inserting record in database - %1', [$errorMessage])
            );
            return false;
        }
    }

    /**
     * Update Lengow order
     *
     * @param array $params order parameters
     *
     * @return Order|false
     */
    public function updateOrder(array $params = [])
    {
        if (!$this->getId()) {
            return false;
        }
        $updatedFields = $this->getUpdatedFields();
        foreach ($params as $key => $value) {
            if (in_array($key, $updatedFields, true)) {
                $this->setData($key, $value);
            }
        }
        $this->setData(self::FIELD_UPDATED_AT, $this->dateTime->gmtDate(DataHelper::DATE_FULL));
        try {
            return $this->save();
        } catch (Exception $e) {
            $errorMessage = '[Orm error]: "' . $e->getMessage() . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
            $this->dataHelper->log(
                DataHelper::CODE_ORM,
                $this->dataHelper->setLogMessage('Error while inserting record in database - %1', [$errorMessage])
            );
            return false;
        }
    }

    /**
     * Get updated fields
     *
     * @return array
     */
    public function getUpdatedFields(): array
    {
        $updatedFields = [];
        foreach ($this->fieldList as $key => $value) {
            if ($value[DataHelper::FIELD_CAN_BE_UPDATED]) {
                $updatedFields[] = $key;
            }
        }
        return $updatedFields;
    }

    /**
     * Check if order is express
     *
     * @return boolean
     */
    public function isExpress(): bool
    {
        $orderTypes = (string) $this->getData(self::FIELD_ORDER_TYPES);
        $orderTypes = $orderTypes !== '' ? json_decode($orderTypes, true) : [];
        return isset($orderTypes[self::TYPE_EXPRESS]) || isset($orderTypes[self::TYPE_PRIME]);
    }

    /**
     * Check if order is B2B
     *
     * @param array $paymentInfo
     *
     * @return boolean
     */
    public function isBusiness(): bool
    {
        $orderTypes = (string) $this->getData(self::FIELD_ORDER_TYPES);
        $orderTypes = $orderTypes !== '' ? json_decode($orderTypes, true) : [];

        if (isset($orderTypes[self::TYPE_BUSINESS])) {
            return true;
        }

        $extraData = json_decode($this->getData(self::FIELD_EXTRA), true);
        $paymentInfo = $extraData['payments'][0] ?? [];
        $billingInfo = $extraData['billing_address'] ?? [];
        if (isset($paymentInfo['payment_terms'])) {
            $fiscalNumber = $paymentInfo['payment_terms']['fiscalnb'] ?? '';
            $vatNumber   = $paymentInfo['payment_terms']['vat_number'] ?? '';
            $siretNumber = $paymentInfo['payment_terms']['siret_number'] ?? '';

            if (!empty($fiscalNumber)
                    || !empty($vatNumber)
                    || !empty($siretNumber)) {
                $this->setData(
                    self::FIELD_ORDER_TYPES,
                    json_encode([self::TYPE_BUSINESS => self::FIELD_B2B_VALUE])
                )->save();
                return true;
            }
        }
        if (!empty($billingInfo['vat_number'])
            && !empty($billingInfo['company'])) {
            $this->setData(
                self::FIELD_ORDER_TYPES,
                json_encode([self::TYPE_BUSINESS => self::FIELD_B2B_VALUE])
            )->save();
            return true;
        }

        return false;
    }

    /**
     * Check if order is delivered by marketplace
     *
     * @return boolean
     */
    public function isDeliveredByMarketplace(): bool
    {
        $orderTypes = (string) $this->getData(self::FIELD_ORDER_TYPES);
        $orderTypes = $orderTypes !== '' ? json_decode($orderTypes, true) : [];
        return isset($orderTypes[self::TYPE_DELIVERED_BY_MARKETPLACE]) || $this->getData(self::FIELD_SENT_MARKETPLACE);
    }

    /**
     * Check if an order has an error
     *
     * @param string $marketplaceSku marketplace sku
     * @param string $marketplaceName marketplace name
     * @param int $type order error type (import or send)
     *
     * @return array|false
     */
    public function orderIsInError(
        string $marketplaceSku,
        string $marketplaceName,
        int $type = LengowOrderError::TYPE_ERROR_IMPORT
    ) {
        // check if log already exists for the given order id
        $results = $this->lengowOrderErrorCollection->create()
            ->join(
                self::TABLE_ORDER,
                '`lengow_order`.id=main_table.order_lengow_id',
                [
                    self::FIELD_MARKETPLACE_SKU => self::FIELD_MARKETPLACE_SKU,
                    self::FIELD_MARKETPLACE_NAME => self::FIELD_MARKETPLACE_NAME,
                ]
            )
            ->addFieldToFilter(self::FIELD_MARKETPLACE_SKU, $marketplaceSku)
            ->addFieldToFilter(self::FIELD_MARKETPLACE_NAME, $marketplaceName)
            ->addFieldToFilter(LengowOrderError::FIELD_TYPE, $type)
            ->addFieldToFilter(LengowOrderError::FIELD_IS_FINISHED, ['eq' => 0])
            ->addFieldToSelect(LengowOrderError::FIELD_ID)
            ->addFieldToSelect(LengowOrderError::FIELD_MESSAGE)
            ->addFieldToSelect(LengowOrderError::FIELD_CREATED_AT)
            ->load()
            ->getData();
        if (empty($results)) {
            return false;
        }
        return $results[0];
    }

    /**
     * If order is already Imported
     *
     * @param string $marketplaceSku marketplace sku
     * @param string $marketplaceName marketplace name
     *
     * @return integer|false
     */
    public function getOrderIdIfExist(string $marketplaceSku, string $marketplaceName)
    {
        // get order id Magento from our table
        $results = $this->lengowOrderCollection->create()
            ->addFieldToFilter(self::FIELD_MARKETPLACE_SKU, $marketplaceSku)
            ->addFieldToFilter(self::FIELD_MARKETPLACE_NAME, $marketplaceName)
            ->addFieldToSelect(self::FIELD_ORDER_ID)
            ->load()
            ->getData();
        if (!empty($results)) {
            return $results[0][self::FIELD_ORDER_ID];
        }
        return false;
    }

    /**
     * Get Lengow ID with order ID Magento and delivery address ID
     *
     * @param integer $orderId Magento order id
     * @param string $deliveryAddressId delivery address id
     *
     * @return string|false
     */
    public function getOrderIdWithDeliveryAddress(int $orderId, string $deliveryAddressId)
    {
        $results = $this->lengowOrderCollection->create()
            ->addFieldToFilter(self::FIELD_ORDER_ID, $orderId)
            ->addFieldToFilter(self::FIELD_DELIVERY_ADDRESS_ID, $deliveryAddressId)
            ->addFieldToSelect(self::FIELD_ID)
            ->getData();
        if (!empty($results)) {
            return $results[0][self::FIELD_ID];
        }
        return false;
    }

    /**
     * Get ID record from lengow orders table
     *
     * @param string $marketplaceSku marketplace sku
     * @param string $marketplaceName marketplace name
     * @param integer $deliveryAddressId delivery address id
     *
     * @return integer|false
     */
    public function getLengowOrderId(string $marketplaceSku, string $marketplaceName)
    {
        $results = $this->lengowOrderCollection->create()
            ->addFieldToFilter(self::FIELD_MARKETPLACE_SKU, $marketplaceSku)
            ->addFieldToFilter(self::FIELD_MARKETPLACE_NAME, $marketplaceName)
            ->addFieldToSelect(self::FIELD_ID)
            ->getData();
        if (!empty($results)) {
            return (int) $results[0][self::FIELD_ID];
        }
        return false;
    }

    /**
     * Get ID record from lengow orders table with Magento order Id
     *
     * @param integer $orderId Magento order id
     *
     * @return integer|false
     */
    public function getLengowOrderIdWithOrderId(int $orderId)
    {
        $results = $this->lengowOrderCollection->create()
            ->addFieldToFilter(self::FIELD_ORDER_ID, $orderId)
            ->addFieldToSelect(self::FIELD_ID)
            ->getData();
        if (!empty($results)) {
            return (int) $results[0][self::FIELD_ID];
        }
        return false;
    }

    /**
     * Get Magento equivalent to lengow order state
     *
     * @param string $orderStateLengow Lengow state
     *
     * @return string
     */
    public function getOrderState(string $orderStateLengow): string
    {
        switch ($orderStateLengow) {
            case self::STATE_NEW:
            case self::STATE_WAITING_ACCEPTANCE:
                return MagentoOrder::STATE_NEW;
            case self::STATE_ACCEPTED:
            case self::STATE_WAITING_SHIPMENT:
                return MagentoOrder::STATE_PROCESSING;
            case self::STATE_SHIPPED:
            case self::STATE_CLOSED:
                return MagentoOrder::STATE_COMPLETE;
            case self::STATE_REFUSED:
            case self::STATE_CANCELED:
                return MagentoOrder::STATE_CANCELED;
            default:
                return '';
        }
    }

    /**
     * Update order state to marketplace state
     *
     * @param MagentoOrder|OrderInterface $order Magento order instance
     * @param Order $lengowOrder Lengow order instance
     * @param string $orderStateLengow lengow order status
     * @param mixed $packageData package data
     *
     * @return string|false
     */
    public function updateState($order, Order $lengowOrder, string $orderStateLengow, $packageData)
    {
        // finish actions if lengow order is shipped, closed, cancel or refunded
        $orderProcessState = $this->getOrderProcessState($orderStateLengow);
        $tracks = $packageData->delivery->trackings;
        if ($orderProcessState === self::PROCESS_STATE_FINISH) {
            $this->lengowActionFactory->create()->finishAllActions($order->getId());
            $this->lengowOrderErrorFactory->create()->finishOrderErrors(
                $lengowOrder->getId(),
                LengowOrderError::TYPE_ERROR_SEND
            );
        }
        // update Lengow order if necessary
        $params = [];
        if ($lengowOrder->getData(self::FIELD_ORDER_PROCESS_STATE) !== $orderStateLengow) {
            $params[self::FIELD_ORDER_LENGOW_STATE] = $orderStateLengow;
            $params[self::FIELD_CARRIER_TRACKING] = !empty($tracks) ? (string) $tracks[0]->number : null;
        }
        if ($orderProcessState === self::PROCESS_STATE_FINISH) {
            if ((int) $lengowOrder->getData(self::FIELD_ORDER_PROCESS_STATE) !== $orderProcessState) {
                $params[self::FIELD_ORDER_PROCESS_STATE] = $orderProcessState;
            }
            if ($lengowOrder->getData(self::FIELD_IS_IN_ERROR)) {
                $params[self::FIELD_IS_IN_ERROR] = 0;
            }
        }
        if (!empty($params)) {
            $lengowOrder->updateOrder($params);
        }
        try {
            // update Magento order's status only if in accepted, waiting_shipment, shipped, closed or cancel
            if ($order->getData('from_lengow')
                && $order->getState() !== $this->getOrderState($orderStateLengow)
            ) {
                if (($orderStateLengow === self::STATE_SHIPPED || $orderStateLengow === self::STATE_CLOSED)
                    && ($order->getState() === $this->getOrderState(self::STATE_ACCEPTED)
                        || $order->getState() === $this->getOrderState(self::STATE_NEW)
                    )
                ) {
                    if (!empty($tracks)) {
                        $tracking = $tracks[0];
                        $carrierName = $tracking->carrier;
                        $carrierMethod = $tracking->method;
                        $trackingNumber = $tracking->number;
                    }
                    $this->toShip($order, $carrierName ?? null, $carrierMethod ?? null, $trackingNumber ?? null);
                    return MagentoOrder::STATE_COMPLETE;
                }
                if (($orderStateLengow === self::STATE_CANCELED || $orderStateLengow === self::STATE_REFUSED)
                    && ($order->getState() === $this->getOrderState(self::STATE_NEW)
                        || $order->getState() === $this->getOrderState(self::STATE_ACCEPTED)
                        || $order->getState() === $this->getOrderState(self::STATE_SHIPPED)
                    )
                ) {
                    $this->toCancel($order);
                    return MagentoOrder::STATE_CANCELED;
                }
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * Cancel order
     *
     * @param MagentoOrder $order Magento order instance
     */
    public function toCancel(MagentoOrder $order): void
    {
        if ($order->canCancel()) {
            $order->cancel();
        }
    }

    /**
     * Create invoice
     *
     * @param MagentoOrder|OrderInterface $order Magento order instance
     *
     * @throws Exception
     */
    public function toInvoice($order): void
    {
        $invoice = $this->invoiceService->prepareInvoice($order);
        if ($invoice) {
            $invoice->register();
            $invoice->getOrder()->setIsInProcess(true);
            $invoice->setState(Invoice::STATE_PAID);
            $transactionSave = $this->transaction->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            );
            $transactionSave->save();
        }
    }

    /**
     * Ship order
     *
     * @param MagentoOrder|OrderInterface $order Magento order instance
     * @param string|null $carrierName carrier name
     * @param string|null $carrierMethod carrier method
     * @param string|null $trackingNumber tracking number
     *
     * @throws Exception
     */
    public function toShip(
        $order,
        string $carrierName = null,
        string $carrierMethod = null,
        string $trackingNumber = null
    ): void {
        if ($order->canShip()) {
            $shipment = $this->convertOrder->toShipment($order);
            if ($shipment) {
                if ($this->configHelper->moduleIsEnabled('Magento_Inventory')
                    && version_compare($this->securityHelper->getMagentoVersion(), '2.3.0', '>=')
                ) {
                    $shipment->getExtensionAttributes()->setSourceCode('default');
                }
                foreach ($order->getAllItems() as $orderItem) {
                    if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                        continue;
                    }
                    $qtyShipped = $orderItem->getQtyToShip();
                    $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);
                    $shipment->addItem($shipmentItem);
                }
                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);
                // add tracking information
                if ($trackingNumber !== null && $trackingNumber !== '') {
                    $title = $carrierName;
                    if ($title === null || $title === 'None') {
                        $title = $carrierMethod;
                    }
                    $track = $this->trackFactory->create()
                        ->setNumber($trackingNumber)
                        ->setCarrierCode(Track::CUSTOM_CARRIER_CODE)
                        ->setTitle($title);
                    $shipment->addTrack($track);
                }
                $shipment->save();
                $shipment->getOrder()->save();
            }
        }
    }

    /**
     * Get marketplace sku by Magento order id from lengow orders table
     *
     * @param integer $orderId Magento order id
     *
     * @return string|false
     */
    public function getMarketplaceSkuByOrderId(int $orderId)
    {
        $results = $this->lengowOrderCollection->create()
            ->addFieldToFilter(self::FIELD_ORDER_ID, $orderId)
            ->addFieldToSelect(self::FIELD_MARKETPLACE_SKU)
            ->getData();
        if (!empty($results)) {
            return $results[0][self::FIELD_MARKETPLACE_SKU];
        }
        return false;
    }

    /**
     * Get Lengow Order by Magento order id from lengow orders table
     *
     * @param integer $orderId Magento order id
     *
     * @return integer|false
     */
    public function getLengowOrderIdByOrderId(int $orderId)
    {
        $results = $this->lengowOrderCollection->create()
            ->addFieldToFilter(self::FIELD_ORDER_ID, $orderId)
            ->getData();
        if (!empty($results)) {
            return (int) $results[0][self::FIELD_ID];
        }
        return false;
    }

    /**
     * Get all unset orders
     *
     * @return array|false
     */
    public function getUnsentOrders()
    {
        $results = $this->lengowOrderCollection->create()
            ->join(
                ['magento_order' => 'sales_order'],
                'magento_order.entity_id=main_table.order_id',
                [
                    'store_id' => 'store_id',
                    'updated_at' => 'updated_at',
                    'state' => 'state',
                ]
            )
            ->addFieldToFilter('magento_order.updated_at', ['from' => strtotime('-5 days'), 'datetime' => true])
            ->addFieldToFilter('magento_order.state', [['in' => ['cancel', 'complete']]])
            ->addFieldToFilter('main_table.order_process_state', ['eq' => 1])
            ->addFieldToFilter('main_table.is_in_error', ['eq' => 0])
            ->getData();
        if (!empty($results)) {
            return $results;
        }
        return false;
    }

    /**
     * Re-import order lengow
     *
     * @param integer $orderLengowId Lengow order id
     *
     * @return array|false
     */
    public function reImportOrder(int $orderLengowId)
    {
        $orderLengow = $this->lengowOrderFactory->create()->load($orderLengowId);
        if ((int) $orderLengow->getData(self::FIELD_ORDER_PROCESS_STATE) === 0
            && $orderLengow->getData(self::FIELD_IS_IN_ERROR)
        ) {
            $params = [
                LengowImport::PARAM_TYPE => LengowImport::TYPE_MANUAL,
                LengowImport::PARAM_ORDER_LENGOW_ID => $orderLengowId,
                LengowImport::PARAM_MARKETPLACE_SKU => $orderLengow->getData(self::FIELD_MARKETPLACE_SKU),
                LengowImport::PARAM_MARKETPLACE_NAME => $orderLengow->getData(self::FIELD_MARKETPLACE_NAME),
                LengowImport::PARAM_DELIVERY_ADDRESS_ID => $orderLengow->getData(self::FIELD_DELIVERY_ADDRESS_ID),
                LengowImport::PARAM_STORE_ID => $orderLengow->getData(self::FIELD_STORE_ID),
            ];
            $lengowImport = $this->lengowImportFactory->create();
            $lengowImport->init($params);
            return $lengowImport->exec();
        }
        return false;
    }

    /**
     * Re-send order lengow
     *
     * @param integer $orderLengowId Lengow order id
     *
     * @return boolean
     */
    public function reSendOrder(int $orderLengowId): bool
    {
        $orderLengow = $this->lengowOrderFactory->create()->load($orderLengowId);
        if ((int) $orderLengow->getData(self::FIELD_ORDER_PROCESS_STATE) === 1
            && $orderLengow->getData(self::FIELD_IS_IN_ERROR)
        ) {
            $orderId = $orderLengow->getData(self::FIELD_ORDER_ID);
            if ($orderId !== null) {
                $order = $this->orderFactory->create()->load($orderId);
                $action = $this->lengowAction->getLastOrderActionType($orderId);
                if (!$action) {
                    $action = $order->getData('status') === self::STATE_CANCELED
                        ? LengowAction::TYPE_CANCEL
                        : LengowAction::TYPE_SHIP;
                }
                /** @var Shipment|void $shipment */
                $shipment = $order->getShipmentsCollection()->getFirstItem();
                return $this->callAction($action, $order, $shipment);
            }
        }
        return false;
    }

    /**
     * Get order process state
     *
     * @param string $state state to be matched
     *
     * @return integer|false
     */
    public function getOrderProcessState(string $state)
    {
        switch ($state) {
            case self::STATE_ACCEPTED:
            case self::STATE_WAITING_SHIPMENT:
                return self::PROCESS_STATE_IMPORT;
            case self::STATE_SHIPPED:
            case self::STATE_CLOSED:
            case self::STATE_REFUSED:
            case self::STATE_CANCELED:
            case self::STATE_REFUNDED:
                return self::PROCESS_STATE_FINISH;
            default:
                return false;
        }
    }

    /**
     * Cancel and re-import order
     *
     * @param MagentoOrder $order Magento order instance
     * @param Order $lengowOrder Lengow order instance
     *
     * @return integer|false
     */
    public function cancelAndReImportOrder(MagentoOrder $order, Order $lengowOrder)
    {
        if (!$this->isReimported($lengowOrder)) {
            return false;
        }
        $params = [
            LengowImport::PARAM_MARKETPLACE_SKU => $lengowOrder->getData(self::FIELD_MARKETPLACE_SKU),
            LengowImport::PARAM_MARKETPLACE_NAME => $lengowOrder->getData(self::FIELD_MARKETPLACE_NAME),
            LengowImport::PARAM_DELIVERY_ADDRESS_ID => $lengowOrder->getData(self::FIELD_DELIVERY_ADDRESS_ID),
            LengowImport::PARAM_STORE_ID => $lengowOrder->getData(self::FIELD_STORE_ID),
        ];
        $lengowImport = $this->lengowImportFactory->create();
        $lengowImport->init($params);
        $result = $lengowImport->exec();
        if (!empty($result[LengowImport::ORDERS_CREATED])) {
            $orderCreated = $result[LengowImport::ORDERS_CREATED][0];
            if ($orderCreated[LengowImportOrder::MERCHANT_ORDER_ID] !== (int) $order->getData('order_id')) {
                try {
                    // if state != STATE_COMPLETE or != STATE_CLOSED
                    $order->setState('lengow_technical_error')->setStatus('lengow_technical_error');
                    $order->save();
                } catch (Exception $e) {
                    $errorMessage = '[Orm error]: "' . $e->getMessage()
                        . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
                    $this->dataHelper->log(
                        DataHelper::CODE_ORM,
                        $this->dataHelper->setLogMessage(
                            'Error while inserting record in database - %1',
                            [$errorMessage]
                        )
                    );
                }
                return $orderCreated[LengowImportOrder::MERCHANT_ORDER_ID];
            }
        }
        // in the event of an error, all new order errors are finished and the order is reset
        $this->lengowOrderErrorFactory->create()->finishOrderErrors($lengowOrder->getId());
        $lengowOrder->updateOrder(
            [
                self::FIELD_ORDER_ID => $order->getId(),
                self::FIELD_ORDER_SKU => $order->getIncrementId(),
                self::FIELD_IS_REIMPORTED => 0,
                self::FIELD_IS_IN_ERROR => 0,
            ]
        );
        return false;
    }

    /**
     * Mark Lengow order as is_reimported in lengow_order table
     *
     * @param Order $lengowOrder Lengow order instance
     *
     * @return boolean
     */
    public function isReimported(Order $lengowOrder): bool
    {
        $lengowOrder->updateOrder([self::FIELD_IS_REIMPORTED => 1]);
        // check success update in database
        if ($lengowOrder->getData(self::FIELD_IS_REIMPORTED)) {
            return true;
        }
        return false;
    }

    /**
     * Send Order action
     *
     * @param string $action Lengow Actions (ship or cancel)
     * @param MagentoOrder $order Magento order instance
     * @param Shipment|null $shipment Magento Shipment instance
     *
     * @return boolean
     */
    public function callAction(string $action, MagentoOrder $order, Shipment $shipment = null): bool
    {
        $success = true;
        if (!(bool) $order->getData('from_lengow')) {
            return false;
        }
        $lengowOrderId = $this->getLengowOrderIdByOrderId($order->getId());
        if (!$lengowOrderId) {
            return false;
        }
        $lengowOrder = $this->lengowOrderFactory->create()->load($lengowOrderId);
        $this->dataHelper->log(
            DataHelper::CODE_ACTION,
            $this->dataHelper->setLogMessage(
                'try to send %1 action (ORDER ID %2)',
                [$action, $order->getIncrementId()]
            ),
            false,
            $lengowOrder->getData(self::FIELD_MARKETPLACE_SKU)
        );
        // finish all order errors before API call
        $this->lengowOrderErrorFactory->create()->finishOrderErrors(
            $lengowOrder->getId(),
            LengowOrderError::TYPE_ERROR_SEND
        );
        if ($lengowOrder->getData(self::FIELD_IS_IN_ERROR)) {
            $lengowOrder->updateOrder([self::FIELD_IS_IN_ERROR => 0]);
        }
        try {
            $marketplace = $this->importHelper->getMarketplaceSingleton(
                $lengowOrder->getData(self::FIELD_MARKETPLACE_NAME)
            );
            if ($marketplace->containOrderLine($action)) {
                $orderLineCollection = $this->lengowOrderLineFactory->create()->getOrderLineByOrderID($order->getId());
                // get order line ids by API for security
                if (!$orderLineCollection) {
                    $orderLineCollection = $this->getOrderLineByApi(
                        $lengowOrder->getData(self::FIELD_MARKETPLACE_SKU),
                        $lengowOrder->getData(self::FIELD_MARKETPLACE_NAME),
                        (int) $lengowOrder->getData(self::FIELD_DELIVERY_ADDRESS_ID)
                    );
                }
                if (!$orderLineCollection) {
                    throw new LengowException(
                        $this->dataHelper->setLogMessage('order line is required but not found in the order')
                    );
                }
                $results = [];
                foreach ($orderLineCollection as $orderLine) {
                    $results[] = $marketplace->callAction(
                        $action,
                        $order,
                        $lengowOrder,
                        $shipment,
                        $orderLine[LengowOrderLine::FIELD_ORDER_LINE_ID]
                    );
                }
                $success = !in_array(false, $results, true);
            } else {
                $success = $marketplace->callAction($action, $order, $lengowOrder, $shipment);
            }
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Magento error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            if ((int) $lengowOrder->getData(self::FIELD_ORDER_PROCESS_STATE) !== self::PROCESS_STATE_FINISH) {
                $lengowOrder->updateOrder([self::FIELD_IS_IN_ERROR => 1]);
                $orderError = $this->lengowOrderErrorFactory->create();
                $orderError->createOrderError(
                    [
                        LengowOrderError::FIELD_ORDER_LENGOW_ID => $lengowOrder->getId(),
                        LengowOrderError::FIELD_MESSAGE => $errorMessage,
                        LengowOrderError::FIELD_TYPE => LengowOrderError::TYPE_ERROR_SEND,
                    ]
                );
                unset($orderError);
            }
            $decodedMessage = $this->dataHelper->decodeLogMessage($errorMessage, false);
            $this->dataHelper->log(
                DataHelper::CODE_ACTION,
                $this->dataHelper->setLogMessage('order action failed - %1', [$decodedMessage]),
                false,
                $lengowOrder->getData(self::FIELD_MARKETPLACE_SKU)
            );
            $success = false;
        }
        if ($success) {
            $message = $this->dataHelper->setLogMessage(
                'action %1 successfully sent (ORDER ID %2)',
                [$action, $order->getIncrementId()]
            );
        } else {
            $message = $this->dataHelper->setLogMessage(
                'WARNING! action %1 could not be sent (ORDER ID %2)',
                [$action, $order->getIncrementId()]
            );
        }
        $this->dataHelper->log(
            DataHelper::CODE_ACTION,
            $message,
            false,
            $lengowOrder->getData(self::FIELD_MARKETPLACE_SKU)
        );
        return $success;
    }

    /**
     * Get order line by API
     *
     * @param string $marketplaceSku marketplace sku
     * @param string $marketplaceName marketplace name
     * @param integer $deliveryAddressId delivery address id
     *
     * @return array|false
     */
    public function getOrderLineByApi(string $marketplaceSku, string $marketplaceName, int $deliveryAddressId)
    {
        $orderLines = [];
        $results = $this->lengowConnector->queryApi(
            LengowConnector::GET,
            LengowConnector::API_ORDER,
            [
                LengowImport::ARG_MARKETPLACE_ORDER_ID => $marketplaceSku,
                LengowImport::ARG_MARKETPLACE => $marketplaceName,
            ]
        );
        if (!isset($results->results) || (isset($results->count) && (int) $results->count === 0)) {
            return false;
        }
        $orderData = $results->results[0];
        foreach ($orderData->packages as $package) {
            $productLines = [];
            foreach ($package->cart as $product) {
                $productLines[] = [
                    LengowOrderLine::FIELD_ORDER_LINE_ID => (string) $product->marketplace_order_line_id,
                ];
            }
            $orderLines[(int) $package->delivery->id] = $productLines;
        }
        return $orderLines[$deliveryAddressId] ?? false;
    }

    /**
     * Get order ids from lengow order ID
     *
     * @param string $marketplaceSku marketplace sku
     * @param string $marketplaceName marketplace name
     *
     * @return array|false
     */
    public function getAllOrderIds(string $marketplaceSku, string $marketplaceName)
    {
        $results = $this->lengowOrderCollection->create()
            ->addFieldToFilter(self::FIELD_MARKETPLACE_SKU, $marketplaceSku)
            ->addFieldToFilter(self::FIELD_MARKETPLACE_NAME, $marketplaceName)
            ->addFieldToSelect(self::FIELD_ORDER_ID)
            ->getData();
        if (!empty($results)) {
            return $results;
        }
        return false;
    }

    /**
     * Get all Lengow order ids
     *
     * @return array|false
     */
    public function getAllLengowOrderIds()
    {
        $results = $this->lengowOrderCollection->create()
            ->addFieldToSelect(self::FIELD_ID)
            ->getData();
        if (!empty($results)) {
            return $results;
        }
        return false;
    }

    /**
     * Retrieves all the Lengow order from a marketplace reference
     *
     * @param string $marketplaceSku marketplace sku
     * @param string $marketplaceName marketplace name
     *
     * @return array
     */
    public function getAllLengowOrders(string $marketplaceSku, string $marketplaceName): array
    {
        $results = $this->lengowOrderCollection->create()
            ->addFieldToFilter(self::FIELD_MARKETPLACE_SKU, $marketplaceSku)
            ->addFieldToFilter(self::FIELD_MARKETPLACE_NAME, $marketplaceName)
            ->getData();
        return !empty($results) ? $results : [];
    }

    /**
     * Synchronize order with Lengow API
     *
     * @param Order $lengowOrder Lengow order instance
     * @param LengowConnector|null $connector
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function synchronizeOrder(
        Order $lengowOrder,
        LengowConnector $connector = null,
        bool $logOutput = false
    ): bool {
        list($accountId, $accessToken, $secretToken) = $this->configHelper->getAccessIds();
        if ($connector === null) {
            if ($this->lengowConnector->isValidAuth($logOutput)) {
                $this->lengowConnector->init(['access_token' => $accessToken, 'secret' => $secretToken]);
            } else {
                return false;
            }
        }
        $orderIds = $this->getAllOrderIds(
            $lengowOrder->getData(self::FIELD_MARKETPLACE_SKU),
            $lengowOrder->getData(self::FIELD_MARKETPLACE_NAME)
        );
        if (empty($orderIds)) {
            return false;
        }

        $magentoIds = [];
        foreach ($orderIds as $orderId) {
            $magentoIds[] = (int) $orderId[self::FIELD_ORDER_ID];
        }
        $tries = self::SYNCHRONIZE_TRIES;
        do {
            try {
                $body = [
                    LengowImport::ARG_ACCOUNT_ID => $accountId,
                    LengowImport::ARG_MARKETPLACE_ORDER_ID => $lengowOrder->getData(self::FIELD_MARKETPLACE_SKU),
                    LengowImport::ARG_MARKETPLACE => $lengowOrder->getData(self::FIELD_MARKETPLACE_NAME),
                    LengowImport::ARG_MERCHANT_ORDER_ID => $magentoIds,
                ];
                $result = $this->lengowConnector->patch(
                    LengowConnector::API_ORDER_MOI,
                    [],
                    LengowConnector::FORMAT_JSON,
                    $this->jsonHelper->jsonEncode($body),
                    $logOutput
                );
                return !($result === null
                || (isset($result['detail']) && $result['detail'] === 'Pas trouvé.')
                || isset($result['error']));
            } catch (Exception $e) {
                $tries --;
                if ($tries === 0) {
                    $message = $this->dataHelper->decodeLogMessage($e->getMessage(), false);
                    $error = $this->dataHelper->setLogMessage('API call failed - %1 - %2', [$e->getCode(), $message]);
                    $this->dataHelper->log(DataHelper::CODE_CONNECTOR, $error, $logOutput);
                }
                usleep(250000);
            }
        } while ($tries > 0);

        return false;
    }

    /**
     * Count order imported by Lengow in Magento
     *
     * @return integer
     */
    public function countOrderImportedByLengow(): int
    {
        $results = $this->lengowOrderCollection->create()
            ->join(['magento_order' => 'sales_order'], 'magento_order.entity_id=main_table.order_id')
            ->addFieldToSelect(self::FIELD_ID)
            ->getData();
        return count($results);
    }

    /**
     * Count order lengow with error
     *
     * @return integer
     */
    public function countOrderWithError(): int
    {
        $results = $this->lengowOrderCollection->create()
            ->addFieldToFilter(self::FIELD_IS_IN_ERROR, 1)
            ->addFieldToSelect(self::FIELD_ID)
            ->getData();
        return count($results);
    }

    /**
     * Count order lengow to be sent
     *
     * @return integer
     */
    public function countOrderToBeSent(): int
    {
        $results = $this->lengowOrderCollection->create()
            ->addFieldToFilter(self::FIELD_ORDER_PROCESS_STATE, 1)
            ->addFieldToSelect(self::FIELD_ID)
            ->getData();
        return count($results);
    }
}


