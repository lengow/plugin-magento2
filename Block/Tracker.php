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

namespace Lengow\Connector\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;

class Tracker extends Template
{
    /**
     * @var \Magento\Checkout\Model\Session Magento checkout session instance
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory Magento order factory instance
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Catalog\Model\ProductRepository Magento product repository instance
     */
    protected $_productRepository;

    /**
     * @var \Magento\Framework\Json\Helper\Data Magento json helper instance
     */
    protected $_jsonHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context Magento block context instance
     * @param \Magento\Checkout\Model\Session $checkoutSession Magento checkout session instance
     * @param \Magento\Sales\Model\OrderFactory $orderFactory Magento order factory instance
     * @param \Magento\Catalog\Model\ProductRepository $productRepository Magento product repository instance
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper Magento json helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        ProductRepository $productRepository,
        JsonHelper $jsonHelper,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_productRepository = $productRepository;
        $this->_jsonHelper = $jsonHelper;
        $this->_configHelper = $configHelper;
    }

    /**
     * Get last order
     *
     * @return \Magento\Sales\Model\Order|false
     */
    public function getLastOrder()
    {
        if ($this->_checkoutSession->getLastRealOrderId()) {
            $order = $this->_orderFactory->create()->loadByIncrementId($this->_checkoutSession->getLastRealOrderId());
            return $order;
        }
        return false;
    }

    /**
     * Return list of order's items id
     *
     * @param \Magento\Sales\Model\Order $order Magento order instance
     *
     * @return string
     */
    public function getProductIds($order)
    {
        $orderItems = $order->getAllVisibleItems();
        $productsCart = [];
        foreach ($orderItems as $item) {
            if ($item->hasProduct()) {
                $product = $item->getProduct();
            } else {
                try {
                    $product = $this->_productRepository->getById($item->getProductId());
                } catch (\Exception $e) {
                    continue;
                }
            }
            $quantity = (int)$item->getQtyOrdered();
            $price = round((float)$item->getRowTotalInclTax() / $quantity, 2);
            $identifier = $this->_configHelper->get('tracking_id');
            $productDatas = [
                'product_id' => $product->getData($identifier),
                'price' => $price,
                'quantity' => $quantity,
            ];
            $productsCart[] = $productDatas;
        }
        return $this->_jsonHelper->jsonEncode($productsCart);
    }

    /**
     * Prepare and return block's html output
     *
     * @return string
     */
    protected function _prepareLayout()
    {
        if ((bool)$this->_configHelper->get('tracking_enable') && $this->getRequest()->getActionName() === 'success') {
            $order = $this->getLastOrder();
            if ($order) {
                $this->setData('account_id', $this->_configHelper->get('account_id'));
                $this->setData('order_ref', $order->getId());
                $this->setData('amount', $order->getGrandTotal());
                $this->setData('currency', $order->getOrderCurrencyCode());
                $this->setData('payment_method', $order->getPayment()->getMethod());
                $this->setData('cart', htmlspecialchars($this->getProductIds($order)));
                $this->setData('cart_number', $order->getQuoteId());
                $this->setData('newbiz', 1);
                $this->setData('valid', 1);
            }
        }
        return parent::_prepareLayout();
    }

    /**
     * Render Lengow tracking scripts
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!(bool)$this->_configHelper->get('tracking_enable') || $this->getRequest()->getActionName() !== 'success') {
            return '';
        }
        return parent::_toHtml();
    }
}
