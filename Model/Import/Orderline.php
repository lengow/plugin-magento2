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
use Lengow\Connector\Model\Import\OrderlineFactory as LengowOrderlineFactory;
use Lengow\Connector\Model\ResourceModel\Orderline\CollectionFactory as OrderlineCollectionFactory;
use Lengow\Connector\Model\ResourceModel\Orderline as OrderlineResource;


/**
 * Model import orderline
 */
class Orderline extends AbstractModel
{

    /**
     * @var \Lengow\Connector\Model\ResourceModel\Orderline\CollectionFactory Lengow Orderline collection factory
     */
    protected $_orderlineCollectionFactory;

    /**
     * @var \Lengow\Connector\Model\Import\OrderlineFactory Lengow Orderline factory
     */
    protected $_orderlineFactory;

    /**
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = [
        'order_id' => ['required' => true, 'updated' => false],
        'product_id' => ['required' => true, 'updated' => false],
        'order_line_id' => ['required' => true, 'updated' => false]
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context Magento context instance
     * @param \Magento\Framework\Registry $registry Magento registry instance
     * @param \Lengow\Connector\Model\Import\OrderlineFactory $orderlineFactory Lengow Orderline factory
     * @param \Lengow\Connector\Model\ResourceModel\Orderline\CollectionFactory $orderlineCollectionFactory Lengow Orderline collection factory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        LengowOrderlineFactory $orderlineFactory,
        OrderlineCollectionFactory $orderlineCollectionFactory
    )
    {
        parent::__construct($context, $registry);
        $this->_orderlineFactory = $orderlineFactory;
        $this->_orderlineCollectionFactory = $orderlineCollectionFactory;
    }

    /**
     * Initialize orderline model
     **
     * @return void
     */
    protected function _construct()
    {
        $this->_init(OrderlineResource::class);
    }

    /**
     * Create Lengow order line
     *
     * @param array $params orderline parameters
     *
     * @return Orderline|false
     */
    public function createOrderLine($params = [])
    {
        foreach ($this->_fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value['required']) {
                return false;
            }
        }
        $orderline = $this->_orderlineFactory->create();
        foreach ($params as $key => $value) {
            $orderline->setData($key, $value);
        }
        return $orderline->save();
    }

    /**
     * Get all order line id by order id
     *
     * @param integer $orderId Magento order id
     *
     * @return array|false
     */
    public function getOrderLineByOrderID($orderId)
    {
        $results = $this->_orderlineCollectionFactory->create()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToSelect('order_line_id')
            ->getData();
        if (count($results) > 0) {
            return $results;
        }
        return false;
    }

}
