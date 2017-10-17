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
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionMagento;
use Lengow\Connector\Model\ResourceModel\Ordererror\CollectionFactory as OrdererrorCollectionFactory;
use Lengow\Connector\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

/**
 * Model import order
 */
class Order extends AbstractModel
{

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
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context Magento context instance
     * @param \Magento\Framework\Registry $registry Magento registry instance
     * @param \Lengow\Connector\Model\Import\Ordererror $orderError Lengow orderError instance
     * @param \Lengow\Connector\Model\ResourceModel\Ordererror\CollectionFactory $ordererrorCollection Lengow ordererror collection factory
     * @param \Lengow\Connector\Model\ResourceModel\Order\CollectionFactory $orderCollection Lengow order collection factory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionMagento Magento order collection factory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Ordererror $orderError,
        OrdererrorCollectionFactory $ordererrorCollection,
        OrderCollectionFactory $orderCollection,
        OrderCollectionMagento $orderCollectionMagento
    )
    {
        parent::__construct($context, $registry);
        $this->_orderError = $orderError;
        $this->_ordererrorCollection = $ordererrorCollection;
        $this->_orderCollection = $orderCollection;
        $this->_orderCollectionMagento = $orderCollectionMagento;
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

}
