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
use Lengow\Connector\Model\ResourceModel\Ordererror as OrderResourceerror;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Lengow\Connector\Model\ResourceModel\Ordererror\CollectionFactory;
use Lengow\Connector\Model\Exception as LengowException;

/**
 * Model import importorder
 */
class Importorder extends AbstractModel
{

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context Magento context instance
     * @param \Magento\Framework\Registry $registry Magento registry instance
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime Magento datetime instance
     * @param \Lengow\Connector\Model\ResourceModel\Ordererror\CollectionFactory Lengow Ordererror $ordererrorCollection
     * @param \Lengow\Connector\Model\OrdererrorFactory Lengow Ordererror $ordererrorFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DateTime $dateTime,
        CollectionFactory $ordererrorCollection,
        OrdererrorFactory $ordererrorFactory
    )
    {
        parent::__construct($context, $registry);
        $this->_dateTime = $dateTime;
        $this->_ordererrorCollection = $ordererrorCollection;
        $this->_ordererrorFactory = $ordererrorFactory;
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
//        $importLog = $this->_modelOrder->orderIsInError(
//            $this->_marketplaceSku,
//            $this->_deliveryAddressId,
//            'import'
//        );
//        if ($importLog) {
//            $decodedMessage = $this->_helper->decodeLogMessage($importLog['message'], 'en_GB');
//            $this->_helper->log(
//                'Import',
//                $this->_helper->setLogMessage(
//                    'log.import.error_already_created',
//                    array(
//                        'decoded_message' => $decodedMessage,
//                        'date_message' => $importLog['created_at']
//                    )
//                ),
//                $this->_logOutput,
//                $this->_marketplaceSku
//            );
//            return false;
//        }
//        // recovery id if the command has already been imported
//        $orderId = $this->_modelOrder->getOrderIdIfExist(
//            $this->_marketplaceSku,
//            $this->_marketplace->name,
//            $this->_deliveryAddressId,
//            $this->_marketplace->legacyCode
//        );
//        // update order state if already imported
//        if ($orderId) {
//            $orderUpdated = $this->_checkAndUpdateOrder($orderId);
//            if ($orderUpdated && isset($orderUpdated['update'])) {
//                return $this->_returnResult('update', $orderUpdated['order_lengow_id'], $orderId);
//            }
//            if (!$this->_isReimported) {
//                return false;
//            }
//        }
//        // // checks if an external id already exists
//        $orderMagentoId = $this->_checkExternalIds($this->_orderData->merchant_order_id);
//        if ($orderMagentoId && !$this->_preprodMode && !$this->_isReimported) {
//            $this->_helper->log(
//                'Import',
//                $this->_helper->setLogMessage(
//                    'log.import.external_id_exist',
//                    array('order_id' => $orderMagentoId)
//                ),
//                $this->_logOutput,
//                $this->_marketplaceSku
//            );
//            return false;
//        }
//        // if order is cancelled or new -> skip
//        if (!$this->_importHelper->checkState($this->_orderStateMarketplace, $this->_marketplace)) {
//            $this->_helper->log(
//                'Import',
//                $this->_helper->setLogMessage(
//                    'log.import.current_order_state_unavailable',
//                    array(
//                        'order_state_marketplace' => $this->_orderStateMarketplace,
//                        'marketplace_name' => $this->_marketplace->name
//                    )
//                ),
//                $this->_logOutput,
//                $this->_marketplaceSku
//            );
//            return false;
//        }
//        // get a record in the lengow order table
//        $this->_orderLengowId = $this->_modelOrder->getLengowOrderId(
//            $this->_marketplaceSku,
//            $this->_deliveryAddressId
//        );
//        if (!$this->_orderLengowId) {
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
//        }
//        // load lengow order
//        $orderLengow = $this->_modelOrder->load((int)$this->_orderLengowId);
//        // checks if the required order data is present
//        if (!$this->_checkOrderData()) {
//            return $this->_returnResult('error', $this->_orderLengowId);
//        }
//        // get order amount and load processing fees and shipping cost
//        $this->_orderAmount = $this->_getOrderAmount();
//        // load tracking data
//        $this->_loadTrackingData();
//        // get customer name and email
//        $customerName = $this->_getCustomerName();
//        $customerEmail = (!is_null($this->_orderData->billing_address->email)
//            ? (string)$this->_orderData->billing_address->email
//            : (string)$this->_packageData->delivery->email
//        );
//        // update Lengow order with new informations
//        $orderLengow->updateOrder(
//            array(
//                'currency' => $this->_orderData->currency->iso_a3,
//                'total_paid' => $this->_orderAmount,
//                'order_item' => $this->_orderItems,
//                'customer_name' => $customerName,
//                'customer_email' => $customerEmail,
//                'commission' => (float)$this->_orderData->commission,
//                'carrier' => $this->_carrierName,
//                'method' => $this->_carrierMethod,
//                'tracking' => $this->_trackingNumber,
//                'sent_marketplace' => $this->_shippedByMp,
//                'delivery_country_iso' => $this->_packageData->delivery->common_country_iso_a2,
//                'order_lengow_state' => $this->_orderStateLengow
//            )
//        );
//        // try to import order
//        try {
//            // check if the order is shipped by marketplace
//            if ($this->_shippedByMp) {
//                $this->_helper->log(
//                    'Import',
//                    $this->_helper->setLogMessage(
//                        'log.import.order_shipped_by_marketplace',
//                        array('marketplace_name' => $this->_marketplace->name)
//                    ),
//                    $this->_logOutput,
//                    $this->_marketplaceSku
//                );
//                if (!$this->_configHelper->get('import_ship_mp_enabled', $this->_storeId)) {
//                    $orderLengow->updateOrder(
//                        array(
//                            'order_process_state' => 2,
//                            'extra' => Mage::helper('core')->jsonEncode($this->_orderData)
//                        )
//                    );
//                    return false;
//                }
//            }
//            // Create or Update customer with addresses
//            $customer = Mage::getModel('lengow/import_customer');
//            $customer->createCustomer(
//                $this->_orderData,
//                $this->_packageData->delivery,
//                $this->_storeId,
//                $this->_marketplaceSku,
//                $this->_logOutput
//            );
//            // Create Magento Quote
//            $quote = $this->_createQuote($customer);
//            // Create Magento order
//            $order = $this->_makeOrder($quote);
//            // If order is succesfully imported
//            if ($order) {
//                // Save order line id in lengow_order_line table
//                $orderLineSaved = $this->_saveLengowOrderLine($order, $quote);
//                $this->_helper->log(
//                    'Import',
//                    $this->_helper->setLogMessage(
//                        'log.import.lengow_order_line_saved',
//                        array('order_line_saved' => $orderLineSaved)
//                    ),
//                    $this->_logOutput,
//                    $this->_marketplaceSku
//                );
//                $this->_helper->log(
//                    'Import',
//                    $this->_helper->setLogMessage(
//                        'log.import.order_successfully_imported',
//                        array('order_id' => $order->getIncrementId())
//                    ),
//                    $this->_logOutput,
//                    $this->_marketplaceSku
//                );
//                // Update state to shipped
//                if ($this->_orderStateLengow == 'shipped' || $this->_orderStateLengow == 'closed') {
//                    $this->_modelOrder->toShip(
//                        $order,
//                        $this->_carrierName,
//                        $this->_carrierMethod,
//                        $this->_trackingNumber
//                    );
//                    $this->_helper->log(
//                        'Import',
//                        $this->_helper->setLogMessage(
//                            'log.import.order_state_updated',
//                            array('state_name' => 'Complete')
//                        ),
//                        $this->_logOutput,
//                        $this->_marketplaceSku
//                    );
//                }
//                // Update Lengow order record
//                $orderLengow->updateOrder(
//                    array(
//                        'order_id' => $order->getId(),
//                        'order_sku' => $order->getIncrementId(),
//                        'order_process_state' => $this->_modelOrder->getOrderProcessState($this->_orderStateLengow),
//                        'extra' => Mage::helper('core')->jsonEncode($this->_orderData),
//                        'order_lengow_state' => $this->_orderStateLengow,
//                        'is_in_error' => 0
//                    )
//                );
//                $this->_helper->log(
//                    'Import',
//                    $this->_helper->setLogMessage('log.import.lengow_order_updated'),
//                    $this->_logOutput,
//                    $this->_marketplaceSku
//                );
//            } else {
//                throw new Lengow_Connector_Model_Exception(
//                    $this->_helper->setLogMessage('lengow_log.exception.order_is_empty')
//                );
//            }
//            // add quantity back for re-import order and order shipped by marketplace
//            if ($this->_isReimported
//                || ($this->_shippedByMp && !$this->_configHelper->get('import_stock_ship_mp', $this->_storeId))
//            ) {
//                if ($this->_isReimported) {
//                    $logMessage = $this->_helper->setLogMessage('log.import.quantity_back_reimported_order');
//                } else {
//                    $logMessage = $this->_helper->setLogMessage('log.import.quantity_back_shipped_by_marketplace');
//                }
//                $this->_helper->log('Import', $logMessage, $this->_logOutput, $this->_marketplaceSku);
//                $this->_addQuantityBack($quote);
//            }
//            // Inactivate quote (Test)
//            $quote->setIsActive(false)->save();
//        } catch (Lengow_Connector_Model_Exception $e) {
//            $errorMessage = $e->getMessage();
//        } catch (Exception $e) {
//            $errorMessage = '[Magento error]: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
//        }
//        if (isset($errorMessage)) {
//            $orderError = Mage::getModel('lengow/import_ordererror');
//            $orderError->createOrderError(
//                array(
//                    'order_lengow_id' => $this->_orderLengowId,
//                    'message' => $errorMessage,
//                    'type' => 'import'
//                )
//            );
//            $decodedMessage = $this->_helper->decodeLogMessage($errorMessage, 'en_GB');
//            $this->_helper->log(
//                'Import',
//                $this->_helper->setLogMessage(
//                    'log.import.order_import_failed',
//                    array('decoded_message' => $decodedMessage)
//                ),
//                $this->_logOutput,
//                $this->_marketplaceSku
//            );
//            $orderLengow->updateOrder(
//                array(
//                    'extra' => Mage::helper('core')->jsonEncode($this->_orderData),
//                    'order_lengow_state' => $this->_orderStateLengow,
//                )
//            );
//            return $this->_returnResult('error', $this->_orderLengowId);
//        }
//        return $this->_returnResult('new', $this->_orderLengowId, $order->getId());
    }
}
