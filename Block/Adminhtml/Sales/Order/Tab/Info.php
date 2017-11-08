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
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;

class Info extends Template implements TabInterface
{
    /**
     * @var string Lengow template path
     */
    protected $_template = 'sales/order/tab/info.phtml';

    /**
     * @var \Magento\Framework\Registry Magento Registry instance
     */
    protected $_coreRegistry;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Model\Import\OrderFactory Lengow order factory instance
     */
    protected $_lengowOrderFactory;

    /**
     * Construct
     *
     * @param \Magento\Backend\Block\Template\Context $context Magento Context instance
     * @param \Magento\Framework\Registry $coreRegistry Magento Registry instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Model\Import\OrderFactory $lengowOrderFactory Lengow order instance
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        LengowOrderFactory $lengowOrderFactory,
        array $data = []
    )
    {
        $this->_coreRegistry = $coreRegistry;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_lengowOrderFactory = $lengowOrderFactory;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
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
     * Get Lengow order by Magento order id
     *
     * @return \Lengow\Connector\Model\Import\Order|false
     */
    public function getLengowOrder()
    {
        $order = $this->getOrder();
        $lengowOrderId = $this->_lengowOrderFactory->create()->getLengowOrderIdByOrderId($order->getId());
        if ($lengowOrderId) {
            return $this->_lengowOrderFactory->create()->load($lengowOrderId);
        }
        return false;
    }

    /**
     * Check if is a order imported by Lengow
     *
     * @return boolean
     */
    public function isOrderImportedByLengow()
    {
        return (bool)$this->getOrder()->getData('from_lengow');
    }

    /**
     * Check if is a order with a Lengow order record
     *
     * @return boolean
     */
    public function isOrderFollowedByLengow()
    {
        $lengowOrder = $this->getLengowOrder();
        return $lengowOrder ? true : false;
    }

    /**
     * Get all Lengow order data
     *
     * @return array
     */
    public function getFields()
    {
        $fields = [];
        $lengowOrder = $this->getLengowOrder();
        if ($lengowOrder) {
            $fields[] = ['label' => __('Marketplace SKU'), 'value' => $lengowOrder->getData('marketplace_sku')];
            $fields[] = ['label' => __('Marketplace'), 'value' => $lengowOrder->getData('marketplace_label')];
            $fields[] = ['label' => __('Delivery Address ID'), 'value' => $lengowOrder->getData('delivery_address_id')];
            $fields[] = ['label' => __('Currency'), 'value' => $lengowOrder->getData('currency')];
            $fields[] = ['label' => __('Total Paid'), 'value' => $lengowOrder->getData('total_paid')];
            $fields[] = ['label' => __('Commission'), 'value' => $lengowOrder->getData('commission')];
            $fields[] = ['label' => __('Customer name'), 'value' => $lengowOrder->getData('customer_name')];
            $fields[] = ['label' => __('Customer email'), 'value' => $lengowOrder->getData('customer_email')];
            $fields[] = ['label' => __('Carrier from marketplace'), 'value' => $lengowOrder->getData('carrier')];
            $fields[] = [
                'label' => __('Shipping method from marketplace'),
                'value' => $lengowOrder->getData('carrier_method')
            ];
            $fields[] = ['label' => __('Tracking number'), 'value' => $lengowOrder->getData('carrier_tracking')];
            $fields[] = ['label' => __('ID relay'), 'value' => $lengowOrder->getData('carrier_id_relay')];
            $fields[] = [
                'label' => __('Shipped by marketplace'),
                'value' => $lengowOrder->getData('sent_marketplace') == 1 ? __('Yes') : __('No')
            ];
            $fields[] = ['label' => __('Message'), 'value' => $lengowOrder->getData('message')];
            $fields[] = [
                'label' => __('Imported at'),
                'value' => $this->_dataHelper->getDateInCorrectFormat(strtotime($lengowOrder->getData('created_at')))
            ];
            $fields[] = [
                'label' => __('JSON format'),
                'value' => '<textarea disabled="disabled">' . $lengowOrder->getData('extra') . '</textarea>'
            ];
        }
        return $fields;
    }
}
