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

use Lengow\Connector\Model\Exception as LengowException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\OrderFactory as MagentoOrderFactory;
use Magento\Framework\Json\Helper\Data as JsonHelper;
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
     * @var string action type ship
     */
    const TYPE_SHIP = 'ship';

    /**
     * @var string action type cancel
     */
    const TYPE_CANCEL = 'cancel';

    /**
     * @var string action argument action type
     */
    const ARG_ACTION_TYPE = 'action_type';

    /**
     * @var string action argument line
     */
    const ARG_LINE = 'line';

    /**
     * @var string action argument carrier
     */
    const ARG_CARRIER = 'carrier';

    /**
     * @var string action argument carrier name
     */
    const ARG_CARRIER_NAME = 'carrier_name';

    /**
     * @var string action argument custom carrier
     */
    const ARG_CUSTOM_CARRIER = 'custom_carrier';

    /**
     * @var string action argument shipping method
     */
    const ARG_SHIPPING_METHOD = 'shipping_method';

    /**
     * @var string action argument tracking number
     */
    const ARG_TRACKING_NUMBER = 'tracking_number';

    /**
     * @var string action argument shipping price
     */
    const ARG_SHIPPING_PRICE = 'shipping_price';

    /**
     * @var string action argument shipping date
     */
    const ARG_SHIPPING_DATE = 'shipping_date';

    /**
     * @var string action argument delivery date
     */
    const ARG_DELIVERY_DATE = 'delivery_date';

    /**
     * @var integer max interval time for action synchronisation (3 days)
     */
    const MAX_INTERVAL_TIME = 259200;

    /**
     * @var integer security interval time for action synchronisation (2 hours)
     */
    const SECURITY_INTERVAL_TIME = 7200;

    /**
     * @var array Parameters to delete for Get call
     */
    public static $getParamsToDelete = [
        self::ARG_SHIPPING_DATE,
        self::ARG_DELIVERY_DATE,
    ];

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface Magento datetime timezone instance
     */
    protected $_timezone;

    /**
     * @var \Magento\Sales\Model\OrderFactory Magento order factory instance
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data Magento json helper instance
     */
    protected $_jsonHelper;

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
        'state' => ['required' => false, 'updated' => true],
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context Magento context instance
     * @param \Magento\Framework\Registry $registry Magento registry instance
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime Magento datetime instance
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone Magento datetime timezone instance
     * @param \Magento\Sales\Model\OrderFactory $orderFactory Magento order factory instance
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper Magento json helper instance
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
        TimezoneInterface $timezone,
        MagentoOrderFactory $orderFactory,
        JsonHelper $jsonHelper,
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
        $this->_timezone = $timezone;
        $this->_orderFactory = $orderFactory;
        $this->_jsonHelper = $jsonHelper;
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
        try {
            return $this->save();
        } catch (\Exception $e) {
            return false;
        }
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
        if ((int)$this->getData('state') !== self::STATE_NEW) {
            return false;
        }
        $updatedFields = $this->getUpdatedFields();
        foreach ($params as $key => $value) {
            if (in_array($key, $updatedFields)) {
                $this->setData($key, $value);
            }
        }
        $this->setData('updated_at', $this->_dateTime->gmtDate('Y-m-d H:i:s'));
        try {
            return $this->save();
        } catch (\Exception $e) {
            return false;
        }
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
     * Get action by API action ID
     *
     * @param integer $actionId action id from API
     *
     * @return integer|false
     */
    public function getActionByActionId($actionId)
    {
        $results = $this->_actionCollection->create()
            ->addFieldToFilter('action_id', $actionId)
            ->getData();
        if (!empty($results)) {
            return (int)$results[0]['id'];
        }
        return false;
    }

    /**
     * Find active actions by order id
     *
     * @param integer $orderId Magento order id
     * @param string|null $actionType action type (ship or cancel)
     *
     * @return array|false
     */
    public function getActiveActionByOrderId($orderId, $actionType = null)
    {
        $collection = $this->_actionCollection->create()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('state', self::STATE_NEW);
        if ($actionType !== null) {
            $collection->addFieldToFilter('action_type', $actionType);
        }
        $results = $collection->getData();
        if (!empty($results)) {
            return $results;
        }
        return false;
    }


    /**
     * Get last order action type to re-send action
     *
     * @param integer $orderId Magento order id
     *
     * @return string|false
     */
    public function getLastOrderActionType($orderId)
    {
        $results = $this->_actionCollection->create()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('state', self::STATE_NEW)
            ->addFieldToSelect('action_type');
        if (!empty($results)) {
            $lastAction = $results->getLastItem()->getData();
            return (string)$lastAction['action_type'];
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
        if (!empty($results)) {
            return $results;
        }
        return false;
    }

    /**
     * Indicates whether an action can be created if it does not already exist
     *
     * @param array $params all available values
     * @param \Magento\Sales\Model\Order $order Magento order instance
     *
     * @throws LengowException
     *
     * @return boolean
     */
    public function canSendAction($params, $order)
    {
        $sendAction = true;
        // check if action is already created
        $getParams = array_merge($params, ['queued' => 'True']);
        // array key deletion for GET verification
        foreach (self::$getParamsToDelete as $param) {
            if (isset($getParams[$param])) {
                unset($getParams[$param]);
            }
        }
        $result = $this->_connector->queryApi(Connector::GET, Connector::API_ORDER_ACTION, $getParams);
        if (isset($result->error) && isset($result->error->message)) {
            throw new LengowException($result->error->message);
        }
        if (isset($result->count) && $result->count > 0) {
            foreach ($result->results as $row) {
                $actionId = $this->getActionByActionId($row->id);
                if ($actionId) {
                    $action = $this->_actionFactory->create()->load($actionId);
                    if ((int)$action->getData('state') === 0) {
                        $retry = (int)$action->getData('retry') + 1;
                        $action->updateAction(['retry' => $retry]);
                        $sendAction = false;
                    }
                } else {
                    // if update doesn't work, create new action
                    $action = $this->_actionFactory->create();
                    $action->createAction(
                        [
                            'order_id' => $order->getId(),
                            'action_type' => $params[self::ARG_ACTION_TYPE],
                            'action_id' => $row->id,
                            'order_line_sku' => isset($params['line']) ? $params['line'] : null,
                            'parameters' => $this->_jsonHelper->jsonEncode($params),
                        ]
                    );
                    $sendAction = false;
                }
                unset($orderAction);
            }
        }
        return $sendAction;
    }

    /**
     * Send a new action on the order via the Lengow API
     *
     * @param array $params all available values
     * @param \Magento\Sales\Model\Order $order Magento order instance
     * @param \Lengow\Connector\Model\Import\Order $lengowOrder Lengow order instance
     *
     * @throws LengowException
     */
    public function sendAction($params, $order, $lengowOrder)
    {
        if (!(bool)$this->_configHelper->get('preprod_mode_enable')) {
            $result = $this->_connector->queryApi(Connector::POST, Connector::API_ORDER_ACTION, $params);
            if (isset($result->id)) {
                $action = $this->_actionFactory->create();
                $action->createAction(
                    [
                        'order_id' => $order->getId(),
                        'action_type' => $params[self::ARG_ACTION_TYPE],
                        'action_id' => $result->id,
                        'order_line_sku' => isset($params['line']) ? $params['line'] : null,
                        'parameters' => $this->_jsonHelper->jsonEncode($params),
                    ]
                );
                unset($orderAction);
            } else {
                if ($result !== null) {
                    $message = $this->_dataHelper->setLogMessage(
                        "can't create action: %1",
                        [$this->_jsonHelper->jsonEncode($result)]
                    );
                } else {
                    // generating a generic error message when the Lengow API is unavailable
                    $message = $this->_dataHelper->setLogMessage(
                        "can't create action because Lengow API is unavailable. Please retry"
                    );
                }
                throw new LengowException($message);
            }
        }
        // create log for call action
        $paramList = false;
        foreach ($params as $param => $value) {
            $paramList .= !$paramList ? '"' . $param . '": ' . $value : ' -- "' . $param . '": ' . $value;
        }
        $this->_dataHelper->log(
            DataHelper::CODE_ACTION,
            $this->_dataHelper->setLogMessage('call tracking with parameters: %1', [$paramList]),
            false,
            $lengowOrder->getData('marketplace_sku')
        );
    }

    /**
     * Removes all actions for one order Magento
     *
     * @param integer $orderId Magento order id
     * @param string|null $actionType action type (ship or cancel)
     *
     * @return boolean
     */
    public function finishAllActions($orderId, $actionType = null)
    {
        // get all order action
        $collection = $this->_actionCollection->create()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('state', self::STATE_NEW);
        if ($actionType !== null) {
            $collection->addFieldToFilter('action_type', $actionType);
        }
        $results = $collection->addFieldToSelect('id')->getData();
        if (!empty($results)) {
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
     * Check if active actions are finished
     *
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function checkFinishAction($logOutput = false)
    {
        if ((bool)$this->_configHelper->get('preprod_mode_enable')) {
            return false;
        }
        $this->_dataHelper->log(
            DataHelper::CODE_ACTION,
            $this->_dataHelper->setLogMessage('check completed actions'),
            $logOutput
        );
        // get all active actions
        $activeActions = $this->getActiveActions();
        // if no active action, do nothing
        if (!$activeActions) {
            return true;
        }
        // get all actions with API (max 3 days)
        $page = 1;
        $apiActions = [];
        $intervalTime = $this->_getIntervalTime();
        $dateFrom = $this->_timezone->date(time() - $intervalTime);
        $dateTo = $this->_timezone->date();
        $this->_dataHelper->log(
            DataHelper::CODE_ACTION,
            $this->_dataHelper->setLogMessage(
                'get order actions between %1 and %2',
                [
                    $dateFrom->format('Y-m-d H:i:s'),
                    $dateTo->format('Y-m-d H:i:s'),
                ]
            ),
            $logOutput
        );
        do {
            $results = $this->_connector->queryApi(
                Connector::GET,
                Connector::API_ORDER_ACTION,
                [
                    'updated_from' => $dateFrom->format('c'),
                    'updated_to' => $dateTo->format('c'),
                    'page' => $page,
                ],
                '',
                $logOutput
            );
            if (!is_object($results) || isset($results->error)) {
                break;
            }
            // construct array actions
            foreach ($results->results as $action) {
                if (isset($action->id)) {
                    $apiActions[$action->id] = $action;
                }
            }
            $page++;
        } while ($results->next != null);
        if (empty($apiActions)) {
            return false;
        }
        // check foreach action if is complete
        foreach ($activeActions as $action) {
            if (!isset($apiActions[$action['action_id']])) {
                continue;
            }
            if (isset($apiActions[$action['action_id']]->queued)
                && isset($apiActions[$action['action_id']]->processed)
                && isset($apiActions[$action['action_id']]->errors)
            ) {
                if ($apiActions[$action['action_id']]->queued == false) {
                    // order action is waiting to return from the marketplace
                    if ($apiActions[$action['action_id']]->processed == false
                        && empty($apiActions[$action['action_id']]->errors)
                    ) {
                        continue;
                    }
                    // finish action in lengow_action table
                    $lengowAction = $this->_actionFactory->create()->load($action['id']);
                    $lengowAction->updateAction(['state' => self::STATE_FINISH]);
                    $lengowOrderId = $this->_lengowOrderFactory->create()
                        ->getLengowOrderIdByOrderId($action['order_id']);
                    if ($lengowOrderId) {
                        $lengowOrder = $this->_lengowOrderFactory->create()->load($lengowOrderId);
                        $this->_orderErrorFactory->create()->finishOrderErrors($lengowOrder->getId(), 'send');
                        if ((bool)$lengowOrder->getData('is_in_error')) {
                            $lengowOrder->updateOrder(['is_in_error' => 0]);
                        }
                        $processStateFinish = $lengowOrder->getOrderProcessState('closed');
                        if ((int)$lengowOrder->getData('order_process_state') !== $processStateFinish) {
                            // if action is accepted -> close order and finish all order actions
                            if ($apiActions[$action['action_id']]->processed == true
                                && empty($apiActions[$action['action_id']]->errors)
                            ) {
                                $lengowOrder->updateOrder(['order_process_state' => $processStateFinish]);
                                $this->finishAllActions($action['order_id']);
                            } else {
                                // if action is denied -> create order error
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
                                    DataHelper::CODE_ACTION,
                                    $this->_dataHelper->setLogMessage(
                                        'order action failed - %1',
                                        [$apiActions[$action['action_id']]->errors]
                                    ),
                                    $logOutput,
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
        $this->_configHelper->set('last_action_sync', time());
        return true;
    }

    /**
     * Remove old actions > 3 days
     *
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function checkOldAction($logOutput = false)
    {
        if ((bool)$this->_configHelper->get('preprod_mode_enable')) {
            return false;
        }
        $this->_dataHelper->log(
            DataHelper::CODE_ACTION,
            $this->_dataHelper->setLogMessage('check and finish old actions'),
            $logOutput
        );
        // get all old order action (+ 3 days)
        $actions = $this->getOldActions();
        if ($actions) {
            foreach ($actions as $action) {
                $action = $this->_actionFactory->create()->load($action['id']);
                $action->updateAction(['state' => self::STATE_FINISH]);
                $lengowOrderId = $this->_lengowOrderFactory->create()->getLengowOrderIdByOrderId($action['order_id']);
                if ($lengowOrderId) {
                    $lengowOrder = $this->_lengowOrderFactory->create()->load($lengowOrderId);
                    $processStateFinish = $lengowOrder->getOrderProcessState('closed');
                    if ((int)$lengowOrder->getData('order_process_state') != $processStateFinish
                        && (bool)$lengowOrder->getData('is_in_error') === false
                    ) {
                        // if action is denied -> create order error
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
                        $decodedMessage = $this->_dataHelper->decodeLogMessage($errorMessage, false);
                        $this->_dataHelper->log(
                            DataHelper::CODE_ACTION,
                            $this->_dataHelper->setLogMessage('order action failed - %1', [$decodedMessage]),
                            $logOutput,
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
     * Get old untreated actions of more than 3 days
     *
     * @return array|false
     */
    public function getOldActions()
    {
        $collection = $this->_actionCollection->create()
            ->addFieldToFilter('state', self::STATE_NEW)
            ->addFieldToFilter(
                'created_at',
                [
                    'to' => time() - self::MAX_INTERVAL_TIME,
                    'datetime' => true,
                ]
            );
        $results = $collection->getData();
        return !empty($results) ? $results : false;
    }

    /**
     * Check if actions are not sent
     *
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function checkActionNotSent($logOutput = false)
    {
        if ((bool)$this->_configHelper->get('preprod_mode_enable')) {
            return false;
        }
        $this->_dataHelper->log(
            DataHelper::CODE_ACTION,
            $this->_dataHelper->setLogMessage('check actions not sent'),
            $logOutput
        );
        // get unsent orders
        $lengowOrder = $this->_lengowOrderFactory->create();
        $unsentOrders = $lengowOrder->getUnsentOrders();
        if ($unsentOrders) {
            foreach ($unsentOrders as $unsentOrder) {
                if (!$this->getActiveActionByOrderId((int)$unsentOrder['order_id'])) {
                    $action = $unsentOrder['state'] === self::TYPE_CANCEL ? self::TYPE_CANCEL : self::TYPE_SHIP;
                    $order = $this->_orderFactory->create()->load((int)$unsentOrder['order_id']);
                    $shipment = $action === self::TYPE_SHIP ? $order->getShipmentsCollection()->getFirstItem() : null;
                    $lengowOrder->callAction($action, $order, $shipment);
                }
            }
        }
        return true;
    }

    /**
     * Get interval time for action synchronisation
     *
     * @return integer
     */
    protected function _getIntervalTime()
    {
        $intervalTime = self::MAX_INTERVAL_TIME;
        $lastActionSynchronisation = $this->_configHelper->get('last_action_sync');
        if ($lastActionSynchronisation) {
            $lastIntervalTime = time() - (int)$lastActionSynchronisation;
            $lastIntervalTime = $lastIntervalTime + self::SECURITY_INTERVAL_TIME;
            $intervalTime = $lastIntervalTime > $intervalTime ? $intervalTime : $lastIntervalTime;
        }
        return $intervalTime;
    }
}
