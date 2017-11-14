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
use Magento\Sales\Model\OrderFactory as MagentoOrderFactory;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Connector;
use Lengow\Connector\Model\ResourceModel\Action as ResourceAction;
use Lengow\Connector\Model\ResourceModel\Action\CollectionFactory as ActionCollectionFactory;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;

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
     * @var \Magento\Sales\Model\OrderFactory Magento order factory instance
     */
    protected $_orderFactory;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Model\Connector Lengow connector instance
     */
    protected $_connector;

    /**
     * @var \Lengow\Connector\Model\Import\OrderFactory Lengow order factory instance
     */
    protected $_lengowOrderFactory;

    /**
     * @var \Lengow\Connector\Model\Import\OrdererrorFactory Lengow order error factory instance
     */
    protected $_orderErrorFactory;

    /**
     * @var \Lengow\Connector\Model\ResourceModel\Action\CollectionFactory Lengow action collection factory
     */
    protected $_actionCollection;

    /**
     * @var \Lengow\Connector\Model\Import\ActionFactory Lengow action factory instance
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
     * @param \Magento\Sales\Model\OrderFactory $orderFactory Magento order factory instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Model\Connector $connector Lengow connector instance
     * @param \Lengow\Connector\Model\Import\OrderFactory $lengowOrderFactory Lengow order factory instance
     * @param \Lengow\Connector\Model\Import\OrdererrorFactory $orderErrorFactory Lengow order error factory instance
     * @param \Lengow\Connector\Model\ResourceModel\Action\CollectionFactory $actionCollection
     * @param \Lengow\Connector\Model\Import\ActionFactory $actionFactory Lengow action factory instance
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DateTime $dateTime,
        MagentoOrderFactory $orderFactory,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        Connector $connector,
        LengowOrderFactory $lengowOrderFactory,
        OrdererrorFactory $orderErrorFactory,
        ActionCollectionFactory $actionCollection,
        ActionFactory $actionFactory
    )
    {
        $this->_dateTime = $dateTime;
        $this->_orderFactory = $orderFactory;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_connector = $connector;
        $this->_lengowOrderFactory = $lengowOrderFactory;
        $this->_orderErrorFactory = $orderErrorFactory;
        $this->_actionCollection = $actionCollection;
        $this->_actionFactory = $actionFactory;
        parent::__construct($context, $registry);
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
     * Find active actions by order id
     *
     * @param integer $orderId Magento order id
     * @param string $actionType action type (ship or cancel)
     *
     * @return array|false
     */
    public function getActiveActionByOrderId($orderId, $actionType = null)
    {
        $collection = $this->_actionCollection->create()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('state', self::STATE_NEW);
        if (!is_null($actionType)) {
            $collection->addFieldToFilter('action_type', $actionType);
        }
        $results = $collection->getData();
        if (count($results) > 0) {
            return $results;
        }
        return false;
    }

    /**
     * Get all active actions
     *
     * @return array|false
     */
    public function getActiveActions()
    {
        $results = $this->_actionCollection->create()
            ->addFieldToFilter('state', self::STATE_NEW)
            ->getData();
        if (count($results) > 0) {
            return $results;
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

    /**
     * Remove old actions > 3 days
     *
     * @param string $actionType action type (null, ship or cancel)
     *
     * @return boolean
     */
    public function finishAllOldActions($actionType = null)
    {
        // get all old order action (+ 3 days)
        $collection = $this->_actionCollection->create()
            ->addFieldToFilter('state', self::STATE_NEW)
            ->addFieldToFilter(
                'created_at',
                [
                    'to' => strtotime('-3 days', time()),
                    'datetime' => true
                ]
            );
        if (!is_null($actionType)) {
            $collection->addFieldToFilter('action_type', $actionType);
        }
        $results = $collection->getData();
        if (count($results) > 0) {
            foreach ($results as $result) {
                $action = $this->_actionFactory->create()->load($result['id']);
                $action->updateAction(['state' => self::STATE_FINISH]);
                $lengowOrderId = $this->_lengowOrderFactory->create()->getLengowOrderIdByOrderId($result['order_id']);
                if ($lengowOrderId) {
                    $lengowOrder = $this->_lengowOrderFactory->create()->load($lengowOrderId);
                    $processStateFinish = $lengowOrder->getOrderProcessState('closed');
                    if ((int)$lengowOrder->getData('order_process_state') != $processStateFinish
                        && $lengowOrder->getData('is_in_error') == 0
                    ) {
                        // If action is denied -> create order error
                        $errorMessage = $this->_dataHelper->setLogMessage('order action is too old. Please retry');
                        $orderError = $this->_orderErrorFactory->create();
                        $orderError->createOrderError(
                            [
                                'order_lengow_id' => $lengowOrder->getId(),
                                'message' => $errorMessage,
                                'type' => 'send',
                            ]
                        );
                        $lengowOrder->updateOrder(['is_in_error' => 1]);
                        $decodedMessage = $this->_dataHelper->decodeLogMessage($errorMessage, 'en_GB');
                        $this->_dataHelper->log(
                            'API-OrderAction',
                            $this->_dataHelper->setLogMessage('order action failed - %1', [$decodedMessage]),
                            false,
                            $lengowOrder->getData('marketplace_sku')
                        );
                        unset($orderError);
                    }
                    unset($lengowOrder);
                }
                unset($action);
            }
            return true;
        }
        return false;
    }

    /**
     * Check if active actions are finished
     *
     * @return boolean
     */
    public function checkFinishAction()
    {
        if ((bool)$this->_configHelper->get('preprod_mode_enable')) {
            return false;
        }
        $this->_dataHelper->log('API-OrderAction', $this->_dataHelper->setLogMessage('check completed actions'));
        // Get all active actions
        $activeActions = $this->getActiveActions();
        // If no active action, do nothing
        if (!$activeActions) {
            return true;
        }
        // Get all actions with API for 3 days
        $page = 1;
        $apiActions = [];
        do {
            $results = $this->_connector->queryApi(
                'get',
                '/v3.0/orders/actions/',
                [
                    'updated_from' => date('c', strtotime(date('Y-m-d') . ' -3days')),
                    'updated_to' => date('c'),
                    'page' => $page
                ]
            );
            if (!is_object($results) || isset($results->error)) {
                break;
            }
            // Construct array actions
            foreach ($results->results as $action) {
                if (isset($action->id)) {
                    $apiActions[$action->id] = $action;
                }
            }
            $page++;
        } while ($results->next != null);
        if (count($apiActions) == 0) {
            return false;
        }
        // Check foreach action if is complete
        foreach ($activeActions as $action) {
            if (!isset($apiActions[$action['action_id']])) {
                continue;
            }
            if (isset($apiActions[$action['action_id']]->queued)
                && isset($apiActions[$action['action_id']]->processed)
                && isset($apiActions[$action['action_id']]->errors)
            ) {
                if ($apiActions[$action['action_id']]->queued == false) {
                    // Finish action in lengow_action table
                    $lengowAction = $this->_actionFactory->create()->load($action['id']);
                    $lengowAction->updateAction(['state' => self::STATE_FINISH]);
                    $lengowOrderId = $this->_lengowOrderFactory->create()
                        ->getLengowOrderIdByOrderId($action['order_id']);
                    if ($lengowOrderId) {
                        $lengowOrder = $this->_lengowOrderFactory->create()->load($lengowOrderId);
                        $this->_orderErrorFactory->create()->finishOrderErrors($lengowOrder->getId(), 'send');
                        if ($lengowOrder->getData('is_in_error') == 1) {
                            $lengowOrder->updateOrder(['is_in_error' => 0]);
                        }
                        $processStateFinish = $lengowOrder->getOrderProcessState('closed');
                        if ((int)$lengowOrder->getData('order_process_state') != $processStateFinish) {
                            // If action is accepted -> close order and finish all order actions
                            if ($apiActions[$action['action_id']]->processed == true) {
                                $lengowOrder->updateOrder(['order_process_state' => $processStateFinish]);
                                $this->finishAllActions($action['order_id']);
                            } else {
                                // If action is denied -> create order error
                                $orderError = $this->_orderErrorFactory->create();
                                $orderError->createOrderError(
                                    [
                                        'order_lengow_id' => $lengowOrder->getId(),
                                        'message' => $apiActions[$action['action_id']]->errors,
                                        'type' => 'send',
                                    ]
                                );
                                $lengowOrder->updateOrder(['is_in_error' => 1]);
                                $this->_dataHelper->log(
                                    'API-OrderAction',
                                    $this->_dataHelper->setLogMessage(
                                        'order action failed - %1',
                                        [$apiActions[$action['action_id']]->errors]
                                    ),
                                    false,
                                    $lengowOrder->getData('marketplace_sku')
                                );
                                unset($orderError);
                            }
                        }
                        unset($lengowOrder);
                    }
                    unset($lengowAction);
                }
            }
        }
        // Clean actions after 3 days
        $this->finishAllOldActions();
        return true;
    }

    /**
     * Check if actions are not sent
     *
     * @return boolean
     */
    public function checkActionNotSent()
    {
        if ((bool)$this->_configHelper->get('preprod_mode_enable')) {
            return false;
        }
        $this->_dataHelper->log('API-OrderAction', $this->_dataHelper->setLogMessage('check actions not sent'));
        // Get unsent orders
        $lengowOrder = $this->_lengowOrderFactory->create();
        $unsentOrders = $lengowOrder->getUnsentOrders();
        if ($unsentOrders) {
            foreach ($unsentOrders as $unsentOrder) {
                if (!$this->getActiveActionByOrderId((int)$unsentOrder['order_id'])) {
                    $action = $unsentOrder['state'] == 'cancel' ? 'cancel' : 'ship';
                    $order = $this->_orderFactory->create()->load((int)$unsentOrder['order_id']);
                    $shipment = $action === 'ship' ? $order->getShipmentsCollection()->getFirstItem() : null;
                    $lengowOrder->callAction($action, $order, $shipment);
                }
            }
        }
        return true;
    }
}
