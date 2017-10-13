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
use Lengow\Connector\Model\Exception as LengowException;
use Lengow\Connector\Model\Import\Order as ModelOrder;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Import\Marketplace;

/**
 * Model import importorder
 */
class Importorder extends AbstractModel
{

    /**
     * @var \Lengow\Connector\Model\Import\Order Lengow order instance
     */
    protected $_modelOrder;

    /**
     * @var \Lengow\Connector\Helper\Import Lengow import helper instance
     */
    protected $_importHelper;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

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
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context Magento context instance
     * @param \Magento\Framework\Registry $registry Magento registry instance
     * @param \Lengow\Connector\Model\Import\Order $modelOrder Lengow order instance
     * @param \Lengow\Connector\Helper\Import $importHelper Lengow import helper instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ModelOrder $modelOrder,
        ImportHelper $importHelper,
        DataHelper $dataHelper
    )
    {
        $this->_modelOrder = $modelOrder;
        $this->_importHelper = $importHelper;
        $this->_dataHelper = $dataHelper;
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
        echo "<br />if log import exist and not finished";
        $importLog = $this->_modelOrder->orderIsInError(
            $this->_marketplaceSku,
            $this->_deliveryAddressId,
            'import'
        );
        if (false/*$importLog*/) {
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
        $orderId = $this->_modelOrder->getOrderIdIfExist(
            $this->_marketplaceSku,
            $this->_marketplace->name,
            $this->_deliveryAddressId
        );
        echo "plop";
        var_dump($orderId);
        // update order state if already imported
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
        // try to import order
        try {
            //TODO
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (\Exception $e) {
            $errorMessage = '[Magento error]: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
        }
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
