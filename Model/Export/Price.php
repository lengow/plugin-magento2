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

use Magento\Framework\Pricing\PriceCurrencyInterface as PriceCurrency;
use Magento\CatalogRule\Model\Rule as CatalogueRule;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Lengow export price
 */
class Price
{
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface Magento price currency instance
     */
    protected $_priceCurrency;

    /**
     * @var \Magento\CatalogRule\Model\Rule Magento catalogue rule instance
     */
    protected $_catalogueRule;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var \Magento\Catalog\Model\Product\Interceptor Magento product instance
     */
    protected $_product;

    /**
     * @var \Magento\Store\Model\Store\Interceptor Magento store instance
     */
    protected $_store;

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
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency Magento price currency instance
     * @param \Magento\CatalogRule\Model\Rule $catalogueRule Magento catalogue rule instance
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime Magento datetime instance
     */
    public function __construct(
        PriceCurrency $priceCurrency,
        CatalogueRule $catalogueRule,
        DateTime $dateTime
    ) {
        $this->_priceCurrency = $priceCurrency;
        $this->_catalogueRule = $catalogueRule;
        $this->_dateTime = $dateTime;
    }

    /**
     * init a new price
     *
     * @param array $params optional options for load a specific product
     * \Magento\Store\Model\Store\Interceptor store    Magento store instance
     * string                                 currency Currency iso code for conversion
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
     * \Magento\Catalog\Model\Product\Interceptor product Magento product instance
     */
    public function load($params)
    {
        $this->_product = $params['product'];
        // Get product prices
        $productPrices = $this->_getAllPrices();
        $this->_priceExclTax = $productPrices['price_excl_tax'];
        $this->_priceInclTax = $productPrices['price_incl_tax'];
        $this->_priceBeforeDiscountExclTax = $productPrices['price_before_discount_excl_tax'];
        $this->_priceBeforeDiscountInclTax = $productPrices['price_before_discount_incl_tax'];
        // Get product discount amount and percent
        $productDiscount = $this->_getAllDiscounts();
        $this->_discountAmount = $productDiscount['discount_amount'];
        $this->_discountPercent = $productDiscount['discount_percent'];
        // Get product discount start and end date
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
            'price_excl_tax'                 => $this->_priceExclTax,
            'price_incl_tax'                 => $this->_priceInclTax,
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
            'discount_amount'     => $this->_discountAmount,
            'discount_percent'    => $this->_discountPercent,
            'discount_start_date' => $this->_discountStartDate,
            'discount_end_date'   => $this->_discountEndDate
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
        $conversion = $this->_currency != $this->_storeCurrency ? true : false;
        $prices = [
            'price_excl_tax'                 => $this->_getSpecificPrice('final_price', $conversion),
            'price_incl_tax'                 => $this->_getSpecificPrice('final_price', $conversion, true),
            'price_before_discount_excl_tax' => $this->_getSpecificPrice('regular_price', $conversion),
            'price_before_discount_incl_tax' => $this->_getSpecificPrice('regular_price', $conversion, true)
        ];
        return $prices;
    }

    /**
     * Get specific price for a product
     *
     * @param string  $code       price code to get specific value
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
            'discount_amount'  => $discountAmount,
            'discount_percent' => $discountPercent
        ];
    }

    /**
     * Get Discount start and end dates
     *
     * @return array
     */
    protected function _getAllDiscountDates()
    {
        // Get discount date from a special price
        $discountStartDate = $this->_product->getSpecialFromDate();
        $discountEndDate = $this->_product->getSpecialToDate();
        // Get discount date from a catalogue rule if exist
        $catalogueRules = $this->_catalogueRule->getResource()->getRulesFromProduct(
            (int)$this->_dateTime->gmtTimestamp(),
            $this->_store->getWebsiteId(),
            1,
            $this->_product->getId()
        );
        if (count($catalogueRules) > 0) {
            $startTimestamp = $catalogueRules[0]['from_time'];
            $endTimestamp = $catalogueRules[0]['to_time'];
            $discountStartDate = $startTimestamp != 0 ? $this->_dateTime->date('Y-m-d H:i:s', $startTimestamp) : '';
            $discountEndDate = $endTimestamp != 0 ? $this->_dateTime->date('Y-m-d H:i:s', $endTimestamp) : '';
        }
        return [
            'discount_start_date' => $discountStartDate,
            'discount_end_date'   => $discountEndDate
        ];
    }
}
