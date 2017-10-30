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
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionMagento;
use Lengow\Connector\Model\ResourceModel\Ordererror\CollectionFactory as OrdererrorCollectionFactory;
use Lengow\Connector\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Model\Connector;
use Lengow\Connector\Model\Exception as LengowException;

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
     * @var \Magento\Framework\DB\Transaction Magento transaction
     */
    protected $_transaction;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService Magento invoice service
     */
    protected $_invoiceService;

    /**
     * @var \Lengow\Connector\Model\Import\Ordererror Lengow ordererror instance
     */
    protected $_orderError;

    /**
     * @var \Lengow\Connector\Model\ResourceModel\Ordererror\CollectionFactory Lengow order error collection factory
     */
    protected $_ordererrorCollection;

    /**
     * @var \Lengow\Connector\Model\ResourceModel\Order\CollectionFactory Lengow order collection factory
     */
    protected $_orderCollection;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory Magento order collection factory
     */
    protected $_orderCollectionMagento;

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
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context Magento context instance
     * @param \Magento\Framework\Registry $registry Magento registry instance
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService Magento invoice service
     * @param \Magento\Framework\DB\Transaction $transaction Magento transaction
     * @param \Lengow\Connector\Model\Import\Ordererror $orderError Lengow orderError instance
     * @param \Lengow\Connector\Model\ResourceModel\Ordererror\CollectionFactory $ordererrorCollection
     * @param \Lengow\Connector\Model\ResourceModel\Order\CollectionFactory $orderCollection
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionMagento
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Import $importHelper Lengow import helper instance
     * @param \Lengow\Connector\Model\Connector $connector Lengow connector instance
     */
    public function __construct(
        Context $context,
        Registry $registry,
        InvoiceService $invoiceService,
        Transaction $transaction,
        Ordererror $orderError,
        OrdererrorCollectionFactory $ordererrorCollection,
        OrderCollectionFactory $orderCollection,
        OrderCollectionMagento $orderCollectionMagento,
        DataHelper $dataHelper,
        ImportHelper $importHelper,
        Connector $connector
    ) {
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_orderError = $orderError;
        $this->_ordererrorCollection = $ordererrorCollection;
        $this->_orderCollection = $orderCollection;
        $this->_orderCollectionMagento = $orderCollectionMagento;
        $this->_dataHelper = $dataHelper;
        $this->_importHelper = $importHelper;
        $this->_connector = $connector;
        parent::__construct($context, $registry);
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
        $errorType = $this->_orderError->getOrderErrorType($type);
        // check if log already exists for the given order id
        $results = $this->_ordererrorCollection->create()
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
     * @return \Lengow\Connector\Model\Import\Order|false
     */
    public function getLengowOrderByOrderId($orderId)
    {
        $results = $this->_orderCollection->create()
            ->addFieldToFilter('order_id', $orderId)
            ->load();
        if (count($results) > 0) {
            return $results->getFirstItem();
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

        // TODO Check is a order from Lengow
        /*if ($order->getData('from_lengow') != 1) {
            return false;
        }*/

        $lengowOrder = $this->getLengowOrderByOrderId($order->getId());
        if (!$lengowOrder) {
            return false;
        }
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
        $this->_orderError->finishOrderErrors($lengowOrder->getId(), 'send');

        // TODO Delete is in error in lengow order
        /*if ($lengowOrder->getData('is_in_error') == 1) {
            $lengowOrder->updateOrder(['is_in_error' => 0]);
        }*/

        try {
            $marketplace = $this->_importHelper->getMarketplaceSingleton($lengowOrder->getData('marketplace_name'));
            if ($marketplace->containOrderLine($action)) {

                // TODO get all order lines from lengow table

                $orderLineCollection = false;
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
            $errorMessage = '[Magento error]: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            if ((int)$lengowOrder->getData('order_process_state') != self::PROCESS_STATE_FINISH) {

                // TODO update is in error in lengow order
                // $lengowOrder->updateOrder(['is_in_error' => 1]);

                $this->_orderError->createOrderError(
                    [
                        'order_lengow_id' => $lengowOrder->getId(),
                        'message' => $errorMessage,
                        'type' => 'send'
                    ]
                );
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
