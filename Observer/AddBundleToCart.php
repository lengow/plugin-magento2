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
class AddBundleToCart implements ObserverInterface
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

        $quoteItems = $observer->getEvent()->getItems();


        list($bundleProductChildrenIds, $bundlePrices) = $this->processBundleData($quoteItems);
        list($bundleChildrenTotal, $bundleChildrenCount) = $this->processChildrenData(
            $quoteItems,
            $bundleProductChildrenIds
        );

        $bundlePricesDelta = $this->processDeltaPrices(
            $bundleChildrenTotal,
            $bundlePrices,
            $bundleChildrenCount
        );
        if (count($bundlePricesDelta) === 0) {
            return;
        }
        $this->backendSession->setBundleItems($this->ventilateDeltaPrices($quoteItems, $bundlePricesDelta));

    }

    /**
     * Process children data
     */
    protected function processChildrenData(
        array $quoteItems,
        array $bundleProductChildrenIds,
    ) : array
    {
        $bundleChildrenTotal = [];
        $bundleChildrenCount = [];
        foreach ($quoteItems as $quoteItem) {
            $productType = $quoteItem->getProductType();
            $productId = $quoteItem->getProductId();
            if ($productType === 'simple') {
                if (isset($bundleProductChildrenIds[$productId])) {
                    $simplePrice = $quoteItem->getPrice();
                    $parentId = $bundleProductChildrenIds[$productId]['parent_id'];
                    if (!isset($bundleChildrenTotal[$parentId])) {
                        $bundleChildrenTotal[$parentId] = $simplePrice;

                    } else {
                        $bundleChildrenTotal[$parentId] += $simplePrice;
                    }

                    $bundleChildrenCount[$parentId][] = $productId;
                }
            }
        }

        return [$bundleChildrenTotal, $bundleChildrenCount];

    }


    /**
     * Process bundle data
     */
    protected function processBundleData(array $quoteItems) : array
    {
        $bundleProductChildrenIds = [];
        $bundlePrices = [];
        foreach ($quoteItems as $quoteItem) {
            $productType = $quoteItem->getProductType();
            if ($productType === 'bundle') {
                $bundleProductId = $quoteItem->getProductId();
                $bundleQtyOptions = $quoteItem->getQtyOptions();
                $bundleChildrenCount[$bundleProductId] = count($bundleQtyOptions);
                $bundlePrice = $quoteItem->getProduct()->getPrice();
                foreach ($bundleQtyOptions as $bundleOption) {
                    $childrenId = $bundleOption['product_id'];
                    $bundleProductChildrenIds[$childrenId] = [
                        'parent_id' => $bundleProductId,
                        'qty' => $bundleOption['value'],
                        'parent_price' => $bundlePrice,
                        'children_id' => $childrenId
                    ];
                    $bundlePrices[$bundleProductId] = $bundlePrice;

                }

            }
        }
        return [$bundleProductChildrenIds, $bundlePrices];

    }

    /**
     * Process delta prices
     */
    protected function processDeltaPrices(
        array $bundleChildrenTotal,
        array $bundlePrices,
        array $bundleChildrenCount
    ): array
    {
        $bundlePricesDelta = [];
        foreach ($bundlePrices as $bundleId => $price) {
            if (!isset($bundleChildrenTotal[$bundleId])) {
                continue;
            }
            if ($price === $bundleChildrenTotal[$bundleId]) {
                continue;
            }
            if (count($bundleChildrenCount[$bundleId]) === 0) {
                continue;
            }
            $priceDiff = $price - $bundleChildrenTotal[$bundleId];
            $ratePriceDiff = 1  - ($bundleChildrenTotal[$bundleId] / $price);

            $bundlePricesDelta[$bundleId] = [
                'price_diff' => $priceDiff,
                'rate_diff' => round($ratePriceDiff, 3),
                'children_ids' => implode(',', $bundleChildrenCount[$bundleId]),
                'bundle_id' => $bundleId,
                'bundle_price' => $price
            ];
        }

        return $bundlePricesDelta;

    }

    /**
     * Dispatches prices diferences to quote items
     */
    protected function ventilateDeltaPrices(array $quoteItems, array $deltaPrices): array
    {

        $bundleItems = [];
        $productCount = 0;
        foreach ($quoteItems as $index => $quoteItem) {
            $productType = $quoteItem->getProductType();
            $productId = $quoteItem->getProductId();

            if (!isset($bundleItems[$productId])) {
                $bundleItems[$productId] = [];
            }
            if ($productType === 'bundle') {
                if (isset($deltaPrices[$productId])) {
                    $quoteItem->setPrice($deltaPrices[$productId]['bundle_price']);
                    $bundleItems[$productId]['price'] = $quoteItem->getPrice();
                    $bundleItems[$productId]['qty'] = $quoteItem->getQty();
                    $diff = $deltaPrices[$productId]['price_diff'] ?? 0;
                    $rateDiff = $deltaPrices[$productId]['rate_diff'] ?? 0;
                }
            }
            if ($productType === 'simple') {
                foreach ($deltaPrices as $bundleId => $deltaPrice) {
                    if ($diff === 0 || $rateDiff === 0) {
                        continue;
                    }
                    $childrenIds = explode(',', $deltaPrice['children_ids']);


                    if (in_array($productId, $childrenIds)) {
                        $productCount++;
                        $deltaPrice['delta_line'] = round($deltaPrice['rate_diff'] * $quoteItem->getPrice(), 3);
                        $quoteItem->setPrice($quoteItem->getPrice() + $deltaPrice['delta_line']);
                        $diff -= $deltaPrice['delta_line'];
                        if ($productCount === count($childrenIds) && abs($diff) > 0) {
                            $quoteItem->setPrice($quoteItem->getPrice() + $diff);
                            $diff = 0;
                        }
                        $bundleItems[$productId]['price'] = $quoteItem->getPrice();
                        $bundleItems[$productId]['qty'] = $quoteItem->getQty();
                        $bundleItems[$productId]['tax_percent'] = $quoteItem->getTaxPercent();
                    }
                }
            }
        }


        return $bundleItems;
    }
}

