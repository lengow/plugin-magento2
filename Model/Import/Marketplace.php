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
use Magento\Framework\Model\Context;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Model\Exception as LengowException;
use Lengow\Connector\Model\Import as LengowImport;
use Lengow\Connector\Model\Import\Action as LengowAction;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\Ordererror as LengowOrderError;
use Lengow\Connector\Model\Import\OrdererrorFactory as LengowOrderErrorFactory;

/**
 * Model marketplace
 */
class Marketplace extends AbstractModel
{
    /**
     * @var TimezoneInterface Magento datetime timezone instance
     */
    private $timezone;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var SyncHelper Lengow sync helper instance
     */
    private $syncHelper;

    /**
     * @var LengowAction Lengow action instance
     */
    private $orderAction;

    /**
     * @var LengowOrderErrorFactory Lengow order error factory instance
     */
    private $orderErrorFactory;

    /**
     * @var array all valid actions
     */
    public static $validActions = [
        LengowAction::TYPE_SHIP,
        LengowAction::TYPE_CANCEL,
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
     */
    public function __construct(
        Context $context,
        Registry $registry,
        TimezoneInterface $timezone,
        DataHelper $dataHelper,
        SyncHelper $syncHelper,
        LengowAction $orderAction,
        LengowOrderErrorFactory $orderErrorFactory
    ) {
        $this->timezone = $timezone;
        $this->dataHelper = $dataHelper;
        $this->syncHelper = $syncHelper;
        $this->orderAction = $orderAction;
        $this->orderErrorFactory = $orderErrorFactory;
        parent::__construct($context, $registry);
    }

    /**
     * Construct a new Marketplace instance with marketplace API
     *
     * @throws LengowException
     */
    public function init(array $params = []): void
    {
        $this->loadApiMarketplace();
        $this->name = strtolower($params['name']);
        if (!isset(self::$marketplaces->{$this->name})) {
            throw new LengowException(
                $this->dataHelper->setLogMessage(
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
                    $this->statesLengow[(string) $value] = (string) $key;
                    $this->states[(string) $key][(string) $value] = (string) $value;
                }
            }
            foreach ($this->marketplace->orders->actions as $key => $action) {
                foreach ($action->status as $state) {
                    $this->actions[(string) $key]['status'][(string) $state] = (string) $state;
                }
                foreach ($action->args as $arg) {
                    $this->actions[(string) $key]['args'][(string) $arg] = (string) $arg;
                }
                foreach ($action->optional_args as $optional_arg) {
                    $this->actions[(string) $key]['optional_args'][(string) $optional_arg] = $optional_arg;
                }
                foreach ($action->args_description as $argKey => $argDescription) {
                    $validValues = [];
                    if (isset($argDescription->valid_values)) {
                        foreach ($argDescription->valid_values as $code => $validValue) {
                            $validValues[(string) $code] = isset($validValue->label)
                                ? (string) $validValue->label
                                : (string) $validValue;
                        }
                    }
                    $defaultValue = isset($argDescription->default_value)
                        ? (string) $argDescription->default_value
                        : '';
                    $acceptFreeValue = !isset($argDescription->accept_free_values)
                        || $argDescription->accept_free_values;
                    $this->argValues[(string) $argKey] = [
                        'default_value' => $defaultValue,
                        'accept_free_values' => $acceptFreeValue,
                        'valid_values' => $validValues,
                    ];
                }
            }
            if (isset($this->marketplace->orders->carriers)) {
                foreach ($this->marketplace->orders->carriers as $key => $carrier) {
                    $this->carriers[(string) $key] = (string) $carrier->label;
                }
            }
            $this->isLoaded = true;
        }
    }

    /**
     * Load the json configuration of all marketplaces
     */
    public function loadApiMarketplace(): void
    {
        if (!self::$marketplaces) {
            self::$marketplaces = $this->syncHelper->getMarketplaces();
        }
    }

    /**
     * Get the real lengow's state
     */
    public function getStateLengow(string $name): string
    {
        if (array_key_exists($name, $this->statesLengow)) {
            return $this->statesLengow[$name];
        }
        return '';
    }

    /**
     * Get the action with parameters
     */
    public function getAction(string $action): array
    {
        if (array_key_exists($action, $this->actions)) {
            return $this->actions[$action];
        }
        return [];
    }

    /**
     * Check if has the field
     */
    public function hasReturnTrackingNumber() : bool
    {
        $action = $this->getAction(LengowAction::TYPE_SHIP);
        if (empty($action)) {
            return false;
        }
        $arguments = $this->getMarketplaceArguments(LengowAction::TYPE_SHIP);

        return in_array(LengowAction::ARG_RETURN_TRACKING_NUMBER, $arguments);
    }

    /**
     * Check if has the field
     */
    public function hasReturnTrackingCarrier() : bool
    {
        $action =  $this->getAction(LengowAction::TYPE_SHIP);
        if (empty($action)) {
            return false;
        }
        $arguments = $this->getMarketplaceArguments(LengowAction::TYPE_SHIP);

        return in_array(LengowAction::ARG_RETURN_CARRIER, $arguments);
    }

    /**
     * Get the default value for argument
     */
    public function getDefaultValue(string $name): string
    {
        if (array_key_exists($name, $this->argValues)) {
            $defaultValue = $this->argValues[$name]['default_value'];
            if (!empty($defaultValue)) {
                return $defaultValue;
            }
        }
        return '';
    }

    /**
     * Is marketplace contain order Line
     */
    public function containOrderLine(string $action): bool
    {
        if (isset($this->actions[$action])) {
            $actions = $this->actions[$action];
            if (isset($actions['args'])
                && is_array($actions['args'])
                && in_array(LengowAction::ARG_LINE, $actions['args'], true)
            ) {
                return true;
            }
            if (isset($actions['optional_args'])
                && is_array($actions['optional_args'])
                && in_array(LengowAction::ARG_LINE, $actions['optional_args'], true)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Call Action with marketplace
     */
    public function callAction(
        string $action,
        MagentoOrder $order,
        Order $lengowOrder,
        Shipment $shipment = null,
        string $orderLineId = null
    ): bool {
        try {
            // check the action and order data
            $this->checkAction($action);
            $this->checkOrderData($lengowOrder);
            // get all required and optional arguments for a specific marketplace
            $marketplaceArguments = $this->getMarketplaceArguments($action);
            // get all available values from an order
            $params = $this->getAllParams($action, $order, $lengowOrder, $shipment, $marketplaceArguments);
            // check required arguments and clean value for empty optionals arguments
            $params = $this->checkAndCleanParams($action, $params);
            // complete the values with the specific values of the account
            if ($orderLineId !== null) {
                $params[LengowAction::ARG_LINE] = $orderLineId;
            }
            $params[LengowImport::ARG_MARKETPLACE_ORDER_ID] = $lengowOrder->getData(LengowOrder::FIELD_MARKETPLACE_SKU);
            $params[LengowImport::ARG_MARKETPLACE] = $lengowOrder->getData(LengowOrder::FIELD_MARKETPLACE_NAME);
            $params[LengowAction::ARG_ACTION_TYPE] = $action;
            // checks whether the action is already created to not return an action
            $canSendAction = $this->orderAction->canSendAction($params, $order);
            if ($canSendAction) {
                // send a new action on the order via the Lengow API
                $this->orderAction->sendAction($params, $order, $lengowOrder);
            }
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Magento error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            $orderProcessState = (int) $lengowOrder->getData(LengowOrder::FIELD_ORDER_PROCESS_STATE);
            if ($orderProcessState !== $lengowOrder->getOrderProcessState(LengowOrder::STATE_CLOSED)) {
                $lengowOrder->updateOrder([LengowOrder::FIELD_IS_IN_ERROR => 1]);
                $orderError = $this->orderErrorFactory->create();
                $orderError->createOrderError(
                    [
                        LengowOrderError::FIELD_ORDER_LENGOW_ID => $lengowOrder->getId(),
                        LengowOrderError::FIELD_MESSAGE => $errorMessage,
                        LengowOrderError::FIELD_TYPE => LengowOrderError::TYPE_ERROR_SEND,
                    ]
                );
                unset($orderError);
            }
            $decodedMessage = $this->dataHelper->decodeLogMessage($errorMessage, false);
            $this->dataHelper->log(
                DataHelper::CODE_ACTION,
                $this->dataHelper->setLogMessage('order action failed - %1', [$decodedMessage]),
                false,
                $lengowOrder->getData(LengowOrder::FIELD_MARKETPLACE_SKU)
            );
            return false;
        }
        return true;
    }

    /**
     * Check if the action is valid and present on the marketplace
     *
     * @throws LengowException
     */
    private function checkAction(string $action): void
    {
        if (!in_array($action, self::$validActions, true)) {
            throw new LengowException($this->dataHelper->setLogMessage('action %1 is not valid', [$action]));
        }
        if (!isset($this->actions[$action])) {
            throw new LengowException(
                $this->dataHelper->setLogMessage('the marketplace action %1 is not present', [$action])
            );
        }
    }

    /**
     * Check if the essential data of the order are present
     *
     * @throws LengowException
     */
    private function checkOrderData(Order $lengowOrder): void
    {
        if ($lengowOrder->getData(LengowOrder::FIELD_MARKETPLACE_SKU) === '') {
            throw new LengowException($this->dataHelper->setLogMessage('marketplace order reference is required'));
        }
        if ($lengowOrder->getData(LengowOrder::FIELD_MARKETPLACE_NAME) === '') {
            throw new LengowException($this->dataHelper->setLogMessage('marketplace name is required'));
        }
    }

    /**
     * Get all marketplace arguments for a specific action
     */
    private function getMarketplaceArguments(string $action): array
    {
        $actions = $this->getAction($action);
        if (isset($actions['args'], $actions['optional_args'])) {
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
     */
    private function getAllParams(
        string $action,
        MagentoOrder $order,
        Order $lengowOrder,
        Shipment $shipment = null,
        array $marketplaceArguments = []
    ): array {
        $params = [];
        $actions = $this->getAction($action);

        // get all order data
        foreach ($marketplaceArguments as $arg) {
            switch ($arg) {
                case LengowAction::ARG_TRACKING_NUMBER:
                    $tracks = $shipment ? $shipment->getAllTracks() : null;
                    if (!empty($tracks)) {
                        $lastTrack = end($tracks);
                    }
                    $params[$arg] = isset($lastTrack) ? $lastTrack->getNumber() : '';
                    break;
                case LengowAction::ARG_RETURN_TRACKING_NUMBER:
                    $tracks = $shipment ? $shipment->getAllTracks() : null;
                    if (!empty($tracks)) {
                        $lastTrack = end($tracks);
                    }

                    $params[$arg] = isset($lastTrack) ? $lastTrack->getReturnTrackNumber() : '';
                    break;
                case LengowAction::ARG_CARRIER:
                case LengowAction::ARG_CARRIER_NAME:
                case LengowAction::ARG_CUSTOM_CARRIER:
                    if ((string) $lengowOrder->getData(LengowOrder::FIELD_CARRIER) !== '') {
                        $carrierCode = (string) $lengowOrder->getData(LengowOrder::FIELD_CARRIER);
                    } else {
                        $tracks = $shipment ? $shipment->getAllTracks() : null;
                        if (!empty($tracks)) {
                            $lastTrack = end($tracks);
                        }
                        $carrierCode = isset($lastTrack)
                            ? $this->matchCarrier((string)$lastTrack->getCarrierCode(), (string)$lastTrack->getTitle())
                            : '';
                    }
                    $params[$arg] = $carrierCode;
                    break;
                case LengowAction::ARG_RETURN_CARRIER:
                    $tracks = $shipment ? $shipment->getAllTracks() : null;
                    if (!empty($tracks)) {
                        $lastTrack = end($tracks);
                    }
                    $returnCarrierCode = isset($lastTrack)
                        ? $this->matchCarrier(strtolower((string) $lastTrack->getReturnCarrierCode()), '')
                        : '';

                    $params[$arg] = $returnCarrierCode;
                    break;
                case LengowAction::ARG_SHIPPING_METHOD:
                    $tracks = $shipment ? $shipment->getAllTracks() : null;
                    if (!empty($tracks)) {
                        $lastTrack = end($tracks);
                    }
                    $shipping_method = isset($lastTrack)
                        ? $this->matchCarrier(strtolower((string) $lastTrack->getShippingMethod()), '')
                        : '';

                    $params[$arg] = $shipping_method;
                    break;
                case LengowAction::ARG_SHIPPING_PRICE:
                    $params[$arg] = $order->getShippingInclTax();
                    break;
                case LengowAction::ARG_SHIPPING_DATE:
                case LengowAction::ARG_DELIVERY_DATE:
                    $params[$arg] = $this->timezone->date()->format(DataHelper::DATE_ISO_8601);
                    break;
                default:
                    if (isset($actions['optional_args']) && in_array($arg, $actions['optional_args'], true)) {
                        break;
                    }
                    $defaultValue = $this->getDefaultValue((string) $arg);
                    $paramValue = $defaultValue ?: $arg . ' not available';
                    $params[$arg] = $paramValue;
                    break;
            }
        }

        return $params;
    }

    /**
     * Get all available values from an order
     *
     * @throws LengowException
     */
    private function checkAndCleanParams(string $action, array $params): array
    {
        $actions = $this->getAction($action);
        // check all required arguments
        if (isset($actions['args'])) {
            foreach ($actions['args'] as $arg) {
                if (!isset($params[$arg]) || $params[$arg] === '') {
                    throw new LengowException(
                        $this->dataHelper->setLogMessage("can't send action: %1 is required", [$arg])
                    );
                }
            }
        }
        // clean empty optional arguments
        if (isset($actions['optional_args'])) {
            foreach ($actions['optional_args'] as $arg) {
                if (isset($params[$arg]) && $params[$arg] === '') {
                    unset($params[$arg]);
                }
            }
        }
        return $params;
    }

    /**
     * Match carrier's name with accepted values
     */
    private function matchCarrier(string $code, string $title): string
    {
        if ($code === Track::CUSTOM_CARRIER_CODE) {
            return $title;
        }

        if (empty($this->carriers)) {
            return $code;
        }

        $codeCleaned  = $this->cleanString($code);
        // strict search by code
        $result = $this->searchCarrierCode($codeCleaned);
        if (!$result) {
            // approximate search by code
            $result = $this->searchCarrierCode($codeCleaned, false);
        }
        if ($result) {
            return $result;
        }

        $titleCleaned = $this->cleanString($title);
        // search by Magento carrier title if it is different from the Magento carrier code
        if ($titleCleaned !== $codeCleaned) {
            // strict search by title
            $result = $this->searchCarrierCode($titleCleaned);
            if (!$result) {
                // approximate search by title
                $result = $this->searchCarrierCode($titleCleaned, false);
            }
        }
        if ($result) {
            return $result;
        }

        return $code;
    }

    /**
     * Cleaning a string before search
     *
     */
    private function cleanString(string $string): string
    {
        $cleanFilters = [' ', '-', '_', '.'];
        return strtolower(str_replace($cleanFilters, '', trim($string)));
    }

    /**
     * Search carrier code in a chain
     */
    private function searchCarrierCode(string $search, bool $strict = true)
    {
        $result = false;
        foreach ($this->carriers as $key => $label) {
            $keyCleaned = $this->cleanString($key);
            $labelCleaned = $this->cleanString($label);
            // search on the carrier key
            $found = $this->searchValue($keyCleaned, $search, $strict);
            // search on the carrier label if it is different from the key
            if (!$found && $labelCleaned !== $keyCleaned) {
                $found = $this->searchValue($labelCleaned, $search, $strict);
            }
            if ($found) {
                $result = $key;
                break;
            }
        }

        if ($result) {
            return $result;
        }

        if (!$strict) {
            // if no previous results, try to match the provided carrier code instead
            foreach ($this->carriers as $key => $label) {
                $keyCleaned = $this->cleanString($key);
                $labelCleaned = $this->cleanString($label);

                $found = $this->searchValue($search, $keyCleaned, false);
                if (!$found && $labelCleaned !== $keyCleaned) {
                    $found = $this->searchValue($search, $labelCleaned, false);
                }
                if ($found) {
                    $result = $key;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Strict or approximate search for a chain
     */
    private function searchValue(string $pattern, string $subject, bool $strict = true): bool
    {
        if ($strict) {
            $found = $pattern === $subject;
        } else {
            $found = (bool) preg_match('`.*?' . $pattern . '.*?`i', $subject);
        }
        return $found;
    }
}
