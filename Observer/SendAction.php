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
 * @subpackage  Plugin
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Observer;

use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Lengow\Connector\Model\Import\Action as LengowAction;
use Lengow\Connector\Model\Import\Order as LengowOrder;

class SendAction implements ObserverInterface
{
    /**
     * @var BackendSession Backend session instance
     */
    protected $backendSession;

    /**
     * @var LengowOrder Lengow order instance
     */
    protected $lengowOrder;

    /**
     * @var array order already shipped
     */
    protected $alreadyShipped = [];

    /**
     * Constructor
     *
     * @param BackendSession $backendSession Backend session instance
     * @param LengowOrder $lengowOrder Lengow order instance
     */
    public function __construct(
        BackendSession $backendSession,
        LengowOrder $lengowOrder
    ) {
        $this->backendSession = $backendSession;
        $this->lengowOrder = $lengowOrder;
    }

    /**
     * Sending a call WSDL for a new order shipment, a new tracking or a cancellation of order
     *
     * @param Observer $observer Magento observer instance
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $order = null;
        $shipment = null;
        $action = LengowAction::TYPE_SHIP;
        $eventName = $observer->getEvent()->getName();
        switch ($eventName) {
            case 'sales_order_shipment_save_after':
                $shipment = $observer->getEvent()->getShipment();
                $order = $shipment->getOrder();
                break;
            case 'sales_order_shipment_track_save_after':
                $track = $observer->getEvent()->getTrack();
                $shipment = $track->getShipment();
                $order = $shipment->getOrder();
                break;
            case 'sales_order_payment_cancel':
                $action = LengowAction::TYPE_CANCEL;
                $payment = $observer->getEvent()->getPayment();
                $order = $payment->getOrder();
                break;
            default:
                break;
        }
        if ($order) {
            $marketplaceSku = $this->lengowOrder->getMarketplaceSkuByOrderId($order->getId());
            if ($marketplaceSku
                && !array_key_exists($marketplaceSku, $this->alreadyShipped)
                && $marketplaceSku !== $this->backendSession->getCurrentOrderLengow()
            ) {
                $this->lengowOrder->callAction($action, $order, $shipment);
                $this->alreadyShipped[$marketplaceSku] = true;
            }
        }
    }
}
