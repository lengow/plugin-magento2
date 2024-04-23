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

/**
 * QuoteTotalsCollectorPlugin
 */
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
     *
     * QuoteTotalsCollectorPlugin constructor
     */
    public function __construct(
        ConfigHelper $configHelper,
        BackendSession $backendSession
    ) {
        $this->configHelper = $configHelper;
        $this->backendSession = $backendSession;
    }

    /**
     * aroundMethod plugn execution
     */
    public function aroundCollect(TotalsCollector $subject, Callable $collect, Quote $quote)
    {

        if (! (bool)$this->backendSession->getIsFromlengow()) {
            return $collect($quote);
        }

        if (! (bool) $this->configHelper->get(ConfigHelper::CHECK_ROUNDING_ENABLED, $quote->getStore()->getId())) {
            return $collect($quote);
        }

        if (! $this->backendSession->getCurrentOrderLengowData()) {
            return $collect($quote);
        }
        $lengowOrderData = $this->backendSession->getCurrentOrderLengowData();
        $result = $collect($quote);

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

            if (($type === 'grand_total' || $type==='base_grand_total') && $amount !==$totalLengow) {
                $result->setData($type, $totalLengow);
            }
        }

        return $result;
    }

}
