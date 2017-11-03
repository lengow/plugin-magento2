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
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Lengow\Connector\Model\Exception as LengowException;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Connector;

/**
 * Model marketplace
 */
class Marketplace extends AbstractModel
{
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
     * @var \Lengow\Connector\Model\Import\Action Lengow action instance
     */
    protected $_orderAction;

    /**
     * @var \Lengow\Connector\Model\Import\ActionFactory Lengow action factory instance
     */
    protected $_orderActionFactory;

    /**
     * @var \Lengow\Connector\Model\Import\Ordererror Lengow ordererror instance
     */
    protected $_orderError;

    /**
     * @var array all valid actions
     */
    public static $validActions = [
        'ship',
        'cancel'
    ];

    /**
     * @var array all marketplaces allowed for an account ID
     */
    public static $marketplaces = [];

    /**
     * @var mixed the current marketplace
     */
    public $marketplace;

    /**
     * @var string the name of the marketplace
     */
    public $name;

    /**
     * @var string the old code of the marketplace for v2 compatibility
     */
    public $legacyCode;

    /**
     * @var string the name of the marketplace
     */
    public $labelName;

    /**
     * @var boolean if the marketplace is loaded
     */
    public $isLoaded = false;

    /**
     * @var array Lengow states => marketplace states
     */
    public $statesLengow = [];

    /**
     * @var array marketplace states => Lengow states
     */
    public $states = [];

    /**
     * @var array all possible actions of the marketplace
     */
    public $actions = [];

    /**
     * @var array all possible values for actions of the marketplace
     */
    public $argValues = [];

    /**
     * @var array all carriers of the marketplace
     */
    public $carriers = [];

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context Magento context instance
     * @param \Magento\Framework\Registry $registry Magento registry instance
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper Magento json helper instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Model\Connector $modelConnector Lengow connector instance
     * @param \Lengow\Connector\Model\Import\Action $orderAction Lengow action instance
     * @param \Lengow\Connector\Model\Import\ActionFactory $orderActionFactory Lengow action factory instance
     * @param \Lengow\Connector\Model\Import\Ordererror $orderError Lengow order error instance
     */
    public function __construct(
        Context $context,
        Registry $registry,
        JsonHelper $jsonHelper,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        Connector $modelConnector,
        Action $orderAction,
        ActionFactory $orderActionFactory,
        Ordererror $orderError
    )
    {
        $this->_jsonHelper = $jsonHelper;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_connector = $modelConnector;
        $this->_orderAction = $orderAction;
        $this->_orderActionFactory = $orderActionFactory;
        $this->_orderError = $orderError;
        parent::__construct($context, $registry);
    }

    /**
     * Construct a new Marketplace instance with marketplace API
     *
     * @param array $params options
     * string  name     Marketplace name
     *
     * @throws LengowException marketplace not present
     */
    public function init($params = [])
    {
        $this->loadApiMarketplace();
        $this->name = strtolower($params['name']);
        if (!isset(self::$marketplaces->{$this->name})) {
            throw new LengowException(
                $this->_dataHelper->setLogMessage(
                    'Lengow error: %1 does not feature in the marketplace list',
                    [$this->name]
                )
            );
        }
        $this->marketplace = self::$marketplaces->{$this->name};
        if (!empty($this->marketplace)) {
            $this->legacyCode = $this->marketplace->legacy_code;
            $this->labelName = $this->marketplace->name;
            foreach ($this->marketplace->orders->status as $key => $state) {
                foreach ($state as $value) {
                    $this->statesLengow[(string)$value] = (string)$key;
                    $this->states[(string)$key][(string)$value] = (string)$value;
                }
            }
            foreach ($this->marketplace->orders->actions as $key => $action) {
                foreach ($action->status as $state) {
                    $this->actions[(string)$key]['status'][(string)$state] = (string)$state;
                }
                foreach ($action->args as $arg) {
                    $this->actions[(string)$key]['args'][(string)$arg] = (string)$arg;
                }
                foreach ($action->optional_args as $optional_arg) {
                    $this->actions[(string)$key]['optional_args'][(string)$optional_arg] = $optional_arg;
                }
                foreach ($action->args_description as $argKey => $argDescription) {
                    $validValues = [];
                    if (isset($argDescription->valid_values)) {
                        foreach ($argDescription->valid_values as $code => $validValue) {
                            $validValues[(string)$code] = isset($validValue->label)
                                ? (string)$validValue->label
                                : (string)$validValue;
                        }
                    }
                    $defaultValue = isset($argDescription->default_value)
                        ? (string)$argDescription->default_value
                        : '';
                    $acceptFreeValue = isset($argDescription->accept_free_values)
                        ? (bool)$argDescription->accept_free_values
                        : true;
                    $this->argValues[(string)$argKey] = [
                        'default_value' => $defaultValue,
                        'accept_free_values' => $acceptFreeValue,
                        'valid_values' => $validValues
                    ];
                }
            }
            if (isset($this->marketplace->orders->carriers)) {
                foreach ($this->marketplace->orders->carriers as $key => $carrier) {
                    $this->carriers[(string)$key] = (string)$carrier->label;
                }
            }
            $this->isLoaded = true;
        }
    }


    /**
     * Load the json configuration of all marketplaces
     */
    public function loadApiMarketplace()
    {
        if (count(self::$marketplaces) === 0) {
            self::$marketplaces = $this->_connector->queryApi('get', '/v3.0/marketplaces');
        }
    }

    /**
     * Get the real lengow's state
     *
     * @param string $name The marketplace state
     *
     * @return string The lengow state
     */
    public function getStateLengow($name)
    {
        if (array_key_exists($name, $this->statesLengow)) {
            return $this->statesLengow[$name];
        }
        return '';
    }

    /**
     * Get the default value for argument
     *
     * @param string $name The argument's name
     *
     * @return string|false
     */
    public function getDefaultValue($name)
    {
        if (array_key_exists($name, $this->argValues)) {
            $defaultValue = $this->argValues[$name]['default_value'];
            if (!empty($defaultValue)) {
                return $defaultValue;
            }
        }
        return false;
    }

    /**
     * Is marketplace contain order Line
     *
     * @param string $action order action (ship or cancel)
     *
     * @return bool
     */
    public function containOrderLine($action)
    {
        if (isset($this->actions[$action])) {
            $actions = $this->actions[$action];
            if (isset($actions['args']) && is_array($actions['args'])) {
                if (in_array('line', $actions['args'])) {
                    return true;
                }
            }
            if (isset($actions['optional_args']) && is_array($actions['optional_args'])) {
                if (in_array('line', $actions['optional_args'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Call Action with marketplace
     *
     * @param string $action order action (ship or cancel)
     * @param \Magento\Sales\Model\Order $order Magento order instance
     * @param \Lengow\Connector\Model\Import\Order $lengowOrder Lengow order instance
     * @param \Magento\Sales\Model\Order\Shipment $shipment Magento shipment instance
     * @param string $orderLineId Lengow order line id
     *
     * @throws LengowException action not valid / marketplace action not present
     *                         store id is required / marketplace name is required
     *                         argument is required / action not created
     *
     * @return boolean
     */
    public function callAction($action, $order, $lengowOrder, $shipment = null, $orderLineId = null)
    {
        try {
            if (!in_array($action, self::$validActions)) {
                throw new LengowException($this->_dataHelper->setLogMessage('action %1 is not valid', [$action]));
            }
            if (!isset($this->actions[$action])) {
                throw new LengowException(
                    $this->_dataHelper->setLogMessage('the marketplace action %1 is not present', [$action])
                );
            }
            if ((int)$order->getStoreId() == 0) {
                throw new LengowException($this->_dataHelper->setLogMessage('store ID is required'));
            }
            if (strlen($lengowOrder->getData('marketplace_name')) == 0) {
                throw new LengowException($this->_dataHelper->setLogMessage('marketplace name is required'));
            }
            // Get all arguments from API
            $params = [];
            $actions = $this->actions[$action];
            if (isset($actions['args']) && isset($actions['optional_args'])) {
                $allArgs = array_merge($actions['args'], $actions['optional_args']);
            } elseif (!isset($actions['args']) && isset($actions['optional_args'])) {
                $allArgs = $actions['optional_args'];
            } elseif (isset($actions['args'])) {
                $allArgs = $actions['args'];
            } else {
                $allArgs = [];
            }
            // Get all order informations
            foreach ($allArgs as $arg) {
                switch ($arg) {
                    case 'tracking_number':
                        $trackings = $shipment->getAllTracks();
                        if (!empty($trackings)) {
                            $lastTrack = end($trackings);
                        }
                        $params[$arg] = isset($lastTrack) ? $lastTrack->getNumber() : '';
                        break;
                    case 'carrier':
                    case 'carrier_name':
                    case 'shipping_method':
                        if (strlen((string)$lengowOrder->getData('carrier')) > 0) {
                            $carrierCode = (string)$lengowOrder->getData('carrier');
                        } else {
                            $trackings = $shipment->getAllTracks();
                            if (!empty($trackings)) {
                                $lastTrack = end($trackings);
                            }
                            $carrierCode = isset($lastTrack)
                                ? $this->_matchCarrier($lastTrack->getCarrierCode(), $lastTrack->getTitle())
                                : '';
                        }
                        $params[$arg] = $carrierCode;
                        break;
                    case 'shipping_price':
                        $params[$arg] = $order->getShippingInclTax();
                        break;
                    case 'shipping_date':
                        $params[$arg] = date('c');
                        break;
                    default:
                        if (isset($actions['optional_args']) && in_array($arg, $actions['optional_args'])) {
                            continue;
                        }
                        $defaultValue = $this->getDefaultValue((string)$arg);
                        $paramValue = $defaultValue ? $defaultValue : $arg . ' not available';
                        $params[$arg] = $paramValue;
                        break;
                }
            }
            if (!is_null($orderLineId)) {
                $params['line'] = $orderLineId;
            }
            // Check all required arguments
            if (isset($actions['args'])) {
                foreach ($actions['args'] as $arg) {
                    if (!isset($params[$arg]) || strlen($params[$arg]) == 0) {
                        throw new LengowException(
                            $this->_dataHelper->setLogMessage("can't send action: %1 is required", [$arg])
                        );
                    }
                }
            }
            // Clean empty optional arguments
            if (isset($actions['optional_args'])) {
                foreach ($actions['optional_args'] as $arg) {
                    if (isset($params[$arg]) && strlen($params[$arg]) == 0) {
                        unset($params[$arg]);
                    }
                }
            }
            // Set identification parameters
            $params['marketplace_order_id'] = $lengowOrder->getData('marketplace_sku');
            $params['marketplace'] = $lengowOrder->getData('marketplace_name');
            $params['action_type'] = $action;
            $result = $this->_connector->queryApi(
                'get',
                '/v3.0/orders/actions/',
                array_merge($params, ['queued' => 'True'])
            );
            if (isset($result->error) && isset($result->error->message)) {
                throw new LengowException($result->error->message);
            }
            if (isset($result->count) && $result->count > 0) {
                foreach ($result->results as $row) {
                    $orderActionId = $this->_orderAction->getActiveActionByActionId($row->id);
                    if ($orderActionId) {
                        $orderAction = $this->_orderActionFactory->create()->load($orderActionId);
                        $retry = (int)$orderAction->getData('retry') + 1;
                        $orderAction->updateAction(['retry' => $retry]);
                        unset($orderAction);
                    } else {
                        // if update doesn't work, create new action
                        $this->_orderAction->createAction(
                            [
                                'order_id' => $order->getId(),
                                'action_type' => $action,
                                'action_id' => $row->id,
                                'order_line_sku' => isset($params['line']) ? $params['line'] : null,
                                'parameters' => $this->_jsonHelper->jsonEncode($params)
                            ]
                        );
                    }
                }
            } else {
                if (!(bool)$this->_configHelper->get('preprod_mode_enable')) {
                    $result = $this->_connector->queryApi('post', '/v3.0/orders/actions/', $params);
                    if (isset($result->id)) {
                        $this->_orderAction->createAction(
                            [
                                'order_id' => $order->getId(),
                                'action_type' => $action,
                                'action_id' => $result->id,
                                'order_line_sku' => isset($params['line']) ? $params['line'] : null,
                                'parameters' => $this->_jsonHelper->jsonEncode($params)
                            ]
                        );
                    } else {
                        throw new LengowException(
                            $this->_dataHelper->setLogMessage(
                                "can't create action: %1",
                                [$this->_jsonHelper->jsonEncode($result)]
                            )
                        );
                    }
                }
                // Create log for call action
                $paramList = false;
                foreach ($params as $param => $value) {
                    $paramList .= !$paramList ? '"' . $param . '": ' . $value : ' -- "' . $param . '": ' . $value;
                }
                $this->_dataHelper->log(
                    'API-OrderAction',
                    $this->_dataHelper->setLogMessage('call tracking with parameters: %1', [$paramList]),
                    false,
                    $lengowOrder->getData('marketplace_sku')
                );
            }
            return true;
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (\Exception $e) {
            $errorMessage = 'Magento error: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            if ((int)$lengowOrder->getData('order_process_state') != $lengowOrder->getOrderProcessState('closed')) {

                // TODO update is in error in lengow order
                // $lengowOrder->updateOrder(['is_in_error' => 1]);

                $this->_orderError->createOrderError(
                    [
                        'order_lengow_id' => $lengowOrder->getId(),
                        'message' => $errorMessage,
                        'type' => 'send'
                    ]
                );
            }
            $decodedMessage = $this->_dataHelper->decodeLogMessage($errorMessage, 'en_GB');
            $this->_dataHelper->log(
                'API-OrderAction',
                $this->_dataHelper->setLogMessage('order action failed - %1', [$decodedMessage]),
                false,
                $lengowOrder->getData('marketplace_sku')
            );
        }
        return false;
    }

    /**
     * Match carrier's name with accepted values
     *
     * @param string $code carrier code
     * @param string $title carrier title
     *
     * @return string
     */
    private function _matchCarrier($code, $title)
    {
        if (count($this->carriers) > 0) {
            // search by code
            foreach ($this->carriers as $key => $carrier) {
                if (preg_match('`' . $key . '`i', trim($code))) {
                    return $key;
                } elseif (preg_match('`.*?' . $key . '.*?`i', $code)) {
                    return $key;
                }
            }
            // search by title
            foreach ($this->carriers as $key => $carrier) {
                if (preg_match('`' . $key . '`i', trim($title))) {
                    return $key;
                } elseif (preg_match('`.*?' . $key . '.*?`i', $title)) {
                    return $key;
                }
            }
        }
        // no match
        if ($code == 'custom') {
            return $title;
        }
        return $code;
    }
}
