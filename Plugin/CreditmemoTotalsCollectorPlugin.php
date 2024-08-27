<?php
/**
 * Copyright 2020 Lengow SAS
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
 * @copyright   2020 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Lengow\Connector\Plugin;

use Magento\Sales\Model\Order\Creditmemo;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Magento\Backend\Model\Session as BackendSession;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Sales\Model\Order;

class CreditmemoTotalsCollectorPlugin
{
     /**
      *
      * @var ConfigHelper $configHelper
      */
    protected $configHelper;

    /**
     *
     * @var BackendSession $backendSession
     */
    protected $backendSession;

    /**
     *
     * @var LengowOrderFactory $lengowOrderFactory
     */
    protected $lengowOrderFactory;

    /**
     *
     * @var RequestHttp $request
     */
    protected $request;

    /**
     *
     * CreditmemoTotalsCollectorPlugin constructor
     */
    public function __construct(
        ConfigHelper $configHelper,
        BackendSession $backendSession,
        LengowOrderFactory $lengowOrderFactory,
        RequestHttp $request
    ) {
        $this->configHelper = $configHelper;
        $this->backendSession = $backendSession;
        $this->lengowOrderFactory = $lengowOrderFactory;
        $this->request = $request;
    }

    /**
     * afterCollectTotals plugin method
     *
     * @param Creditmemo $subject
     * @param Creditmemo $result
     */
    public function afterCollectTotals(Creditmemo $subject, Creditmemo $result): Creditmemo
    {

        /** @var  \Magento\Sales\Model\Order $order */
        $order = $result->getOrder();

        if (is_null($order)) {
            return $result;
        }

        if (! (bool)$order->getFromLengow()) {
            return $result;
        }

        if (! (bool) $this->configHelper->get(
            ConfigHelper::CHECK_ROUNDING_ENABLED,
            $order->getStore()->getId()
        )) {
            return $result;
        }

        if ($this->hasPostDifferentItemsQty($order)) {
            return $result;
        }

        $lengowOrderId = $this->lengowOrderFactory->create()
            ->getLengowOrderIdByOrderId($order->getId());
        $lengowOrder = $this->lengowOrderFactory->create()->load($lengowOrderId);
        $lengowOrderData = json_decode(
            $lengowOrder->getData(LengowOrder::FIELD_EXTRA)
        );

        //not fix rounding if total_order, total_tax or shipping is null
        if (is_null($lengowOrderData->total_order) || is_null($lengowOrderData->total_tax) || is_null($lengowOrderData->shipping)) {
            return $result;
        }

        $shippingLengow = (float) $lengowOrderData->shipping;
        if ($this->hasPostAdjustedAmounts($shippingLengow)) {
            return $result;
        }

        $totalLengow = (float) $lengowOrderData->total_order;
        $taxLengow = (float) $lengowOrderData->total_tax;
        $subtotalLengow = $totalLengow - $taxLengow - $shippingLengow;
        foreach ($result->getData() as $type => $amount) {

            if ($amount === 0) {
                continue;
            }
            if ($type === 'subtotal'
                    || $type === 'base_subtotal'
                    || $type === 'base_subtotal_with_discount'
                    || $type === 'subtotal_with_discount') {

                if ($subtotalLengow !== $amount) {
                    $result->setData($type, $subtotalLengow);
                }
            }

            if (($type === 'grand_total'
                    || $type==='base_grand_total')
                    && $amount !==$totalLengow) {

                $result->setData($type, $totalLengow);
            }
        }

        return $result;
    }

    /**
     * check if hasPostDifferentItemsQty
     *
     * @param Order $order
     *
     * @return bool
     */
    protected function hasPostDifferentItemsQty(Order $order): bool
    {
        $items = $this->request->getParam('creditmemo')['items'] ?? [];
        $itemsOrdered = $order->getAllVisibleItems();
        foreach ($itemsOrdered as $item) {
            $itemId = (int) $item->getId();
            $qtyOrdered = (int) $item->getQtyOrdered();
            if (isset($items[$itemId])) {
                $qtyRefund = (int) $items[$itemId]['qty'];
                if ($qtyRefund !== $qtyOrdered) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * check if hasPostAdjustedAmounts
     *
     * @param float $shippingLengow
     *
     * @return bool
     */
    protected function hasPostAdjustedAmounts(float $shippingLengow) : bool
    {
        if (is_null($this->request->getParam('creditmemo'))) {
            return false;
        }
        $creditMemo = $this->request->getParam('creditmemo');
        $shippingRefund = (float) $creditMemo['shipping_amount'];
        if ($shippingRefund !== $shippingLengow) {
            return true;
        }
        $adjustementPos = (float) $creditMemo['adjustment_positive'];
        if ($adjustementPos > 0) {
            return true;
        }
        $adjustementNeg = (float) $creditMemo['adjustment_negative'];
        if ($adjustementNeg > 0) {
            return true;
        }

        return false;
    }
}
