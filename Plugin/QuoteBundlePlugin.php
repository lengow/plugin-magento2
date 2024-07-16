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


use Lengow\Connector\Helper\Config as ConfigHelper;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Quote\Model\Quote\Item;

/**
 * QuoteBundlePlugin
 */
class QuoteBundlePlugin
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
     * afterBeforeSave  plugin execution method
     */
    public function afterBeforeSave(Item $subject)
    {


        if (! (bool)$this->backendSession->getIsFromlengow()) {
            return $subject;
        }

        if (! $this->backendSession->getBundleItems()) {
            return $subject;
        }

        $bundleQuoteItems = $this->backendSession->getBundleItems();
        $productId = $subject->getProductId();

        if (!isset($bundleQuoteItems[$productId])) {
            return $subject;
        }

        $sessionPrice = (float) $bundleQuoteItems[$productId]['price'];
        $sessionQty = ((int) $bundleQuoteItems[$productId]['qty'] > 0) ? (int) $bundleQuoteItems[$productId]['qty'] : 1;
        $taxPercent = (float) $subject->getTaxPercent();
        $sessionPriceUnit = $sessionPrice / $sessionQty;
        $originalPrice = round(($sessionPriceUnit / (100 + $taxPercent))   * 100, 3);
        $taxAmount = round($sessionPrice - ($originalPrice * $sessionQty), 3);
        $subject->setPriceInclTax($sessionPrice);
        $subject->setBasePriceInclTax($sessionPrice);
        $subject->setCustomPriceInclTax($sessionPrice);
        $subject->setOriginalCustomPrice($originalPrice);
        $subject->setOriginalPrice($originalPrice);
        $subject->setBaseOriginalPrice($originalPrice);
        $subject->setPrice($originalPrice);
        $subject->setBasePrice($originalPrice);
        $subject->setCustomPrice($originalPrice);
        $subject->setTaxAmount($taxAmount);
        $subject->setBaseTaxAmount($taxAmount);
        $subject->setBaseRowTotal($originalPrice * $sessionQty);
        $subject->setRowTotal($originalPrice * $sessionQty);
        $subject->setRowTotalInclTax($sessionPrice * $sessionQty);
        $subject->setBaseRowTotalInclTax($sessionPrice * $sessionQty);
        $subject->setCustomRowTotalIncTax($sessionPrice * $sessionQty);
        unset($bundleQuoteItems[$productId]);
        $this->backendSession->setBundleItems($bundleQuoteItems);

        return $subject;
    }

}
