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

use Exception;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Sales\Model\OrderFactory as MagentoOrderFactory;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Connector as LengowConnector;
use Lengow\Connector\Model\Exception as LengowException;
use Lengow\Connector\Model\Import as LengowImport;
use Lengow\Connector\Model\Import\Action as LengowAction;
use Lengow\Connector\Model\Import\ActionFactory as LengowActionFactory;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;
use Lengow\Connector\Model\Import\Ordererror as LengowOrderError;
use Lengow\Connector\Model\Import\OrdererrorFactory as LengowOrderErrorFactory;
use Lengow\Connector\Model\ResourceModel\Action as LengowActionResource;
use Lengow\Connector\Model\ResourceModel\Action\CollectionFactory as LengowActionCollectionFactory;

/**
 * Model import action
 */
class Action extends AbstractModel
{
    /**
     * @var string Lengow action table name
     */
    public const TABLE_ACTION = 'lengow_action';

    /* Action fields */
    public const FIELD_ID = 'id';
    public const FIELD_ORDER_ID = 'order_id';
    public const FIELD_ACTION_ID = 'action_id';
    public const FIELD_ORDER_LINE_SKU = 'order_line_sku';
    public const FIELD_ACTION_TYPE = 'action_type';
    public const FIELD_RETRY = 'retry';
    public const FIELD_PARAMETERS = 'parameters';
    public const FIELD_STATE = 'state';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';

    /* Action states */
    public const STATE_NEW = 0;
    public const STATE_FINISH = 1;

    /* Action types */
    public const TYPE_SHIP = 'ship';
    public const TYPE_CANCEL = 'cancel';

    /* Action API arguments */
    public const ARG_ACTION_TYPE = 'action_type';
    public const ARG_LINE = 'line';
    public const ARG_CARRIER = 'carrier';
    public const ARG_CARRIER_NAME = 'carrier_name';
    public const ARG_CUSTOM_CARRIER = 'custom_carrier';
    public const ARG_SHIPPING_METHOD = 'shipping_method';
    public const ARG_TRACKING_NUMBER = 'tracking_number';
    public const ARG_RETURN_TRACKING_NUMBER = 'return_tracking_number';
    public const ARG_RETURN_CARRIER = 'return_carrier';
    public const ARG_SHIPPING_PRICE = 'shipping_price';
    public const ARG_SHIPPING_DATE = 'shipping_date';
    public const ARG_DELIVERY_DATE = 'delivery_date';

    /**
     * @var integer max interval time for action synchronisation (3 days)
     */
    private const MAX_INTERVAL_TIME = 259200;

    /**
     * @var integer security interval time for action synchronisation (2 hours)
     */
    private const SECURITY_INTERVAL_TIME = 7200;

    /**
     * @var array Parameters to delete for Get call
     */
    public static $getParamsToDelete = [
        self::ARG_SHIPPING_DATE,
        self::ARG_DELIVERY_DATE,
    ];

    /**
     * @var DateTime Magento datetime instance
     */
    private $dateTime;

    /**
     * @var TimezoneInterface Magento datetime timezone instance
     */
    private $timezone;

    /**
     * @var MagentoOrderFactory Magento order factory instance
     */
    private $orderFactory;

    /**
     * @var JsonHelper Magento json helper instance
     */
    private $jsonHelper;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var LengowConnector Lengow connector instance
     */
    private $lengowConnector;

    /**
     * @var LengowActionFactory Lengow action factory instance
     */
    private $lengowActionFactory;

    /**
     * @var LengowOrderFactory Lengow order factory instance
     */
    private $lengowOrderFactory;

    /**
     * @var LengowOrderErrorFactory Lengow order error factory instance
     */
    private $lengowOrderErrorFactory;

    /**
     * @var LengowActionCollectionFactory Lengow action collection factory
     */
    private $lengowActionCollection;

    /**
     * @var array field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    private $fieldList = [
        self::FIELD_ORDER_ID => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_ACTION_ID => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_ORDER_LINE_SKU => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_ACTION_TYPE => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_RETRY => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_PARAMETERS => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_STATE => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
    ];

    /**
     * Constructor
     *
     * @param Context $context Magento context instance
     * @param Registry $registry Magento registry instance
     * @param DateTime $dateTime Magento datetime instance
     * @param TimezoneInterface $timezone Magento datetime timezone instance
     * @param MagentoOrderFactory $orderFactory Magento order factory instance
     * @param JsonHelper $jsonHelper Magento json helper instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param LengowConnector $lengowConnector Lengow connector instance
     * @param LengowOrderFactory $lengowOrderFactory Lengow order factory instance
     * @param LengowOrderErrorFactory $lengowOrderErrorFactory Lengow order error factory instance
     * @param LengowActionCollectionFactory $lengowActionCollection Lengow action collection factory
     * @param LengowActionFactory $lengowActionFactory Lengow action factory instance
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
        LengowConnector $lengowConnector,
        LengowOrderFactory $lengowOrderFactory,
        LengowOrderErrorFactory $lengowOrderErrorFactory,
        LengowActionCollectionFactory $lengowActionCollection,
        LengowActionFactory $lengowActionFactory
    ) {
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->orderFactory = $orderFactory;
        $this->jsonHelper = $jsonHelper;
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->lengowConnector = $lengowConnector;
        $this->lengowOrderFactory = $lengowOrderFactory;
        $this->lengowOrderErrorFactory = $lengowOrderErrorFactory;
        $this->lengowActionCollection = $lengowActionCollection;
        $this->lengowActionFactory = $lengowActionFactory;
        parent::__construct($context, $registry);
    }

    /**
     * Initialize action model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(LengowActionResource::class);
    }

    /**
     * Create Lengow action
     *
     * @param array $params action parameters
     *
     * @return LengowAction|false
     */
    public function createAction(array $params = [])
    {
        foreach ($this->fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value[DataHelper::FIELD_REQUIRED]) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        $this->setData(self::FIELD_STATE, self::STATE_NEW);
        $this->setData(self::FIELD_CREATED_AT, $this->dateTime->gmtDate(DataHelper::DATE_FULL));
        try {
            return $this->save();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Update Lengow action
     *
     * @param array $params action parameters
     *
     * @return LengowAction|false
     */
    public function updateAction(array $params = [])
    {
        if (!$this->getId()) {
            return false;
        }
        if ((int) $this->getData(self::FIELD_STATE) !== self::STATE_NEW) {
            return false;
        }
        $updatedFields = $this->getUpdatedFields();
        foreach ($params as $key => $value) {
            if (in_array($key, $updatedFields, true)) {
                $this->setData($key, $value);
            }
        }
        $this->setData(self::FIELD_UPDATED_AT, $this->dateTime->gmtDate(DataHelper::DATE_FULL));
        try {
            return $this->save();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get updated fields
     *
     * @return array
     */
    public function getUpdatedFields(): array
    {
        $updatedFields = [];
        foreach ($this->fieldList as $key => $value) {
            if ($value[DataHelper::FIELD_CAN_BE_UPDATED]) {
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
    public function getActionByActionId(int $actionId)
    {
        $results = $this->lengowActionCollection->create()
            ->addFieldToFilter(self::FIELD_ACTION_ID, $actionId)
            ->getData();
        if (!empty($results)) {
            return (int) $results[0][self::FIELD_ID];
        }
        return false;
    }

    /**
     * Find actions by order id
     *
     * @param integer $orderId Magento order id
     * @param boolean $onlyActive get only active actions
     * @param string|null $actionType action type (ship or cancel)
     *
     * @return array|false
     */
    public function getActionsByOrderId(int $orderId, bool $onlyActive = false, ?string $actionType = null)
    {
        $collection = $this->lengowActionCollection->create()->addFieldToFilter(self::FIELD_ORDER_ID, $orderId);
        if ($onlyActive) {
            $collection->addFieldToFilter(self::FIELD_STATE, self::STATE_NEW);
        }
        if ($actionType) {
            $collection->addFieldToFilter(self::FIELD_ACTION_TYPE, $actionType);
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
    public function getLastOrderActionType(int $orderId)
    {
        $results = $this->lengowActionCollection->create()
            ->addFieldToFilter(self::FIELD_ORDER_ID, $orderId)
            ->addFieldToFilter(self::FIELD_STATE, self::STATE_NEW)
            ->addFieldToSelect(self::FIELD_ACTION_TYPE);
        $lastAction = $results->getLastItem()->getData();
        if (!empty($lastAction)) {
            return (string) $lastAction[self::FIELD_ACTION_TYPE];
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
        $results = $this->lengowActionCollection->create()
            ->addFieldToFilter(self::FIELD_STATE, self::STATE_NEW)
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
     * @param MagentoOrder $order Magento order instance
     *
     * @throws LengowException
     *
     * @return boolean
     */
    public function canSendAction(array $params, MagentoOrder $order): bool
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
        $result = $this->lengowConnector->queryApi(LengowConnector::GET, LengowConnector::API_ORDER_ACTION, $getParams);
        if (isset($result->error, $result->error->message)) {
            throw new LengowException($result->error->message);
        }
        if (isset($result->count) && $result->count > 0) {
            foreach ($result->results as $row) {
                $actionId = $this->getActionByActionId($row->id);
                if ($actionId) {
                    $action = $this->lengowActionFactory->create()->load($actionId);
                    if ((int) $action->getData(self::FIELD_STATE) === 0) {
                        $retry = (int) $action->getData(self::FIELD_RETRY) + 1;
                        $action->updateAction([self::FIELD_RETRY => $retry]);
                        $sendAction = false;
                    }
                } else {
                    // if update doesn't work, create new action
                    $action = $this->lengowActionFactory->create();
                    $action->createAction(
                        [
                            self::FIELD_ORDER_ID => $order->getId(),
                            self::FIELD_ACTION_TYPE => $params[self::ARG_ACTION_TYPE],
                            self::FIELD_ACTION_ID => $row->id,
                            self::FIELD_ORDER_LINE_SKU => $params[self::ARG_LINE] ?? null,
                            self::FIELD_PARAMETERS => $this->jsonHelper->jsonEncode($params),
                        ]
                    );
                    $sendAction = false;
                }
            }
        }
        return $sendAction;
    }

    /**
     * Send a new action on the order via the Lengow API
     *
     * @param array $params all available values
     * @param MagentoOrder $order Magento order instance
     * @param LengowOrder $lengowOrder Lengow order instance
     *
     * @throws LengowException
     */
    public function sendAction(array $params, MagentoOrder $order, Order $lengowOrder): void
    {
        if (!$this->configHelper->debugModeIsActive()) {
            $result = $this->lengowConnector->queryApi(
                LengowConnector::POST,
                LengowConnector::API_ORDER_ACTION,
                $params
            );
            if (isset($result->id)) {
                $action = $this->lengowActionFactory->create();
                $action->createAction(
                    [
                        self::FIELD_ORDER_ID => $order->getId(),
                        self::FIELD_ACTION_TYPE => $params[self::ARG_ACTION_TYPE],
                        self::FIELD_ACTION_ID => $result->id,
                        self::FIELD_ORDER_LINE_SKU => $params[self::ARG_LINE] ?? null,
                        self::FIELD_PARAMETERS => $this->jsonHelper->jsonEncode($params),
                    ]
                );
            } else {
                if ($result) {
                    $message = $this->dataHelper->setLogMessage(
                        "can't create action: %1",
                        [$this->jsonHelper->jsonEncode($result)]
                    );
                } else {
                    // generating a generic error message when the Lengow API is unavailable
                    $message = $this->dataHelper->setLogMessage(
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
        $this->dataHelper->log(
            DataHelper::CODE_ACTION,
            $this->dataHelper->setLogMessage('call tracking with parameters: %1', [$paramList]),
            false,
            $lengowOrder->getData(LengowOrder::FIELD_MARKETPLACE_SKU)
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
    public function finishAllActions(int $orderId, ?string $actionType = null): bool
    {
        // get all order action
        $collection = $this->lengowActionCollection->create()
            ->addFieldToFilter(self::FIELD_ORDER_ID, $orderId)
            ->addFieldToFilter(self::FIELD_STATE, self::STATE_NEW);
        if ($actionType !== null) {
            $collection->addFieldToFilter(self::FIELD_ACTION_TYPE, $actionType);
        }
        $results = $collection->addFieldToSelect(self::FIELD_ID)->getData();
        if (!empty($results)) {
            foreach ($results as $result) {
                $action = $this->lengowActionFactory->create()->load($result[self::FIELD_ID]);
                $action->updateAction([self::FIELD_STATE => self::STATE_FINISH]);
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
    public function checkFinishAction(bool $logOutput = false): bool
    {
        if ($this->configHelper->debugModeIsActive()) {
            return false;
        }
        $this->dataHelper->log(
            DataHelper::CODE_ACTION,
            $this->dataHelper->setLogMessage('check completed actions'),
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
        $intervalTime = $this->getIntervalTime();
        $dateFrom = $this->timezone->date(time() - $intervalTime);
        $dateTo = $this->timezone->date();
        $this->dataHelper->log(
            DataHelper::CODE_ACTION,
            $this->dataHelper->setLogMessage(
                'get order actions between %1 and %2',
                [
                    $dateFrom->format(DataHelper::DATE_FULL),
                    $dateTo->format(DataHelper::DATE_FULL),
                ]
            ),
            $logOutput
        );
        do {
            $results = $this->lengowConnector->queryApi(
                LengowConnector::GET,
                LengowConnector::API_ORDER_ACTION,
                [
                    LengowImport::ARG_UPDATED_FROM => $dateFrom->format(DataHelper::DATE_ISO_8601),
                    LengowImport::ARG_UPDATED_TO => $dateTo->format(DataHelper::DATE_ISO_8601),
                    LengowImport::ARG_PAGE => $page,
                ],
                '',
                $logOutput
            );
            if (!is_object($results) || isset($results->error)) {
                break;
            }
            if (isset($results->results)) {
                 // construct array actions
                foreach ($results->results as $action) {
                    if (isset($action->id)) {
                        $apiActions[$action->id] = $action;
                    }
                }
            }
            $page++;
        } while (!empty($results->next));
        if (empty($apiActions)) {
            return false;
        }
        // check foreach action if is complete
        foreach ($activeActions as $action) {
            if (!isset($apiActions[$action[self::FIELD_ACTION_ID]])) {
                continue;
            }
            $apiAction = $apiActions[$action[self::FIELD_ACTION_ID]];
            if (isset($apiAction->queued, $apiAction->processed, $apiAction->errors) && $apiAction->queued == false) {
                // order action is waiting to return from the marketplace
                if ($apiAction->processed == false && empty($apiAction->errors)) {
                    continue;
                }
                // finish action in lengow_action table
                $lengowAction = $this->lengowActionFactory->create()->load($action[self::FIELD_ID]);
                $lengowAction->updateAction([self::FIELD_STATE => self::STATE_FINISH]);
                $lengowOrderId = $this->lengowOrderFactory->create()
                    ->getLengowOrderIdByOrderId($action[self::FIELD_ORDER_ID]);
                if ($lengowOrderId) {
                    $lengowOrder = $this->lengowOrderFactory->create()->load($lengowOrderId);
                    $this->lengowOrderErrorFactory->create()->finishOrderErrors(
                        $lengowOrder->getId(),
                        LengowOrderError::TYPE_ERROR_SEND
                    );
                    if ((bool) $lengowOrder->getData(LengowOrder::FIELD_IS_IN_ERROR)) {
                        $lengowOrder->updateOrder([LengowOrder::FIELD_IS_IN_ERROR => 0]);
                    }
                    $processStateFinish = $lengowOrder->getOrderProcessState('closed');
                    if ((int) $lengowOrder->getData(LengowOrder::FIELD_ORDER_PROCESS_STATE) !== $processStateFinish) {
                        // if action is accepted -> close order and finish all order actions
                        if ($apiAction->processed == true
                            && empty($apiAction->errors)
                        ) {
                            $lengowOrder->updateOrder(
                                [LengowOrder::FIELD_ORDER_PROCESS_STATE => $processStateFinish]
                            );
                            $this->finishAllActions($action[self::FIELD_ORDER_ID]);
                        } else {
                            // if action is denied -> create order error
                            $orderError = $this->lengowOrderErrorFactory->create();
                            $orderError->createOrderError(
                                [
                                    LengowOrderError::FIELD_ORDER_LENGOW_ID => $lengowOrder->getId(),
                                    LengowOrderError::FIELD_MESSAGE => $apiAction->errors,
                                    LengowOrderError::FIELD_TYPE => LengowOrderError::TYPE_ERROR_SEND,
                                ]
                            );
                            $lengowOrder->updateOrder([LengowOrder::FIELD_IS_IN_ERROR => 1]);
                            $this->dataHelper->log(
                                DataHelper::CODE_ACTION,
                                $this->dataHelper->setLogMessage(
                                    'order action failed - %1',
                                    [$apiAction->errors]
                                ),
                                $logOutput,
                                $lengowOrder->getData(LengowOrder::FIELD_MARKETPLACE_SKU)
                            );
                            unset($orderError);
                        }
                    }
                    unset($lengowOrder);
                }
                unset($lengowAction);
            }
        }
        $this->configHelper->set(ConfigHelper::LAST_UPDATE_ACTION_SYNCHRONIZATION, time());
        return true;
    }

    /**
     * Remove old actions > 3 days
     *
     * @param boolean $logOutput see log or not
     *
     * @return boolean
     */
    public function checkOldAction(bool $logOutput = false): bool
    {
        if ($this->configHelper->debugModeIsActive()) {
            return false;
        }
        $this->dataHelper->log(
            DataHelper::CODE_ACTION,
            $this->dataHelper->setLogMessage('check and finish old actions'),
            $logOutput
        );
        // get all old order action (+ 3 days)
        $actions = $this->getOldActions();
        if ($actions) {
            foreach ($actions as $action) {
                $action = $this->lengowActionFactory->create()->load($action[self::FIELD_ID]);
                $action->updateAction([self::FIELD_STATE => self::STATE_FINISH]);
                $lengowOrderId = $this->lengowOrderFactory->create()
                    ->getLengowOrderIdByOrderId($action[self::FIELD_ORDER_ID]);
                if ($lengowOrderId) {
                    $lengowOrder = $this->lengowOrderFactory->create()->load($lengowOrderId);
                    $processStateFinish = $lengowOrder->getOrderProcessState('closed');
                    if ((int) $lengowOrder->getData(LengowOrder::FIELD_ORDER_PROCESS_STATE) !== $processStateFinish
                        && (bool) $lengowOrder->getData(LengowOrder::FIELD_IS_IN_ERROR) === false
                    ) {
                        // if action is denied -> create order error
                        $errorMessage = $this->dataHelper->setLogMessage('order action is too old. Please retry');
                        $orderError = $this->lengowOrderErrorFactory->create();
                        $orderError->createOrderError(
                            [
                                LengowOrderError::FIELD_ORDER_LENGOW_ID => $lengowOrder->getId(),
                                LengowOrderError::FIELD_MESSAGE => $errorMessage,
                                LengowOrderError::FIELD_TYPE => LengowOrderError::TYPE_ERROR_SEND,
                            ]
                        );
                        $lengowOrder->updateOrder([LengowOrder::FIELD_IS_IN_ERROR => 1]);
                        $decodedMessage = $this->dataHelper->decodeLogMessage($errorMessage, false);
                        $this->dataHelper->log(
                            DataHelper::CODE_ACTION,
                            $this->dataHelper->setLogMessage('order action failed - %1', [$decodedMessage]),
                            $logOutput,
                            $lengowOrder->getData(LengowOrder::FIELD_MARKETPLACE_SKU)
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
        $collection = $this->lengowActionCollection->create()
            ->addFieldToFilter(self::FIELD_STATE, self::STATE_NEW)
            ->addFieldToFilter(
                self::FIELD_CREATED_AT,
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
    public function checkActionNotSent(bool $logOutput = false): bool
    {
        if ($this->configHelper->debugModeIsActive()) {
            return false;
        }
        $this->dataHelper->log(
            DataHelper::CODE_ACTION,
            $this->dataHelper->setLogMessage('check actions not sent'),
            $logOutput
        );
        // get unsent orders
        $lengowOrder = $this->lengowOrderFactory->create();
        $unsentOrders = $lengowOrder->getUnsentOrders();
        if ($unsentOrders) {
            foreach ($unsentOrders as $unsentOrder) {
                if (!$this->getActionsByOrderId((int) $unsentOrder['order_id'], true)) {
                    $action = $unsentOrder['state'] === self::TYPE_CANCEL ? self::TYPE_CANCEL : self::TYPE_SHIP;
                    $order = $this->orderFactory->create()->load((int) $unsentOrder['order_id']);
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
    private function getIntervalTime(): int
    {
        $intervalTime = self::MAX_INTERVAL_TIME;
        $lastActionSynchronisation = $this->configHelper->get(ConfigHelper::LAST_UPDATE_ACTION_SYNCHRONIZATION);
        if ($lastActionSynchronisation) {
            $lastIntervalTime = time() - (int) $lastActionSynchronisation;
            $lastIntervalTime += self::SECURITY_INTERVAL_TIME;
            $intervalTime = $lastIntervalTime > $intervalTime ? $intervalTime : $lastIntervalTime;
        }
        return $intervalTime;
    }
}
