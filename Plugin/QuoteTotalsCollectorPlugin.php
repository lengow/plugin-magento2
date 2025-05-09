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
 * @subpackage  Observer
 * @author      Team module <team-module@lengow.com>
 * @copyright   2020 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Lengow\Connector\Plugin;

use Magento\Quote\Model\Quote\TotalsCollector;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Quote\Model\Quote;

class QuoteTotalsCollectorPlugin
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
     * Plugin constructor
     */
    public function __construct(
        ConfigHelper $configHelper,
        BackendSession $backendSession
    ) {
        $this->configHelper = $configHelper;
        $this->backendSession = $backendSession;
    }

    /**
     * aroundMethod plugin execution
     */
    public function aroundCollect(TotalsCollector $subject, callable $collect, Quote $quote)
    {
        $storeId = $quote->getStore()->getId();
        $result = $collect($quote);
        if (!$this->mustCkeck($storeId)) {
            return $result;
        }
        $lengowOrderData = $this->backendSession->getCurrentOrderLengowData();
        $result = $collect($quote);

        //not fix rounding if total_order, total_tax or shipping is null
        if (is_null($lengowOrderData->total_order) || is_null($lengowOrderData->total_tax) || is_null($lengowOrderData->shipping)) {
            return $result;
        }
        $totalLengow = (float) $lengowOrderData->total_order;
        $taxLengow = (float) $lengowOrderData->total_tax;
        $shippingLengow = (float) $lengowOrderData->shipping;
        $subtotalLengow = $totalLengow - $taxLengow - $shippingLengow;

        foreach ($result->getData() as $type => $amount) {

            if ($type === 'subtotal'
                    || $type === 'base_subtotal'
                    || $type === 'base_subtotal_with_discount'
                    || $type === 'subtotal_with_discount') {

                if ($subtotalLengow !== $amount) {
                    $result->setData($type, $subtotalLengow);
                }
            }

            if (($type === 'grand_total' || $type==='base_grand_total')
                    && $amount !==$totalLengow) {
                $result->setData($type, $totalLengow);
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
