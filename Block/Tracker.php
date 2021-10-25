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

use Exception;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Session;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Sales\Model\OrderFactory as MagentoOrderFactory;
use Lengow\Connector\Helper\Config as ConfigHelper;

class Tracker extends Template
{
    /**
     * @var ProductRepository Magento product repository instance
     */
    private $productRepository;

    /**
     * @var Session Magento checkout session instance
     */
    private $checkoutSession;

    /**
     * @var JsonHelper Magento json helper instance
     */
    private $jsonHelper;

    /**
     * @var MagentoOrderFactory Magento order factory instance
     */
    private $orderFactory;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * Constructor
     *
     * @param Context $context Magento block context instance
     * @param Session $checkoutSession Magento checkout session instance
     * @param MagentoOrderFactory $orderFactory Magento order factory instance
     * @param ProductRepository $productRepository Magento product repository instance
     * @param JsonHelper $jsonHelper Magento json helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        MagentoOrderFactory $orderFactory,
        ProductRepository $productRepository,
        JsonHelper $jsonHelper,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->productRepository = $productRepository;
        $this->jsonHelper = $jsonHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * Get last order
     *
     * @return MagentoOrder|false
     */
    public function getLastOrder()
    {
        if ($this->checkoutSession->getLastRealOrderId()) {
            return $this->orderFactory->create()->loadByIncrementId($this->checkoutSession->getLastRealOrderId());
        }
        return false;
    }

    /**
     * Return list of order's items id
     *
     * @param MagentoOrder $order Magento order instance
     *
     * @return string
     */
    private function getProductIds(MagentoOrder $order): string
    {
        $orderItems = $order->getAllVisibleItems();
        $productsCart = [];
        foreach ($orderItems as $item) {
            if ($item->hasProduct()) {
                $product = $item->getProduct();
            } else {
                try {
                    $product = $this->productRepository->getById($item->getProductId());
                } catch (Exception $e) {
                    continue;
                }
            }
            $quantity = (int) $item->getQtyOrdered();
            $price = round((float) $item->getRowTotalInclTax() / $quantity, 2);
            $identifier = $this->configHelper->get(ConfigHelper::TRACKING_ID);
            $productsCart[] = [
                'product_id' => $product->getData($identifier),
                'price' => $price,
                'quantity' => $quantity,
            ];
        }
        return $this->jsonHelper->jsonEncode($productsCart);
    }

    /**
     * Prepare and return block's html output
     *
     * @return Tracker
     */
    protected function _prepareLayout(): Tracker
    {
        if ($this->configHelper->get(ConfigHelper::TRACKING_ENABLED)
            && $this->getRequest()->getActionName() === 'success'
        ) {
            $order = $this->getLastOrder();
            if ($order) {
                $this->setData('account_id', $this->configHelper->get(ConfigHelper::ACCOUNT_ID));
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
    protected function _toHtml(): string
    {
        if (!(bool) $this->configHelper->get(ConfigHelper::TRACKING_ENABLED)
            || $this->getRequest()->getActionName() !== 'success'
        ) {
            return '';
        }
        return parent::_toHtml();
    }
}
