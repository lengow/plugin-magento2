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
use Lengow\Connector\Model\ResourceModel\Action as ResourceAction;
use Lengow\Connector\Model\ResourceModel\ActionFactory as ResourceActionFactory;
use Lengow\Connector\Model\ResourceModel\Action\CollectionFactory as ActionCollectionFactory;

/**
 * Model import action
 */
class Action extends AbstractModel
{
    /**
     * @var integer action state for new action
     */
    const STATE_NEW = 0;

    /**
     * @var integer action state for action finished
     */
    const STATE_FINISH = 1;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var \Lengow\Connector\Model\ResourceModel\Action\CollectionFactory Lengow action collection factory
     */
    protected $_actionCollection;

    /**
     * @var \Lengow\Connector\Model\ResourceModel\ActionFactory Lengow action factory
     */
    protected $_actionFactory;

    /**
     * @var array $_fieldList field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    protected $_fieldList = [
        'order_id' => ['required' => true, 'updated' => false],
        'action_id' => ['required' => true, 'updated' => false],
        'order_line_sku' => ['required' => false, 'updated' => false],
        'action_type' => ['required' => true, 'updated' => false],
        'retry' => ['required' => false, 'updated' => true],
        'parameters' => ['required' => true, 'updated' => false],
        'state' => ['required' => false, 'updated' => true]
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context Magento context instance
     * @param \Magento\Framework\Registry $registry Magento registry instance
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime Magento datetime instance
     * @param \Lengow\Connector\Model\ResourceModel\Action\CollectionFactory $actionCollection
     * @param \Lengow\Connector\Model\ResourceModel\ActionFactory $actionFactory Lengow action factory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DateTime $dateTime,
        ActionCollectionFactory $actionCollection,
        ActionFactory $actionFactory
    )
    {
        parent::__construct($context, $registry);
        $this->_dateTime = $dateTime;
        $this->_actionCollection = $actionCollection;
        $this->_actionFactory = $actionFactory;
    }

    /**
     * Initialize action model
     **
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceAction::class);
    }

    /**
     * Create Lengow action
     *
     * @param array $params action parameters
     *
     * @return \Lengow\Connector\Model\Import\Action|false
     */
    public function createAction($params = [])
    {
        foreach ($this->_fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value['required']) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        $this->setData('state', self::STATE_NEW);
        $this->setData('created_at', $this->_dateTime->gmtDate('Y-m-d H:i:s'));
        return $this->save();
    }

    /**
     * Update Lengow action
     *
     * @param array $params action parameters
     *
     * @return \Lengow\Connector\Model\Import\Action|false
     */
    public function updateAction($params = [])
    {
        if (!$this->getId()) {
            return false;
        }
        if ((int)$this->getData('state') != self::STATE_NEW) {
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
     * Get active action by API action ID
     *
     * @param integer $actionId action id from API
     *
     * @return integer|false
     */
    public function getActiveActionByActionId($actionId)
    {
        $results = $this->_actionCollection->create()
            ->addFieldToFilter('action_id', $actionId)
            ->addFieldToFilter('state', self::STATE_NEW)
            ->getData();
        if (count($results) > 0) {
            return (int)$results[0]['id'];
        }
        return false;
    }

    /**
     * Removes all actions for one order Magento
     *
     * @param integer $orderId Magento order id
     * @param string $actionType action type (null, ship or cancel)
     *
     * @return boolean
     */
    public function finishAllActions($orderId, $actionType = null)
    {
        // get all order action
        $collection = $this->_actionCollection->create()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('state', self::STATE_NEW);
        if (!is_null($actionType)) {
            $collection->addFieldToFilter('action_type', $actionType);
        }
        $results = $collection->addFieldToSelect('id')->getData();
        if (count($results) > 0) {
            foreach ($results as $result) {
                $action = $this->_actionFactory->create()->load($result['id']);
                $action->updateAction(['state' => self::STATE_FINISH]);
                unset($action);
            }
            return true;
        }
        return false;
    }
}
