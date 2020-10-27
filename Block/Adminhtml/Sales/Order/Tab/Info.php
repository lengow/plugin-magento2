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
 * @subpackage  Block
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Block\Adminhtml\Sales\Order\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order as MagentoOrder;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Import\Action as LengowAction;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;

class Info extends Template implements TabInterface
{
    /**
     * @var string Lengow template path
     */
    protected $_template = 'sales/order/tab/info.phtml';

    /**
     * @var Registry Magento Registry instance
     */
    protected $_coreRegistry;

    /**
     * @var MagentoOrder Magento order instance
     */
    protected $_order;

    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var LengowAction Lengow action instance
     */
    protected $_action;

    /**
     * @var LengowOrder Lengow order instance
     */
    protected $_lengowOrder;

    /**
     * @var LengowOrderFactory Lengow order factory instance
     */
    protected $_lengowOrderFactory;

    /**
     * Construct
     *
     * @param Context $context Magento Context instance
     * @param Registry $coreRegistry Magento Registry instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param LengowOrderFactory $lengowOrderFactory Lengow order factory instance
     * @param LengowAction $action Lengow action instance
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        LengowOrderFactory $lengowOrderFactory,
        LengowAction $action,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_lengowOrderFactory = $lengowOrderFactory;
        $this->_action = $action;
        $this->_order = $this->getOrder();
        $this->_lengowOrder = $this->getLengowOrder();
        parent::__construct($context, $data);
    }

    /**
     * Get tab label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Lengow');
    }

    /**
     * Get tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Lengow');
    }

    /**
     * Can show tab
     *
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Retrieve order model instance
     *
     * @return MagentoOrder
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Get Lengow order by Magento order id
     *
     * @return LengowOrder|false
     */
    public function getLengowOrder()
    {
        $lengowOrderId = $this->_lengowOrderFactory->create()->getLengowOrderIdByOrderId($this->getOrderId());
        if ($lengowOrderId) {
            return $this->_lengowOrderFactory->create()->load($lengowOrderId);
        }
        return false;
    }

    /**
     * Get Magento order id
     *
     * @return integer
     */
    public function getOrderId()
    {
        return (int)$this->_order->getId();
    }

    /**
     * Get Magento order status
     *
     * @return string
     */
    public function getOrderStatus()
    {
        return $this->_order->getStatus();
    }

    /**
     * Get Lengow order id if exist
     *
     * @return integer|false
     */
    public function getLengowOrderId()
    {
        return $this->_lengowOrder ? (int)$this->_lengowOrder->getId() : false;
    }

    /**
     * Debug Mode is enabled
     *
     * @return boolean
     */
    public function debugModeIsEnabled()
    {
        return $this->_configHelper->debugModeIsActive();
    }

    /**
     * Check if is a order imported by Lengow
     *
     * @return boolean
     */
    public function isOrderImportedByLengow()
    {
        return (bool)$this->_order->getData('from_lengow');
    }

    /**
     * Check if is a order with a Lengow order record
     *
     * @return boolean
     */
    public function isOrderFollowedByLengow()
    {
        return $this->_lengowOrder ? true : false;
    }

    /**
     * Check if can resend action order
     *
     * @return boolean
     */
    public function canReSendAction()
    {
        if (!$this->_action->getActiveActionByOrderId($this->getOrderId())) {
            $orderStatus = $this->getOrderStatus();
            if (($orderStatus === 'complete' || $orderStatus === 'canceled') && $this->_lengowOrder) {
                $finishProcessState = $this->_lengowOrder->getOrderProcessState('closed');
                if ($this->_lengowOrder->getData('order_process_state') != $finishProcessState) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get all Lengow order data
     *
     * @return array
     */
    public function getFields()
    {
        $fields = [];
        if ($this->_lengowOrder) {
            $fields[] = [
                'label' => __('Marketplace SKU'),
                'value' => $this->_lengowOrder->getData('marketplace_sku'),
            ];
            $fields[] = [
                'label' => __('Marketplace'),
                'value' => $this->_lengowOrder->getData('marketplace_label'),
            ];
            $fields[] = [
                'label' => __('Delivery Address ID'),
                'value' => $this->_lengowOrder->getData('delivery_address_id'),
            ];
            $fields[] = [
                'label' => __('Currency'),
                'value' => $this->_lengowOrder->getData('currency'),
            ];
            $fields[] = [
                'label' => __('Total Paid'),
                'value' => $this->_lengowOrder->getData('total_paid'),
            ];
            $fields[] = [
                'label' => __('Commission'),
                'value' => $this->_lengowOrder->getData('commission'),
            ];
            $fields[] = [
                'label' => __('Customer name'),
                'value' => $this->_lengowOrder->getData('customer_name'),
            ];
            $fields[] = [
                'label' => __('Customer email'),
                'value' => $this->_lengowOrder->getData('customer_email'),
            ];
            $fields[] = [
                'label' => __('Carrier from marketplace'),
                'value' => $this->_lengowOrder->getData('carrier'),
            ];
            $fields[] = [
                'label' => __('Shipping method from marketplace'),
                'value' => $this->_lengowOrder->getData('carrier_method'),
            ];
            $fields[] = [
                'label' => __('Tracking number'),
                'value' => $this->_lengowOrder->getData('carrier_tracking'),
            ];
            $fields[] = [
                'label' => __('ID relay'),
                'value' => $this->_lengowOrder->getData('carrier_id_relay'),
            ];
            $fields[] = [
                'label' => __('Express delivery'),
                'value' => $this->_lengowOrder->isExpress() ? __('Yes') : __('No'),
            ];
            $fields[] = [
                'label' => __('Shipped by Marketplace'),
                'value' => $this->_lengowOrder->isDeliveredByMarketplace() ? __('Yes') : __('No'),
            ];
            $fields[] = [
                'label' => __('Business'),
                'value' => $this->_lengowOrder->isBusiness() ? __('Yes') : __('No'),
            ];
            $fields[] = [
                'label' => __('Message'),
                'value' => $this->_lengowOrder->getData('message'),
            ];
            $fields[] = [
                'label' => ('Vat number'),
                'value' => $this->_lengowOrder->getData('customer_vat_number'),
            ];
            $fields[] = [
                'label' => __('Imported at'),
                'value' => $this->_dataHelper->getDateInCorrectFormat(
                    strtotime($this->_lengowOrder->getData('created_at'))
                ),
            ];
            $fields[] = [
                'label' => __('JSON format'),
                'value' => '<textarea disabled="disabled">' . $this->_lengowOrder->getData('extra') . '</textarea>',
            ];
        }
        return $fields;
    }
}
