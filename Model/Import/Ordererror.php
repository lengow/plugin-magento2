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
use Magento\Framework\Stdlib\DateTime\DateTime;
use Lengow\Connector\Model\ResourceModel\Ordererror as OrderResourceerror;
use Lengow\Connector\Model\ResourceModel\Ordererror\CollectionFactory;

/**
 * Model import ordererror
 */
class Ordererror extends AbstractModel
{
    /**
     * @var integer order error import type
     */
    const TYPE_ERROR_IMPORT = 1;

    /**
     * @var integer order error send type
     */
    const TYPE_ERROR_SEND = 2;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime $_dateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var \Lengow\Connector\Model\ResourceModel\Ordererror\CollectionFactory Lengow order error collection factory
     */
    protected $_ordererrorCollection;

    /**
     * @var \Lengow\Connector\Model\Import\OrdererrorFactory Lengow order error factory
     */
    protected $_ordererrorFactory;

    /**
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = [
        'order_lengow_id' => ['required' => true, 'updated' => false],
        'message' => ['required' => true, 'updated' => false],
        'type' => ['required' => true, 'updated' => false],
        'is_finished' => ['required' => false, 'updated' => true],
        'mail' => ['required' => false, 'updated' => true]
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context Magento context instance
     * @param \Magento\Framework\Registry $registry Magento registry instance
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime Magento datetime instance
     * @param \Lengow\Connector\Model\ResourceModel\Ordererror\CollectionFactory $ordererrorCollection
     * @param \Lengow\Connector\Model\Import\OrdererrorFactory $ordererrorFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DateTime $dateTime,
        CollectionFactory $ordererrorCollection,
        OrdererrorFactory $ordererrorFactory
    )
    {
        $this->_dateTime = $dateTime;
        $this->_ordererrorCollection = $ordererrorCollection;
        $this->_ordererrorFactory = $ordererrorFactory;
        parent::__construct($context, $registry);
    }

    /**
     * Initialize ordererror model
     **
     * @return void
     */
    protected function _construct()
    {
        $this->_init(OrderResourceerror::class);
    }

    /**
     * Create Lengow order error
     *
     * @param array $params ordererror parameters
     *
     * @return Ordererror|false
     */
    public function createOrderError($params = [])
    {
        foreach ($this->_fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value['required']) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            if ($key == 'type') {
                $value = $this->getOrderErrorType($value);
            }
            $this->setData($key, $value);
        }
        $this->setData('created_at', $this->_dateTime->gmtDate('Y-m-d H:i:s'));
        return $this->save();
    }

    /**
     * Update Lengow order error
     *
     * @param array $params ordererror parameters
     *
     * @return Ordererror|false
     */
    public function updateOrderError($params = [])
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
     * Return type value
     *
     * @param string $type order error type (import or send)
     *
     * @return integer
     */
    public function getOrderErrorType($type = null)
    {
        switch ($type) {
            case 'import':
                return self::TYPE_ERROR_IMPORT;
                break;
            case 'send':
                return self::TYPE_ERROR_SEND;
                break;
            default:
                return self::TYPE_ERROR_IMPORT;
                break;
        }
    }

    /**
     * Removes all order error for one order lengow
     *
     * @param integer $orderLengowId Lengow order id
     * @param string $type order error type (import or send)
     *
     * @return boolean
     */
    public function finishOrderErrors($orderLengowId, $type = 'import')
    {
        $errorType = $this->getOrderErrorType($type);
        // get all order errors
        $results = $this->_ordererrorCollection->create()->load()
            ->addFieldToFilter('order_lengow_id', $orderLengowId)
            ->addFieldToFilter('is_finished', 0)
            ->addFieldToFilter('type', $errorType)
            ->addFieldToSelect('id')
            ->getData();
        if (count($results) > 0) {
            foreach ($results as $result) {
                $orderError = $this->_ordererrorFactory->create()->load((int)$result['id']);
                $orderError->updateOrderError(['is_finished' => 1]);
                unset($orderError);
            }
            return true;
        }
        return false;
    }

    /**
     * Get error import logs never send by mail
     *
     * @return array|false
     */
    public function getImportErrors()
    {
        $results = $this->_ordererrorCollection->create()->load()
            ->join(
                'lengow_order',
                '`lengow_order`.id=main_table.order_lengow_id',
                ['marketplace_sku' => 'marketplace_sku']
            )
            ->addFieldToFilter('mail', ['eq' => 0])
            ->addFieldToFilter('is_finished', ['eq' => 0])
            ->addFieldToSelect('message')
            ->addFieldToSelect('id')
            ->getData();
        if (count($results) == 0) {
            return false;
        }
        return $results;
    }
}
