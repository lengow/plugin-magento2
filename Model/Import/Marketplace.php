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

use Lengow\Connector\Helper\Sync;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Lengow\Connector\Model\Exception as LengowException;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Model\Connector;

/**
 * Model marketplace
 */
class Marketplace extends AbstractModel
{
    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Helper\Sync Lengow sync helper instance
     */
    protected $_syncHelper;

    /**
     * @var \Lengow\Connector\Model\Connector Lengow connector instance
     */
    protected $_connector;

    /**
     * @var \Lengow\Connector\Model\Import\Action Lengow action instance
     */
    protected $_orderAction;

    /**
     * @var \Lengow\Connector\Model\Import\OrdererrorFactory Lengow order error factory instance
     */
    protected $_orderErrorFactory;

    /**
     * @var array all valid actions
     */
    public static $validActions = [
        'ship',
        'cancel',
    ];

    /**
     * @var Object all marketplaces allowed for an account ID
     */
    public static $marketplaces = false;

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
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Helper\Sync $syncHelper Lengow sync helper instance
     * @param \Lengow\Connector\Model\Connector $modelConnector Lengow connector instance
     * @param \Lengow\Connector\Model\Import\Action $orderAction Lengow action instance
     * @param \Lengow\Connector\Model\Import\OrdererrorFactory $orderErrorFactory Lengow order error factory instance
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        SyncHelper $syncHelper,
        Connector $modelConnector,
        Action $orderAction,
        OrdererrorFactory $orderErrorFactory
    )
    {
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_syncHelper = $syncHelper;
        $this->_connector = $modelConnector;
        $this->_orderAction = $orderAction;
        $this->_orderErrorFactory = $orderErrorFactory;
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
                        'valid_values' => $validValues,
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
        if (!self::$marketplaces) {
            self::$marketplaces = $this->_syncHelper->getMarketplaces();
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
     * Get the action with parameters
     *
     * @param string $action order action (ship or cancel)
     *
     * @return array|false
     */
    public function getAction($action)
    {
        if (array_key_exists($action, $this->actions)) {
            return $this->actions[$action];
        }
        return false;
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
     * @param \Magento\Sales\Model\Order\Shipment|null $shipment Magento shipment instance
     * @param string|null $orderLineId Lengow order line id
     *
     * @return boolean
     */
    public function callAction($action, $order, $lengowOrder, $shipment = null, $orderLineId = null)
    {
        try {
            // check the action and order data
            $this->_checkAction($action);
            $this->_checkOrderData($lengowOrder);
            // get all required and optional arguments for a specific marketplace
            $marketplaceArguments = $this->_getMarketplaceArguments($action);
            // get all available values from an order
            $params = $this->_getAllParams($action, $order, $lengowOrder, $shipment, $marketplaceArguments);
            // check required arguments and clean value for empty optionals arguments
            $params = $this->_checkAndCleanParams($action, $params);
            // complete the values with the specific values of the account
            if (!is_null($orderLineId)) {
                $params['line'] = $orderLineId;
            }
            $params['marketplace_order_id'] = $lengowOrder->getData('marketplace_sku');
            $params['marketplace'] = $lengowOrder->getData('marketplace_name');
            $params['action_type'] = $action;
            // checks whether the action is already created to not return an action
            $canSendAction = $this->_orderAction->canSendAction($params, $order);
            if ($canSendAction) {
                // send a new action on the order via the Lengow API
                $this->_orderAction->sendAction($params, $order, $lengowOrder);
            }
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (\Exception $e) {
            $errorMessage = 'Magento error: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            if ((int)$lengowOrder->getData('order_process_state') != $lengowOrder->getOrderProcessState('closed')) {
                $lengowOrder->updateOrder(['is_in_error' => 1]);
                $orderError = $this->_orderErrorFactory->create();
                $orderError->createOrderError(
                    [
                        'order_lengow_id' => $lengowOrder->getId(),
                        'message' => $errorMessage,
                        'type' => 'send',
                    ]
                );
                unset($orderError);
            }
            $decodedMessage = $this->_dataHelper->decodeLogMessage($errorMessage, false);
            $this->_dataHelper->log(
                'API-OrderAction',
                $this->_dataHelper->setLogMessage('order action failed - %1', [$decodedMessage]),
                false,
                $lengowOrder->getData('marketplace_sku')
            );
            return false;
        }
        return true;
    }

    /**
     * Check if the action is valid and present on the marketplace
     *
     * @param string $action Lengow order actions type (ship or cancel)
     *
     * @throws LengowException action not valid / marketplace action not present
     */
    protected function _checkAction($action)
    {
        if (!in_array($action, self::$validActions)) {
            throw new LengowException($this->_dataHelper->setLogMessage('action %1 is not valid', [$action]));
        }
        if (!isset($this->actions[$action])) {
            throw new LengowException(
                $this->_dataHelper->setLogMessage('the marketplace action %1 is not present', [$action])
            );
        }
    }

    /**
     * Check if the essential data of the order are present
     *
     * @param \Lengow\Connector\Model\Import\Order $lengowOrder Lengow order instance
     *
     * @throws LengowException marketplace sku is required / marketplace name is required
     */
    protected function _checkOrderData($lengowOrder)
    {
        if (strlen($lengowOrder->getData('marketplace_sku')) === 0) {
            throw new LengowException($this->_dataHelper->setLogMessage('marketplace order reference is required'));
        }
        if (strlen($lengowOrder->getData('marketplace_name')) === 0) {
            throw new LengowException($this->_dataHelper->setLogMessage('marketplace name is required'));
        }
    }

    /**
     * Get all marketplace arguments for a specific action
     *
     * @param string $action Lengow order actions type (ship or cancel)
     *
     * @return array
     */
    protected function _getMarketplaceArguments($action)
    {
        $actions = $this->getAction($action);
        if (isset($actions['args']) && isset($actions['optional_args'])) {
            $marketplaceArguments = array_merge($actions['args'], $actions['optional_args']);
        } elseif (!isset($actions['args']) && isset($actions['optional_args'])) {
            $marketplaceArguments = $actions['optional_args'];
        } elseif (isset($actions['args'])) {
            $marketplaceArguments = $actions['args'];
        } else {
            $marketplaceArguments = [];
        }
        return $marketplaceArguments;
    }

    /**
     * Get all available values from an order
     *
     * @param string $action Lengow order actions type (ship or cancel)
     * @param \Magento\Sales\Model\Order $order Magento order instance
     * @param \Lengow\Connector\Model\Import\Order $lengowOrder Lengow order instance
     * @param \Magento\Sales\Model\Order\Shipment $shipment Magento shipment instance
     * @param array $marketplaceArguments All marketplace arguments for a specific action
     *
     * @return array
     */
    protected function _getAllParams($action, $order, $lengowOrder, $shipment, $marketplaceArguments)
    {
        $params = [];
        $actions = $this->getAction($action);
        // get all order informations
        foreach ($marketplaceArguments as $arg) {
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
                case 'custom_carrier':
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
                case 'delivery_date':
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
        return $params;
    }

    /**
     * Get all available values from an order
     *
     * @param string $action Lengow order actions type (ship or cancel)
     * @param array $params all available values
     *
     * @throws LengowException argument is required
     *
     * @return array
     */
    protected function _checkAndCleanParams($action, $params)
    {
        $actions = $this->getAction($action);
        // check all required arguments
        if (isset($actions['args'])) {
            foreach ($actions['args'] as $arg) {
                if (!isset($params[$arg]) || strlen($params[$arg]) === 0) {
                    throw new LengowException(
                        $this->_dataHelper->setLogMessage("can't send action: %1 is required", [$arg])
                    );
                }
            }
        }
        // clean empty optional arguments
        if (isset($actions['optional_args'])) {
            foreach ($actions['optional_args'] as $arg) {
                if (isset($params[$arg]) && strlen($params[$arg]) === 0) {
                    unset($params[$arg]);
                }
            }
        }
        return $params;
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
            $codeCleaned = $this->_cleanString($code);
            $titleCleaned = $this->_cleanString($title);
            foreach ($this->carriers as $key => $label) {
                $keyCleaned = $this->_cleanString($key);
                $labelCleaned = $this->_cleanString($label);
                // search by code
                // search on the carrier key
                $found = $this->_searchValue($keyCleaned, $codeCleaned);
                // search on the carrier label if it is different from the key
                if (!$found && $labelCleaned !== $keyCleaned) {
                    $found = $this->_searchValue($labelCleaned, $codeCleaned);
                }
                // search by title if it is different from the code
                if (!$found && $titleCleaned !== $codeCleaned) {
                    // search on the carrier key
                    $found = $this->_searchValue($keyCleaned, $titleCleaned);
                    // search on the carrier label if it is different from the key
                    if (!$found && $labelCleaned !== $keyCleaned) {
                        $found = $this->_searchValue($labelCleaned, $titleCleaned);
                    }
                }
                if ($found) {
                    return $key;
                }
            }
        }
        // no match
        if ($code === \Magento\Sales\Model\Order\Shipment\Track::CUSTOM_CARRIER_CODE) {
            return $title;
        }
        return $code;
    }

    /**
     * Cleaning a string before search
     *
     * @param string $string string to clean
     *
     * @return string
     */
    private function _cleanString($string)
    {
        $cleanFilters = array(' ', '-', '_', '.');
        return strtolower(str_replace($cleanFilters, '',  trim($string)));
    }

    /**
     * Strict and then approximate search for a chain
     *
     * @param string $pattern search pattern
     * @param string $subject string to search
     *
     * @return boolean
     */
    private function _searchValue($pattern, $subject)
    {
        $found = false;
        if (preg_match('`' . $pattern . '`i', $subject)) {
            $found = true;
        } elseif (preg_match('`.*?' . $pattern . '.*?`i', $subject)) {
            $found = true;
        }
        return $found;
    }
}
