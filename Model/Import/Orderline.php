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
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\ResourceModel\Orderline\CollectionFactory as LengowOrderLineCollectionFactory;
use Lengow\Connector\Model\ResourceModel\Orderline as LengowOrderLineResource;

/**
 * Model import orderline
 */
class Orderline extends AbstractModel
{
    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var LengowOrderLineCollectionFactory Lengow orderline collection factory instance
     */
    protected $_orderLineCollectionFactory;

    /**
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = [
        'order_id' => ['required' => true, 'updated' => false],
        'product_id' => ['required' => true, 'updated' => false],
        'order_line_id' => ['required' => true, 'updated' => false],
    ];

    /**
     * Constructor
     *
     * @param Context $context Magento context instance
     * @param Registry $registry Magento registry instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param LengowOrderLineCollectionFactory $orderLineCollectionFactory Lengow orderline collection factory instance
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DataHelper $dataHelper,
        LengowOrderLineCollectionFactory $orderLineCollectionFactory
    )
    {
        parent::__construct($context, $registry);
        $this->_dataHelper = $dataHelper;
        $this->_orderLineCollectionFactory = $orderLineCollectionFactory;
    }

    /**
     * Initialize orderline model
     **
     * @return void
     */
    protected function _construct()
    {
        $this->_init(LengowOrderLineResource::class);
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
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        try {
            return $this->save();
        } catch (\Exception $e) {
            $errorMessage = 'Orm error: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
            $this->_dataHelper->log(
                DataHelper::CODE_ORM,
                $this->_dataHelper->setLogMessage('Error while inserting record in database - %1', [$errorMessage])
            );
            return false;
        }
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
        $results = $this->_orderLineCollectionFactory->create()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToSelect('order_line_id')
            ->getData();
        if (!empty($results)) {
            return $results;
        }
        return false;
    }
}
