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
 * @subpackage  Model
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Model\Export;

use Magento\Catalog\Model\Product\Interceptor as ProductInterceptor;
use Magento\Store\Model\Store\Interceptor as StoreInterceptor;
use Magento\CatalogRule\Model\Rule as CatalogueRule;
use Magento\Framework\Pricing\PriceCurrencyInterface as PriceCurrency;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Lengow\Connector\Helper\Data as DataHelper;

/**
 * Lengow export price
 */
class Price
{
    /**
     * @var ProductInterceptor Magento product instance
     */
    private $product;

    /**
     * @var StoreInterceptor Magento store instance
     */
    private $store;

    /**
     * @var CatalogueRule Magento catalogue rule instance
     */
    private $catalogueRule;

    /**
     * @var PriceCurrency Magento price currency instance
     */
    private $priceCurrency;

    /**
     * @var DateTime Magento datetime instance
     */
    private $dateTime;

    /**
     * @var TimezoneInterface Magento datetime timezone instance
     */
    private $timezone;

    /**
     * @var string currency code for conversion
     */
    private $currency;

    /**
     * @var string original store currency
     */
    private $storeCurrency;

    /**
     * @var float Product price exclude tax
     */
    private $priceExclTax;

    /**
     * @var float Product price include tax
     */
    private $priceInclTax;

    /**
     * @var float Product price before discount exclude tax
     */
    private $priceBeforeDiscountExclTax;

    /**
     * @var float Product price before discount include tax
     */
    private $priceBeforeDiscountInclTax;

    /**
     * @var float discount amount
     */
    private $discountAmount;

    /**
     * @var float discount percent
     */
    private $discountPercent;

    /**
     * @var string discount start date
     */
    private $discountStartDate;

    /**
     * @var string discount end date
     */
    private $discountEndDate;

    /**
     * Constructor
     *
     * @param PriceCurrency $priceCurrency Magento price currency instance
     * @param CatalogueRule $catalogueRule Magento catalogue rule instance
     * @param DateTime $dateTime Magento datetime instance
     * @param TimezoneInterface $timezone Magento datetime timezone instance
     */
    public function __construct(
        PriceCurrency $priceCurrency,
        CatalogueRule $catalogueRule,
        DateTime $dateTime,
        TimezoneInterface $timezone
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->catalogueRule = $catalogueRule;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
    }

    /**
     * Init a new price
     *
     * @param array $params optional options for load a specific product
     * StoreInterceptor store    Magento store instance
     * string           currency Currency iso code for conversion
     */
    public function init(array $params): void
    {
        $this->currency = $params['currency'];
        $this->store = $params['store'];
        $this->storeCurrency = $this->store->getCurrentCurrencyCode();
    }

    /**
     * Load a new price with a specific params
     *
     * @param array $params optional options for load a specific price
     * ProductInterceptor product Magento product instance
     */
    public function load(array $params): void
    {
        $this->product = $params['product'];
        // get product prices
        $productPrices = $this->getAllPrices();
        $this->priceExclTax = $productPrices['price_excl_tax'];
        $this->priceInclTax = $productPrices['price_incl_tax'];
        $this->priceBeforeDiscountExclTax = $productPrices['price_before_discount_excl_tax'];
        $this->priceBeforeDiscountInclTax = $productPrices['price_before_discount_incl_tax'];
        // get product discount amount and percent
        $productDiscount = $this->getAllDiscounts();
        $this->discountAmount = $productDiscount['discount_amount'];
        $this->discountPercent = $productDiscount['discount_percent'];
        // get product discount start and end date
        $productDiscountDates = $this->getAllDiscountDates();
        $this->discountStartDate = $productDiscountDates['discount_start_date'];
        $this->discountEndDate = $productDiscountDates['discount_end_date'];
    }

    /**
     * Get all product prices
     *
     * @return array
     */
    public function getPrices(): array
    {
        return [
            'price_excl_tax' => $this->priceExclTax,
            'price_incl_tax' => $this->priceInclTax,
            'price_before_discount_excl_tax' => $this->priceBeforeDiscountExclTax,
            'price_before_discount_incl_tax' => $this->priceBeforeDiscountInclTax,
        ];
    }

    /**
     * Get all product discount
     *
     * @return array
     */
    public function getDiscounts(): array
    {
        return [
            'discount_amount' => $this->discountAmount,
            'discount_percent' => $this->discountPercent,
            'discount_start_date' => $this->discountStartDate,
            'discount_end_date' => $this->discountEndDate,
        ];
    }

    /**
     * Clean product price for a next product
     */
    public function clean(): void
    {
        $this->product = null;
        $this->priceExclTax = null;
        $this->priceInclTax = null;
        $this->priceBeforeDiscountExclTax = null;
        $this->priceBeforeDiscountInclTax = null;
        $this->discountAmount = null;
        $this->discountPercent = null;
        $this->discountStartDate = null;
        $this->discountEndDate = null;
    }

    /**
     * Get all product prices with or without conversion
     *
     * @return array
     */
    private function getAllPrices(): array
    {
        $conversion = $this->currency !== $this->storeCurrency;
        return [
            'price_excl_tax' => $this->getSpecificPrice('final_price', $conversion),
            'price_incl_tax' => $this->getSpecificPrice('final_price', $conversion, true),
            'price_before_discount_excl_tax' => $this->getSpecificPrice('regular_price', $conversion),
            'price_before_discount_incl_tax' => $this->getSpecificPrice('regular_price', $conversion, true),
        ];
    }

    /**
     * Get specific price for a product
     *
     * @param string $code price code to get specific value
     * @param boolean $conversion currency iso code for conversion
     * @param boolean $includeTax get price with tax or not
     *
     * @return float
     */
    private function getSpecificPrice(string $code, bool $conversion = false, bool $includeTax = false): float
    {
        if ($includeTax) {
            $price = $this->product->getPriceInfo()->getPrice($code)->getAmount()->getValue();
        } else {
            $price = $this->product->getPriceInfo()->getPrice($code)->getAmount()->getBaseAmount();
        }
        if ($conversion) {
            $price = $this->priceCurrency->convert($price, $this->storeCurrency, $this->currency);
        }
        return $this->priceCurrency->round($price);
    }

    /**
     * Get Discount amount and percent
     *
     * @return array
     */
    private function getAllDiscounts(): array
    {
        $discountAmount = $this->priceBeforeDiscountInclTax - $this->priceInclTax;
        $discountAmount = $discountAmount > 0 ? $this->priceCurrency->round($discountAmount) : 0;
        $discountPercent = $discountAmount > 0
            ? $this->priceCurrency->round(($discountAmount * 100) / $this->priceBeforeDiscountInclTax)
            : 0;
        return [
            'discount_amount' => $discountAmount,
            'discount_percent' => $discountPercent,
        ];
    }

    /**
     * Get Discount start and end dates
     *
     * @return array
     */
    private function getAllDiscountDates(): array
    {
        // get discount date from a special price
        $discountStartDate = $this->product->getSpecialFromDate();
        $discountEndDate = $this->product->getSpecialToDate();
        // get discount date from a catalogue rule if exist
        $catalogueRules = $this->catalogueRule->getResource()->getRulesFromProduct(
            (int) $this->dateTime->gmtTimestamp(),
            $this->store->getWebsiteId(),
            1,
            $this->product->getId()
        );
        if (!empty($catalogueRules)) {
            $startTimestamp = (int) $catalogueRules[0]['from_time'];
            $endTimestamp = (int) $catalogueRules[0]['to_time'];
            $discountStartDate = $startTimestamp !== 0
                ? $this->getFormatedDate($startTimestamp)
                : '';
            $discountEndDate = $endTimestamp !== 0
                ? $this->getFormatedDate($endTimestamp)
                : '';
        }
        return [
            'discount_start_date' => $discountStartDate,
            'discount_end_date' => $discountEndDate,
        ];
    }

    /**
     *
     * @param int $timestamp
     */
    protected function getFormatedDate(int $timestamp): string
    {
        if (is_null($this->timezone->date($timestamp))) {
            $date = new \DateTime($timestamp);
            return $date->format(DataHelper::DATE_FULL);
        }

        return $this->timezone->date($timestamp)->format(DataHelper::DATE_FULL);
    }
}
