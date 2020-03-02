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
 * @subpackage  Controller
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Controller\Adminhtml\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Sales\Model\OrderFactory as MagentoOrderFactory;
use Lengow\Connector\Model\Import\Action as LengowAction;
use Lengow\Connector\Model\Import\Order as LengowOrder;

class Resend extends Action
{
    /**
     * @var MagentoOrderFactory Magento order factory instance
     */
    protected $_orderFactory;

    /**
     * @var LengowOrder Lengow order instance
     */
    protected $_lengowOrder;

    /**
     * Constructor
     *
     * @param Context $context Magento action context instance
     * @param MagentoOrderFactory $orderFactory Magento order factory instance
     * @param LengowOrder $lengowOrder Lengow order instance
     */
    public function __construct(
        Context $context,
        MagentoOrderFactory $orderFactory,
        LengowOrder $lengowOrder
    )
    {
        $this->_orderFactory = $orderFactory;
        $this->_lengowOrder = $lengowOrder;
        parent::__construct($context);
    }

    /**
     * Resend action
     *
     * @return Redirect
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $action = $this->getRequest()->getParam('status') === LengowOrder::STATE_CANCELED
            ? LengowAction::TYPE_CANCEL
            : LengowAction::TYPE_SHIP;
        $order = $this->_orderFactory->create()->load((int)$orderId);
        $shipment = $action === LengowAction::TYPE_SHIP ? $order->getShipmentsCollection()->getFirstItem() : null;
        $this->_lengowOrder->callAction($action, $order, $shipment);
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
    }
}
