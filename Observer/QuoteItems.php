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

use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Lengow\Connector\Helper\Config as ConfigHelper;

/**
 * QuoteItems
 */
class QuoteItems implements ObserverInterface
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
     * QuoteItems constructor
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
     * @param Observer $observer
     * @return type
     */
    public function execute(Observer $observer)
    {

        if (!(bool)$this->backendSession->getIsFromlengow()) {
            return;
        }

        $quote = $observer->getEvent()->getQuote();
        $products = $this->backendSession->getCurrentOrderLengowProducts();
        $storeId = $quote->getStore()->getId();

        if (!(bool) $this->configHelper->get(ConfigHelper::CHECK_ROUNDING_ENABLED, $storeId)) {
            return ;
        }

        if (!$products) {
            return;
        }

        foreach ($quote->getAllVisibleItems() as $item) {

            if (!isset($products[$item->getProductId()])) {
                continue;
            }

            $product = $products[$item->getProductId()];

            if (!$item->getTaxAmount() || !$product['tax_amount']) {
                continue;
            }

            if ($product['tax_amount'] === (float) $item->getTaxAmount()
                && $product['amount'] === $item->getRowTotalInclTax()
            ) {
                continue;
            }
            $originalPrice = $product['price_unit'] - $product['tax_unit'];
            $rowTotalInclTax = $product['amount'];
            $rowTotalExclTax = $product['amount'] - $product['tax_amount'];
            $taxAmount = $product['tax_amount'];
            $unitPriceInclTax = $product['price_unit'];

            $item->setTaxAmount($taxAmount);
            $item->setBaseTaxAmount($taxAmount);
            $item->setBaseRowTotal($rowTotalExclTax);
            $item->setRowTotal($rowTotalExclTax);
            $item->setRowTotalInclTax($rowTotalInclTax);
            $item->setBaseRowTotalInclTax($rowTotalInclTax);
            $item->setPrice($originalPrice);
            $item->setPriceInclTax($unitPriceInclTax);
            $item->setBasePriceInclTax($unitPriceInclTax);
            $item->setCustomPrice($originalPrice);
            $item->setOriginalCustomPrice($originalPrice);
            $item->setBasePrice($originalPrice);
            $item->setOriginalPrice($originalPrice);
            $item->setBaseOriginalPrice($originalPrice);

        }
    }
}
