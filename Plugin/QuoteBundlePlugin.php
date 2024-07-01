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
     * aroundMethod plugn execution
     */
    public function beforeAfterSave(Item $subject)
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
        $sessionPrice = $bundleQuoteItems[$productId]['price'];
        $sessionQty = $bundleQuoteItems[$productId]['qty'];

        $subject->setPriceInclTax($sessionPrice);
        $subject->setBasePriceInclTax($sessionPrice);
        $subject->setCustomPrice($sessionPrice);
        $subject->setOriginalCustomPrice($sessionPrice);
        $subject->setOriginalPrice($sessionPrice);
        $subject->setRowTotal($sessionPrice * $sessionQty);
        $subject->setRowTotalInclTax($sessionPrice * $sessionQty);
        $subject->setCustomRowTotal($sessionPrice * $sessionQty);
        unset($bundleQuoteItems[$productId]);
        $this->backendSession->setBundleItems($bundleQuoteItems);
        $subject->save();

        return $subject;
    }

}
