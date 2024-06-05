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
namespace Lengow\Connector\Observer;

use Lengow\Connector\Helper\Config as ConfigHelper;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * AddressTotals
 */
class AddressTotals implements ObserverInterface
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
     * AddressTotals constructor
     */
    public function __construct(
        ConfigHelper $configHelper,
        BackendSession $backendSession
    ) {
        $this->configHelper = $configHelper;
        $this->backendSession = $backendSession;
    }

    /**
     *
     * Observer execution method
     */
    public function execute(Observer $observer)
    {

        if (!(bool)$this->backendSession->getIsFromlengow()) {
            return;
        }

        $quote = $observer->getEvent()->getQuote();
        $total = $observer->getEvent()->getTotal();
        $lengowOrderData = $this->backendSession->getCurrentOrderLengowData();
        $storeId =  $quote->getStore()->getId();

        if (!(bool) $this->configHelper->get(ConfigHelper::CHECK_ROUNDING_ENABLED, $storeId)) {
            return ;
        }

        if (!$lengowOrderData) {
            return;
        }

        $totalLengow = (float) $lengowOrderData->total_order;
        $taxLengow = (float) $lengowOrderData->total_tax;
        $shippingLengow = (float) $lengowOrderData->shipping;
        $subtotalLengow = $totalLengow - $taxLengow - $shippingLengow;
        $subtotalInclTaxLengow = $totalLengow - $shippingLengow;

        foreach ($total->getData() as $type => $amount) {
            if ($amount === 0) {
                continue;
            }
            if (($type === 'subtotal'
                    || $type === 'base_subtotal'
                    || $type === 'base_subtotal_with_discount'
                    || $type === 'subtotal_with_discount')
                    && $amount !== $subtotalLengow) {
                $total->setData($type, $subtotalLengow);
            }
            if (($type === 'grand_total' || $type === 'base_grand_total')
                    && $amount !== $totalLengow) {
                $total->setData($type, $totalLengow);
            }
            if (($type === 'subtotal_incl_tax'
                    || $type === 'base_subtotal_incl_tax'
                    || $type === 'base_subtotal_total_incl_tax')
                    && $amount !== $subtotalInclTaxLengow) {
                $total->setData($type, $subtotalInclTaxLengow);
            }
            if (($type === 'tax_amount'
                    || $type === 'base_tax_amount')
                    && $amount !== $taxLengow) {
                $total->setData($type, $taxLengow);
            }
            if (($type === 'shipping_incl_tax'
                    || $type === 'base_shipping_incl_tax')
                    && $amount !== $shippingLengow) {
                $total->setData($type, $shippingLengow);
            }
            if ($type === 'shipping_amount'
                    || $type=== 'base_shipping_amount'
                    || $type==='shipping_tax_calculation_amount'
                    || $type==='base_shipping_tax_calculation_amount') {
                $shippingTaxAmount = $total->getData('shipping_tax_amount');
                $shippingLengowExclTax = $shippingLengow - $shippingTaxAmount;
                if ($shippingLengowExclTax !== $amount) {
                    $total->setData($type, $shippingLengowExclTax);
                }

            }
        }
        $observer->getEvent()->setTotal($total);
    }
}
