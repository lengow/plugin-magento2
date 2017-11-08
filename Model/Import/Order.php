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

use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionMagento;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Lengow\Connector\Model\ResourceModel\Ordererror\CollectionFactory as OrdererrorCollectionFactory;
use Lengow\Connector\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Model\ResourceModel\Order as OrderResource;
use Lengow\Connector\Model\Connector;
use Lengow\Connector\Model\Exception as LengowException;
use Lengow\Connector\Model\ResourceModel\OrderlineFactory;
use Lengow\Connector\Model\ResourceModel\ActionFactory as ResourceActionFactory;

/**
 * Model import order
 */
class Order extends AbstractModel
{
    /**
     * @var integer order process state for new order not imported
     */
    const PROCESS_STATE_NEW = 0;

    /**
     * @var integer order process state for order imported
     */
    const PROCESS_STATE_IMPORT = 1;

    /**
     * @var integer order process state for order finished
     */
    const PROCESS_STATE_FINISH = 2;

    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory Magento shipment track instance
     */
    protected $_trackFactory;

    /**
     * @var \Magento\Sales\Model\Convert\Order Magento convert order instance
     */
    protected $_convertOrder;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var \Magento\Framework\DB\Transaction Magento transaction
     */
    protected $_transaction;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService Magento invoice service
     */
    protected $_invoiceService;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory Magento order collection factory
     */
    protected $_orderCollectionMagento;

    /**
     * @var \Lengow\Connector\Model\Import\OrdererrorFactory Lengow order error factory instance
     */
    protected $_orderErrorFactory;

    /**
     * @var \Lengow\Connector\Model\ResourceModel\Ordererror\CollectionFactory Lengow order error collection factory
     */
    protected $_orderErrorCollection;

    /**
     * @var \Lengow\Connector\Model\ResourceModel\ActionFactory Lengow orderline factory instance
     */
    protected $_actionFactory;

    /**
     * @var \Lengow\Connector\Model\ResourceModel\OrderlineFactory Lengow orderline factory instance
     */
    protected $_lengowOrderlineFactory;

    /**
     * @var \Lengow\Connector\Model\Import\OrderFactory Lengow order instance
     */
    protected $_lengowOrderFactory;

    /**
     * @var \Lengow\Connector\Model\ResourceModel\Order\CollectionFactory Lengow order collection factory
     */
    protected $_orderCollection;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Import Lengow import helper instance
     */
    protected $_importHelper;

    /**
     * @var \Lengow\Connector\Model\Connector Lengow connector instance
     */
    protected $_connector;

    /**
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = [
        'order_id' => ['required' => false, 'updated' => true],
        'order_sku' => ['required' => false, 'updated' => true],
        'store_id' => ['required' => true, 'updated' => false],
        'delivery_address_id' => ['required' => true, 'updated' => false],
        'delivery_country_iso' => ['required' => false, 'updated' => true],
        'marketplace_sku' => ['required' => true, 'updated' => false],
        'marketplace_name' => ['required' => true, 'updated' => false],
        'marketplace_label' => ['required' => true, 'updated' => false],
        'order_lengow_state' => ['required' => true, 'updated' => true],
        'order_process_state' => ['required' => false, 'updated' => true],
        'order_date' => ['required' => true, 'updated' => false],
        'order_item' => ['required' => false, 'updated' => true],
        'currency' => ['required' => false, 'updated' => true],
        'total_paid' => ['required' => false, 'updated' => true],
        'commission' => ['required' => false, 'updated' => true],
        'customer_name' => ['required' => false, 'updated' => true],
        'customer_email' => ['required' => false, 'updated' => true],
        'carrier' => ['required' => false, 'updated' => true],
        'carrier_method' => ['required' => false, 'updated' => true],
        'carrier_tracking' => ['required' => false, 'updated' => true],
        'carrier_id_relay' => ['required' => false, 'updated' => true],
        'sent_marketplace' => ['required' => false, 'updated' => true],
        'is_in_error' => ['required' => false, 'updated' => true],
        'message' => ['required' => true, 'updated' => true],
        'extra' => ['required' => false, 'updated' => true]
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context Magento context instance
     * @param \Magento\Framework\Registry $registry Magento registry instance
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService Magento invoice service
     * @param \Magento\Framework\DB\Transaction $transaction Magento transaction
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime Magento datetime instance
     * @param \Magento\Sales\Model\Convert\Order $convertOrder Magento convert order instance
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory Magento shipment track factory instance
     * @param \Lengow\Connector\Model\Import\OrdererrorFactory $orderErrorFactory Lengow order error factory instance
     * @param \Lengow\Connector\Model\ResourceModel\Ordererror\CollectionFactory $orderErrorCollection
     * @param \Lengow\Connector\Model\ResourceModel\Order\CollectionFactory $orderCollection
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionMagento
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Import $importHelper Lengow import helper instance
     * @param \Lengow\Connector\Model\Connector $connector Lengow connector instance
     * @param \Lengow\Connector\Model\Import\OrderFactory $lengowOrderFactory Lengow order factory instance
     * @param \Lengow\Connector\Model\ResourceModel\OrderlineFactory $orderLineFactory Lengow orderline factory instance
     * @param \Lengow\Connector\Model\ResourceModel\ActionFactory $actionFactory Lengow action factory instance
     */
    public function __construct(
        Context $context,
        Registry $registry,
        InvoiceService $invoiceService,
        Transaction $transaction,
        DateTime $dateTime,
        ConvertOrder $convertOrder,
        TrackFactory $trackFactory,
        OrdererrorFactory $orderErrorFactory,
        OrdererrorCollectionFactory $orderErrorCollection,
        OrderCollectionFactory $orderCollection,
        OrderCollectionMagento $orderCollectionMagento,
        DataHelper $dataHelper,
        ImportHelper $importHelper,
        Connector $connector,
        OrderFactory $lengowOrderFactory,
        OrderlineFactory $orderLineFactory,
        ResourceActionFactory $actionFactory
    )
    {
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_dateTime = $dateTime;
        $this->_convertOrder = $convertOrder;
        $this->_trackFactory = $trackFactory;
        $this->_orderErrorFactory = $orderErrorFactory;
        $this->_orderErrorCollection = $orderErrorCollection;
        $this->_orderCollection = $orderCollection;
        $this->_orderCollectionMagento = $orderCollectionMagento;
        $this->_dataHelper = $dataHelper;
        $this->_importHelper = $importHelper;
        $this->_connector = $connector;
        $this->_lengowOrderFactory = $lengowOrderFactory;
        $this->_lengowOrderlineFactory = $orderLineFactory;
        $this->_actionFactory = $actionFactory;
        parent::__construct($context, $registry);
    }

    /**
     * Initialize order model
     **
     * @return void
     */
    protected function _construct()
    {
        $this->_init(OrderResource::class);
    }

    /**
     * Create Lengow order
     *
     * @param array $params order parameters
     *
     * @throws LengowException value required
     *
     * @return Order
     */
    public function createOrder($params = [])
    {
        foreach ($this->_fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value['required']) {
                throw new LengowException(
                    $this->_dataHelper->setLogMessage(
                        '%1 is required to create Lengow order',
                        $key
                    )
                );
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        if (!array_key_exists('order_process_state', $params)) {
            $this->setData('order_process_state', self::PROCESS_STATE_NEW);
        }
        if (!$this->getCreatedAt()) {
            $this->setData('created_at', $this->_dateTime->gmtDate('Y-m-d H:i:s'));
        }
        return $this->save();
    }

    /**
     * Update Lengow order
     *
     * @param array $params order parameters
     *
     * @return Order|false
     */
    public function updateOrder($params = [])
    {
        if (!$this->getId()) {
            return false;
        }
        $updatedFields = $this->getUpdatedFields();
        foreach ($params as $key => $value) {
            if (in_array($key, $updatedFields)) {
                $this->setData($key, $value);
            }
        }
        $this->setData('updated_at', $this->_dateTime->gmtDate('Y-m-d H:i:s'));
        return $this->save();
    }

    /**
     * Get updated fields
     *
     * @return array
     */
    public function getUpdatedFields()
    {
        $updatedFields = [];
        foreach ($this->_fieldList as $key => $value) {
            if ($value['updated']) {
                $updatedFields[] = $key;
            }
        }
        return $updatedFields;
    }

    /**
     * Check if an order has an error
     *
     * @param string $marketplaceSku marketplace sku
     * @param integer $deliveryAddressId delivery address id
     * @param string $type order error type (import or send)
     *
     * @return array|false
     */
    public function orderIsInError($marketplaceSku, $deliveryAddressId, $type = 'import')
    {
        $errorType = $this->_orderErrorFactory->create()->getOrderErrorType($type);
        // check if log already exists for the given order id
        $results = $this->_orderErrorCollection->create()
            ->join(
                'lengow_order',
                '`lengow_order`.id=main_table.order_lengow_id',
                ['marketplace_sku' => 'marketplace_sku', 'delivery_address_id' => 'delivery_address_id']
            )
            ->addFieldToFilter('marketplace_sku', $marketplaceSku)
            ->addFieldToFilter('delivery_address_id', $deliveryAddressId)
            ->addFieldToFilter('type', $errorType)
            ->addFieldToFilter('is_finished', ['eq' => 0])
            ->addFieldToSelect('id')
            ->addFieldToSelect('message')
            ->addFieldToSelect('created_at')
            ->load()
            ->getData();
        if (count($results) == 0) {
            return false;
        }
        return $results[0];
    }

    /**
     * if order is already Imported
     *
     * @param string $marketplaceSku marketplace sku
     * @param string $marketplaceName marketplace name
     * @param integer $deliveryAddressId delivery address id
     *
     * @return integer|false
     */
    public function getOrderIdIfExist($marketplaceSku, $marketplaceName, $deliveryAddressId)
    {
        // get order id Magento from our table
        $results = $this->_orderCollection->create()
            ->addFieldToFilter('order_sku', $marketplaceSku)
            ->addFieldToFilter('marketplace_name', ['in' => $marketplaceName])
            ->addFieldToFilter('delivery_address_id', $deliveryAddressId)
            ->addFieldToSelect('order_id')
            ->load()
            ->getData();

        if (count($results) > 0) {
            return $results[0]['order_id'];
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
    public function getOrderIdWithDeliveryAddress($orderId, $deliveryAddressId)
    {
        $results = $this->_orderCollection->create()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('delivery_address_id', $deliveryAddressId)
            ->addFieldToSelect('id')
            ->getData();
        if (count($results) > 0) {
            return $results[0]['id'];
        }
        return false;
    }

    /**
     * Get ID record from lengow orders table
     *
     * @param string $marketplaceSku marketplace sku
     * @param integer $deliveryAddressId delivery address id
     *
     * @return integer|false
     */
    public function getLengowOrderId($marketplaceSku, $deliveryAddressId)
    {
        $results = $this->_orderCollection->create()
            ->addFieldToFilter('marketplace_sku', $marketplaceSku)
            ->addFieldToFilter('delivery_address_id', $deliveryAddressId)
            ->addFieldToSelect('id')
            ->getData();
        if (count($results) > 0) {
            return (int)$results[0]['id'];
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
    public function getLengowOrderIdWithOrderId($orderId)
    {
        $results = $this->_orderCollection->create()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToSelect('id')
            ->getData();
        if (count($results) > 0) {
            return (int)$results[0]['id'];
        }
        return false;
    }

    /**
     * Get Magento equivalent to lengow order state
     *
     * @param  string $orderStateLengow Lengow state
     *
     * @return integer
     */
    public function getOrderState($orderStateLengow)
    {
        switch ($orderStateLengow) {
            case 'new':
            case 'waiting_acceptance':
                return \Magento\Sales\Model\Order::STATE_NEW;
            case 'accepted':
            case 'waiting_shipment':
                return \Magento\Sales\Model\Order::STATE_PROCESSING;
            case 'shipped':
            case 'closed':
                return \Magento\Sales\Model\Order::STATE_COMPLETE;
            case 'refused':
            case 'canceled':
                return \Magento\Sales\Model\Order::STATE_CANCELED;
        }
    }

    /**
     * Update order state to marketplace state
     *
     * @param \Magento\Sales\Model\Order $order Magento order instance
     * @param string $orderStateLengow lengow order status
     * @param mixed $orderData order data
     * @param mixed $packageData package data
     * @param mixed $orderLengowId lengow order id or false
     *
     * @return string|false
     */
    public function updateState($order, $orderStateLengow, $orderData, $packageData, $orderLengowId)
    {
        // Finish actions if lengow order is shipped, closed or cancel
        $orderProcessState = $this->getOrderProcessState($orderStateLengow);
        $trackings = $packageData->delivery->trackings;
        if ($orderProcessState == self::PROCESS_STATE_FINISH) {
            $this->_actionFactory->create()->finishAllActions($order->getId());
        }
        // Update Lengow order if necessary
        if ($orderLengowId) {
            $orderLengow = $this->_lengowOrderFactory->create()->load($orderLengowId);
            $params = [];
            if ($orderLengow->getData('order_lengow_state') != $orderStateLengow) {
                $params['order_lengow_state'] = $orderStateLengow;
                $params['extra'] = json_encode($orderData);
                $params['tracking'] = count($trackings) > 0 ? (string)$trackings[0]->number : null;
            }
            if ($orderProcessState == self::PROCESS_STATE_FINISH) {
                if ((int)$orderLengow->getData('order_process_state') != $orderProcessState) {
                    $params['order_process_state'] = $orderProcessState;
                }
                if ((int)$orderLengow->getData('is_in_error') != 0) {
                    $params['is_in_error'] = 0;
                }
            }
            if (count($params) > 0) {
                $orderLengow->updateOrder($params);
            }
            unset($orderLengow);
        }
        // Update Magento order's status only if in accepted, waiting_shipment, shipped, closed or cancel
        if ($order->getState() != $this->getOrderState($orderStateLengow) && $order->getData('from_lengow') == 1) {
            if (($order->getState() == $this->getOrderState('accepted')
                    || $order->getState() == $this->getOrderState('new'))
                && ($orderStateLengow == 'shipped' || $orderStateLengow == 'closed')
            ) {
                $this->toShip(
                    $order,
                    (count($trackings) > 0 ? (string)$trackings[0]->carrier : null),
                    (count($trackings) > 0 ? (string)$trackings[0]->method : null),
                    (count($trackings) > 0 ? (string)$trackings[0]->number : null)
                );
                return 'Complete';
            } else {
                if (($order->getState() == $this->getOrderState('new')
                        || $order->getState() == $this->getOrderState('accepted')
                        || $order->getState() == $this->getOrderState('shipped'))
                    && ($orderStateLengow == 'canceled' || $orderStateLengow == 'refused')
                ) {
                    $this->toCancel($order);
                    return 'Canceled';
                }
            }
        }
        return false;
    }

    /**
     * Cancel order
     *
     * @param \Magento\Sales\Model\Order $order Magento order instance
     */
    public function toCancel($order)
    {
        if ($order->canCancel()) {
            $order->cancel();
        }
    }

    /**
     * Create invoice
     *
     * @param \Magento\Sales\Model\Order $order Magento order instance
     */
    public function toInvoice($order)
    {
        $invoice = $this->_invoiceService->prepareInvoice($order);
        if ($invoice) {
            $invoice->register();
            $invoice->getOrder()->setIsInProcess(true);
            $invoice->setState(\Magento\Sales\Model\Order\Invoice::STATE_PAID);
            $transactionSave = $this->_transaction->addObject(
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
     * @param \Magento\Sales\Model\Order $order Magento order instance
     * @param string $carrierName carrier name
     * @param string $carrierMethod carrier method
     * @param string $trackingNumber tracking number
     */
    public function toShip($order, $carrierName, $carrierMethod, $trackingNumber)
    {
        if ($order->canShip()) {
            $shipment = $this->_convertOrder->toShipment($order);
            if ($shipment) {
                foreach ($order->getAllItems() AS $orderItem) {
                    if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                        continue;
                    }

                    $qtyShipped = $orderItem->getQtyToShip();
                    $shipmentItem = $this->_convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);
                    $shipment->addItem($shipmentItem);
                }
                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);
                // Add tracking information
                if (!is_null($trackingNumber)) {
                    $track = $this->_trackFactory->create()
                        ->setNumber($trackingNumber)
                        ->setCarrierCode($carrierName)
                        ->setTitle($carrierMethod);
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
    public function getMarketplaceSkuByOrderId($orderId)
    {
        $results = $this->_orderCollection->create()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToSelect('marketplace_sku')
            ->getData();
        if (count($results) > 0) {
            return $results[0]['marketplace_sku'];
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
    public function getLengowOrderIdByOrderId($orderId)
    {
        $results = $this->_orderCollection->create()
            ->addFieldToFilter('order_id', $orderId)
            ->getData();
        if (count($results) > 0) {
            return (int)$results[0]['id'];
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
    public function getOrderProcessState($state)
    {
        switch ($state) {
            case 'accepted':
            case 'waiting_shipment':
                return self::PROCESS_STATE_IMPORT;
            case 'shipped':
            case 'closed':
            case 'refused':
            case 'canceled':
                return self::PROCESS_STATE_FINISH;
            default:
                return false;
        }
    }

    /**
     * Send Order action
     *
     * @param string $action Lengow Actions (ship or cancel)
     * @param \Magento\Sales\Model\Order $order Magento order instance
     * @param \Magento\Sales\Model\Order\Shipment $shipment Magento Shipment instance
     *
     * @throws LengowException order line is required
     *
     * @return boolean
     */
    public function callAction($action, $order, $shipment = null)
    {
        $success = true;
        if ($order->getData('from_lengow') != 1) {
            return false;
        }
        $lengowOrderId = $this->getLengowOrderIdByOrderId($order->getId());
        if (!$lengowOrderId) {
            return false;
        }
        $lengowOrder = $this->_lengowOrderFactory->create()->load($lengowOrderId);
        $this->_dataHelper->log(
            'API-OrderAction',
            $this->_dataHelper->setLogMessage(
                'try to send %1 action (ORDER ID %2)',
                [$action, $order->getIncrementId()]
            ),
            false,
            $lengowOrder->getData('marketplace_sku')
        );
        // Finish all order errors before API call
        $this->_orderErrorFactory->create()->finishOrderErrors($lengowOrder->getId(), 'send');
        if ($lengowOrder->getData('is_in_error') == 1) {
            $lengowOrder->updateOrder(['is_in_error' => 0]);
        }
        try {
            $marketplace = $this->_importHelper->getMarketplaceSingleton($lengowOrder->getData('marketplace_name'));
            if ($marketplace->containOrderLine($action)) {
                $orderLineCollection = $this->_lengowOrderlineFactory->create()->getOrderLineByOrderID($order->getId());
                // Get order line ids by API for security
                if (!$orderLineCollection) {
                    $orderLineCollection = $this->getOrderLineByApi(
                        $lengowOrder->getData('marketplace_sku'),
                        $lengowOrder->getData('marketplace_name'),
                        (int)$lengowOrder->getData('delivery_address_id')
                    );
                }
                if (!$orderLineCollection) {
                    throw new LengowException(
                        $this->_dataHelper->setLogMessage('order line is required but not found in the order')
                    );
                }
                $results = [];
                foreach ($orderLineCollection as $orderLine) {
                    $results[] = $marketplace->callAction(
                        $action,
                        $order,
                        $lengowOrder,
                        $shipment,
                        $orderLine['order_line_id']
                    );
                }
                $success = !in_array(false, $results);
            } else {
                $success = $marketplace->callAction($action, $order, $lengowOrder, $shipment);
            }
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (\Exception $e) {
            $errorMessage = 'Magento error: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            if ((int)$lengowOrder->getData('order_process_state') != self::PROCESS_STATE_FINISH) {
                $lengowOrder->updateOrder(['is_in_error' => 1]);
                $orderError = $this->_orderErrorFactory->create();
                $orderError->createOrderError(
                    [
                        'order_lengow_id' => $lengowOrder->getId(),
                        'message' => $errorMessage,
                        'type' => 'send'
                    ]
                );
                unset($orderError);
            }
            $decodedMessage = $this->_dataHelper->decodeLogMessage($errorMessage, 'en_GB');
            $this->_dataHelper->log(
                'API-OrderAction',
                $this->_dataHelper->setLogMessage('order action failed - %1', [$decodedMessage]),
                false,
                $lengowOrder->getData('marketplace_sku')
            );
            $success = false;
        }
        if ($success) {
            $message = $this->_dataHelper->setLogMessage(
                'action %1 successfully sent (ORDER ID %2)',
                [$action, $order->getIncrementId()]
            );
        } else {
            $message = $this->_dataHelper->setLogMessage(
                'WARNING! action %1 could not be sent (ORDER ID %2)',
                [$action, $order->getIncrementId()]
            );
        }
        $this->_dataHelper->log('API-OrderAction', $message, false, $lengowOrder->getData('marketplace_sku'));
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
    public function getOrderLineByApi($marketplaceSku, $marketplaceName, $deliveryAddressId)
    {
        $orderLines = [];
        $results = $this->_connector->queryApi(
            'get',
            '/v3.0/orders',
            [
                'marketplace_order_id' => $marketplaceSku,
                'marketplace' => $marketplaceName
            ]
        );
        if (!isset($results->results) || (isset($results->count) && $results->count == 0)) {
            return false;
        }
        $orderData = $results->results[0];
        foreach ($orderData->packages as $package) {
            $productLines = [];
            foreach ($package->cart as $product) {
                $productLines[] = ['order_line_id' => (string)$product->marketplace_order_line_id];
            }
            $orderLines[(int)$package->delivery->id] = $productLines;
        }
        $return = isset($orderLines[$deliveryAddressId]) ? $orderLines[$deliveryAddressId] : [];
        return count($return) > 0 ? $return : false;
    }
}
