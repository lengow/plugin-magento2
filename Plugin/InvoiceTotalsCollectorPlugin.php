<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Lengow\Connector\Plugin;

use Lengow\Connector\Helper\Config as ConfigHelper;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Sales\Model\Order\Invoice;

/**
 * Description of InvoiceTotalsCollectorPlugin
 *
 */
class InvoiceTotalsCollectorPlugin
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
     * InvoiceTotalsCollectorPlugin constructor
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
     * @param Invoice $subject
     * @param Invoice $result
     * @return Invoice
     */
    public function afterCollectTotals(Invoice $subject, Invoice $result)
    {
        $storeId = $subject->getOrder()->getStoreId();
        if (! $this->mustCkeck($storeId)) {
            return $result;
        }

        $lengowOrderData = $this->backendSession->getCurrentOrderLengowData();

        //not fix rounding if total_order, total_tax or shipping is null
        if (is_null($lengowOrderData->total_order) || is_null($lengowOrderData->total_tax) || is_null($lengowOrderData->shipping)) {
            return $result;
        }
        $totalLengow = (float) $lengowOrderData->total_order;
        $taxLengow = (float) $lengowOrderData->total_tax;
        $shippingLengow = (float) $lengowOrderData->shipping;
        $subtotalLengow = $totalLengow - $taxLengow - $shippingLengow;
        $subtotalInclTaxLengow = $totalLengow - $shippingLengow;

        foreach ($result->getData() as $type => $amount) {
            if ($amount === 0) {
                continue;
            }
            if (($type === 'subtotal'
                    || $type === 'base_subtotal'
                    || $type === 'base_subtotal_with_discount'
                    || $type === 'subtotal_with_discount')
                    && $amount !== $subtotalLengow) {
                $result->setData($type, $subtotalLengow);
            }
            if (($type === 'grand_total' || $type === 'base_grand_total')
                    && $amount !== $totalLengow) {
                $result->setData($type, $totalLengow);
            }
            if (($type === 'subtotal_incl_tax'
                    || $type === 'base_subtotal_incl_tax'
                    || $type === 'base_subtotal_total_incl_tax')
                    && $amount !== $subtotalInclTaxLengow) {
                $result->setData($type, $subtotalInclTaxLengow);
            }
        }

        return $result;
    }

    /**
     * check if we must check rounding
     */
    private function mustCkeck(int $storeId): bool
    {
        $isActive = (bool) $this->configHelper->get(ConfigHelper::CHECK_ROUNDING_ENABLED, $storeId);
        $hasBundle = $this->backendSession->getHasBundleItems();
        if (!$this->backendSession->getIsFromlengow()) {
            return false;
        }
        if (! $this->backendSession->getCurrentOrderLengowData()) {
            return false;
        }
        if (!$isActive && !$hasBundle) {
            return false;
        }

        return true;
    }
}
