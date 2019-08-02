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

use Magento\Shipping\Model\Shipping as MagentoShipping;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface as PriceCurrency;
use Lengow\Connector\Helper\Config as ConfigHelper;

/**
 * Lengow export shipping
 */
class Shipping
{
    /**
     * @var \Magento\Shipping\Model\Shipping Magento shipping instance
     */
    protected $_magentoShipping;

    /**
     * @var \Magento\Shipping\Model\CarrierFactory Magento carrier Factory instance
     */
    protected $_carrierFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateRequestFactory Magento rate request instance
     */
    protected $_rateRequestFactory;

    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory Magento quote item factory
     */
    protected $_quoteItemFactory;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface Magento price currency instance
     */
    protected $_priceCurrency;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

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
     * @var string shipping carrier code
     */
    protected $_shippingCarrier;

    /**
     * @var string shipping method
     */
    protected $_shippingMethod;

    /**
     * @var string shipping country iso code
     */
    protected $_shippingCountryCode;

    /**
     * @var boolean shipping is fixed or not
     */
    protected $_shippingIsFixed;

    /**
     * @var float shipping cost
     */
    protected $_shippingCost;

    /**
     * @var float shipping cost fixed
     */
    protected $_shippingCostFixed;

    /**
     * @var float default shipping price
     */
    protected $_defaultShippingPrice;

    /**
     * Constructor
     *
     * @param \Magento\Shipping\Model\Shipping $magentoShipping Magento shipping instance
     * @param \Magento\Shipping\Model\CarrierFactory $carrierFactory Magento carrier Factory instance
     * @param \Magento\Quote\Model\Quote\Address\RateRequestFactory $rateRequestFactory Magento rate request instance
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory Magento quote item factory
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency Magento price currency instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     */
    public function __construct(
        MagentoShipping $magentoShipping,
        CarrierFactory $carrierFactory,
        RateRequestFactory $rateRequestFactory,
        ItemFactory $quoteItemFactory,
        PriceCurrency $priceCurrency,
        ConfigHelper $configHelper
    ) {
        $this->_magentoShipping = $magentoShipping;
        $this->_carrierFactory = $carrierFactory;
        $this->_rateRequestFactory = $rateRequestFactory;
        $this->_quoteItemFactory = $quoteItemFactory;
        $this->_priceCurrency = $priceCurrency;
        $this->_configHelper = $configHelper;
    }

    /**
     * init a new shipping
     *
     * @param array $params optional options for load a specific product
     * \Magento\Store\Model\Store\Interceptor store    Magento store instance
     * string                                 currency Currency iso code for conversion
     */
    public function init($params)
    {
        $this->_store = $params['store'];
        $this->_currency = $params['currency'];
        $this->_storeCurrency = $this->_store->getCurrentCurrencyCode();
        $shippingData = $this->_getShippingData();
        $this->_shippingCarrier = isset($shippingData['shipping_carrier']) ? $shippingData['shipping_carrier'] : null;
        $this->_shippingMethod = isset($shippingData['shipping_method']) ? $shippingData['shipping_method'] : null;
        $this->_shippingIsFixed = isset($shippingData['shipping_is_fixed']) ? $shippingData['shipping_is_fixed'] : null;
        $this->_shippingCountryCode = $this->_configHelper->get('shipping_country', $this->_store->getId());
        $conversion = $this->_currency !== $this->_storeCurrency ? true : false;
        $this->_defaultShippingPrice = $this->_configHelper->get('shipping_price', $this->_store->getId());
        if (!is_null($this->_defaultShippingPrice) && $conversion) {
            $this->_defaultShippingPrice = $this->_priceCurrency->convertAndRound(
                $this->_defaultShippingPrice,
                $this->_storeCurrency,
                $this->_currency
            );
        }
    }

    /**
     * Load a new shipping with specific params
     *
     * @param array $params optional options for load a specific shipping
     * \Magento\Catalog\Model\Product\Interceptor product Magento product instance
     */
    public function load($params)
    {
        $this->_product = $params['product'];
        if (!is_null($this->_defaultShippingPrice) || is_null($this->_shippingCarrier)) {
            $this->_shippingCost = $this->_defaultShippingPrice;
        } elseif ($this->_shippingIsFixed && !is_null($this->_shippingCostFixed)) {
            $this->_shippingCost = $this->_shippingCostFixed;
        } else {
            $this->_shippingCost = $this->_getProductShippingCost();
        }
    }

    /**
     * Get shipping method
     *
     * @return string
     */
    public function getShippingMethod()
    {
        return $this->_shippingMethod;
    }

    /**
     * Get shipping cost
     *
     * @return float
     */
    public function getShippingCost()
    {
        return $this->_shippingCost;
    }

    /**
     * Clean product shipping for a next product
     */
    public function clean()
    {
        $this->_product = null;
        $this->_shippingCost = null;
    }

    /**
     * Get shipping carrier and method
     *
     * @return array
     */
    protected function _getShippingData()
    {
        $shippingData = [];
        $shippingMethod = $this->_configHelper->get('shipping_method', $this->_store->getId());
        if (!is_null($shippingMethod)) {
            $shippingMethod = explode('_', $shippingMethod);
            $carrier = $this->_carrierFactory->get($shippingMethod[0]);
            $shippingData['shipping_carrier'] = $carrier ? $carrier->getCarrierCode() : '';
            $shippingData['shipping_is_fixed'] = $carrier ? $carrier->isFixed() : false;
            $shippingData['shipping_method'] = ucfirst($shippingMethod[1]);
        }
        return $shippingData;
    }

    /**
     * Get shipping Cost for a product
     *
     * @return float|boolean
     */
    protected function _getProductShippingCost()
    {
        $shippingCost = 0;
        $conversion = $this->_currency !== $this->_storeCurrency ? true : false;
        $shippingRateRequest = $this->_getShippingRateRequest();
        $result = $this->_magentoShipping->collectCarrierRates($this->_shippingCarrier, $shippingRateRequest)
            ->getResult();
        if (is_null($result) || $result->getError()) {
            return false;
        }
        $rates = $result->getAllRates();
        if (count($rates) > 0) {
            foreach ($rates as $rate) {
                $shippingCost = $rate->getPrice();
                break;
            }
            if ($conversion) {
                $shippingCost = $this->_priceCurrency->convertAndRound(
                    $shippingCost,
                    $this->_storeCurrency,
                    $this->_currency
                );
            } else {
                $shippingCost = $this->_priceCurrency->round($shippingCost);
            }
        }
        if ($this->_shippingIsFixed) {
            $this->_shippingCostFixed = $shippingCost;
        }
        return $shippingCost;
    }

    /**
     * Get shipping rate request for a product
     *
     * @return \Magento\Quote\Model\Quote\Address\RateRequest
     */
    protected function _getShippingRateRequest()
    {
        $quoteItem = $this->_quoteItemFactory->create();
        $quoteItem->setStoreId($this->_store->getId());
        $quoteItem->setOptions($this->_product->getCustomOptions())->setProduct($this->_product);
        $request = $this->_rateRequestFactory->create();
        if (!$request->getOrig()) {
            $request->setCountryId($this->_shippingCountryCode)
                ->setRegionId('')
                ->setCity('')
                ->setPostcode('');
        }
        $request->setAllItems([$quoteItem]);
        $request->setDestCountryId($this->_shippingCountryCode);
        $request->setDestRegionId('');
        $request->setDestRegionCode('');
        $request->setDestPostcode('');
        $request->setPackageValue($this->_product->getPrice());
        $request->setPackageValueWithDiscount($this->_product->getFinalPrice());
        $request->setPackageWeight($this->_product->getWeight());
        $request->setFreeMethodWeight(0);
        $request->setPackageQty(1);
        $request->setStoreId($this->_store->getId());
        $request->setWebsiteId($this->_store->getWebsiteId());
        $request->setBaseCurrency($this->_store->getBaseCurrency());
        $request->setPackageCurrency($this->_store->getCurrentCurrency());
        return $request;
    }
}
