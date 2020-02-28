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

/**
 * Lengow export price
 */
class Price
{
    /**
     * @var ProductInterceptor Magento product instance
     */
    protected $_product;

    /**
     * @var StoreInterceptor Magento store instance
     */
    protected $_store;

    /**
     * @var CatalogueRule Magento catalogue rule instance
     */
    protected $_catalogueRule;

    /**
     * @var PriceCurrency Magento price currency instance
     */
    protected $_priceCurrency;

    /**
     * @var DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var TimezoneInterface Magento datetime timezone instance
     */
    protected $_timezone;

    /**
     * @var string currency code for conversion
     */
    protected $_currency;

    /**
     * @var string original store currency
     */
    protected $_storeCurrency;

    /**
     * @var float Product price exclude tax
     */
    protected $_priceExclTax;

    /**
     * @var float Product price include tax
     */
    protected $_priceInclTax;

    /**
     * @var float Product price before discount exclude tax
     */
    protected $_priceBeforeDiscountExclTax;

    /**
     * @var float Product price before discount include tax
     */
    protected $_priceBeforeDiscountInclTax;

    /**
     * @var float discount amount
     */
    protected $_discountAmount;

    /**
     * @var float discount percent
     */
    protected $_discountPercent;

    /**
     * @var string discount start date
     */
    protected $_discountStartDate;

    /**
     * @var string discount end date
     */
    protected $_discountEndDate;

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
    )
    {
        $this->_priceCurrency = $priceCurrency;
        $this->_catalogueRule = $catalogueRule;
        $this->_dateTime = $dateTime;
        $this->_timezone = $timezone;
    }

    /**
     * init a new price
     *
     * @param array $params optional options for load a specific product
     * StoreInterceptor store    Magento store instance
     * string           currency Currency iso code for conversion
     */
    public function init($params)
    {
        $this->_currency = $params['currency'];
        $this->_store = $params['store'];
        $this->_storeCurrency = $this->_store->getCurrentCurrencyCode();
    }

    /**
     * Load a new price with a specific params
     *
     * @param array $params optional options for load a specific price
     * ProductInterceptor product Magento product instance
     */
    public function load($params)
    {
        $this->_product = $params['product'];
        // get product prices
        $productPrices = $this->_getAllPrices();
        $this->_priceExclTax = $productPrices['price_excl_tax'];
        $this->_priceInclTax = $productPrices['price_incl_tax'];
        $this->_priceBeforeDiscountExclTax = $productPrices['price_before_discount_excl_tax'];
        $this->_priceBeforeDiscountInclTax = $productPrices['price_before_discount_incl_tax'];
        // get product discount amount and percent
        $productDiscount = $this->_getAllDiscounts();
        $this->_discountAmount = $productDiscount['discount_amount'];
        $this->_discountPercent = $productDiscount['discount_percent'];
        // get product discount start and end date
        $productDiscountDates = $this->_getAllDiscountDates();
        $this->_discountStartDate = $productDiscountDates['discount_start_date'];
        $this->_discountEndDate = $productDiscountDates['discount_end_date'];
    }

    /**
     * Get all product prices
     *
     * @return array
     */
    public function getPrices()
    {
        return [
            'price_excl_tax' => $this->_priceExclTax,
            'price_incl_tax' => $this->_priceInclTax,
            'price_before_discount_excl_tax' => $this->_priceBeforeDiscountExclTax,
            'price_before_discount_incl_tax' => $this->_priceBeforeDiscountInclTax,
        ];
    }

    /**
     * Get all product discount
     *
     * @return array
     */
    public function getDiscounts()
    {
        return [
            'discount_amount' => $this->_discountAmount,
            'discount_percent' => $this->_discountPercent,
            'discount_start_date' => $this->_discountStartDate,
            'discount_end_date' => $this->_discountEndDate,
        ];
    }

    /**
     * Clean product price for a next product
     */
    public function clean()
    {
        $this->_product = null;
        $this->_priceExclTax = null;
        $this->_priceInclTax = null;
        $this->_priceBeforeDiscountExclTax = null;
        $this->_priceBeforeDiscountInclTax = null;
        $this->_discountAmount = null;
        $this->_discountPercent = null;
        $this->_discountStartDate = null;
        $this->_discountEndDate = null;
    }

    /**
     * Get all product prices with or without conversion
     *
     * @return array
     */
    protected function _getAllPrices()
    {
        $conversion = $this->_currency !== $this->_storeCurrency ? true : false;
        $prices = [
            'price_excl_tax' => $this->_getSpecificPrice('final_price', $conversion),
            'price_incl_tax' => $this->_getSpecificPrice('final_price', $conversion, true),
            'price_before_discount_excl_tax' => $this->_getSpecificPrice('regular_price', $conversion),
            'price_before_discount_incl_tax' => $this->_getSpecificPrice('regular_price', $conversion, true),
        ];
        return $prices;
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
    protected function _getSpecificPrice($code, $conversion = false, $includeTax = false)
    {
        if ($includeTax) {
            $price = $this->_product->getPriceInfo()->getPrice($code)->getAmount()->getValue();
        } else {
            $price = $this->_product->getPriceInfo()->getPrice($code)->getAmount()->getBaseAmount();
        }
        if ($conversion) {
            $price = $this->_priceCurrency->convert($price, $this->_storeCurrency, $this->_currency);
        }
        return $this->_priceCurrency->round($price);
    }

    /**
     * Get Discount amount and percent
     *
     * @return array
     */
    protected function _getAllDiscounts()
    {
        $discountAmount = $this->_priceBeforeDiscountInclTax - $this->_priceInclTax;
        $discountAmount = $discountAmount > 0 ? $this->_priceCurrency->round($discountAmount) : 0;
        $discountPercent = $discountAmount > 0
            ? $this->_priceCurrency->round(($discountAmount * 100) / $this->_priceBeforeDiscountInclTax)
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
    protected function _getAllDiscountDates()
    {
        // get discount date from a special price
        $discountStartDate = $this->_product->getSpecialFromDate();
        $discountEndDate = $this->_product->getSpecialToDate();
        // get discount date from a catalogue rule if exist
        $catalogueRules = $this->_catalogueRule->getResource()->getRulesFromProduct(
            (int)$this->_dateTime->gmtTimestamp(),
            $this->_store->getWebsiteId(),
            1,
            $this->_product->getId()
        );
        if (!empty($catalogueRules)) {
            $startTimestamp = (int)$catalogueRules[0]['from_time'];
            $endTimestamp = (int)$catalogueRules[0]['to_time'];
            $discountStartDate = $startTimestamp !== 0
                ? $this->_timezone->date($startTimestamp)->format('Y-m-d H:i:s')
                : '';
            $discountEndDate = $endTimestamp !== 0
                ? $this->_timezone->date($endTimestamp)->format('Y-m-d H:i:s')
                : '';
        }
        return [
            'discount_start_date' => $discountStartDate,
            'discount_end_date' => $discountEndDate,
        ];
    }
}
