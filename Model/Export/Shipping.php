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

use Exception;
use Magento\Catalog\Model\Product\Interceptor as ProductInterceptor;
use Magento\Framework\Pricing\PriceCurrencyInterface as PriceCurrency;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Shipping\Model\ShippingFactory;
use Magento\Store\Model\Store\Interceptor as StoreInterceptor;
use Lengow\Connector\Helper\Config as ConfigHelper;

/**
 * Lengow export shipping
 */
class Shipping
{
    /**
     * @var ProductInterceptor Magento product instance
     */
    private $product;

    /**
     * @var ShippingFactory Magento shipping Factory instance
     */
    private $shippingFactory;

    /**
     * @var CarrierFactory Magento carrier Factory instance
     */
    private $carrierFactory;

    /**
     * @var RateRequestFactory Magento rate request instance
     */
    private $rateRequestFactory;

    /**
     * @var ItemFactory Magento quote item factory
     */
    private $quoteItemFactory;

    /**
     * @var PriceCurrency Magento price currency instance
     */
    private $priceCurrency;

    /**
     * @var StoreInterceptor Magento store instance
     */
    private $store;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var string currency code for conversion
     */
    private $currency;

    /**
     * @var string original store currency
     */
    private $storeCurrency;

    /**
     * @var string shipping carrier code
     */
    private $shippingCarrier;

    /**
     * @var string shipping method
     */
    private $shippingMethod;

    /**
     * @var string shipping country iso code
     */
    private $shippingCountryCode;

    /**
     * @var boolean shipping is fixed or not
     */
    private $shippingIsFixed;

    /**
     * @var float shipping cost
     */
    private $shippingCost;

    /**
     * @var float shipping cost fixed
     */
    private $shippingCostFixed;

    /**
     * @var float default shipping price
     */
    private $defaultShippingPrice;

    /**
     * Constructor
     *
     * @param ShippingFactory $shippingFactory Magento shipping Factory instance
     * @param CarrierFactory $carrierFactory Magento carrier Factory instance
     * @param RateRequestFactory $rateRequestFactory Magento rate request instance
     * @param ItemFactory $quoteItemFactory Magento quote item factory
     * @param PriceCurrency $priceCurrency Magento price currency instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     */
    public function __construct(
        ShippingFactory $shippingFactory,
        CarrierFactory $carrierFactory,
        RateRequestFactory $rateRequestFactory,
        ItemFactory $quoteItemFactory,
        PriceCurrency $priceCurrency,
        ConfigHelper $configHelper
    ) {
        $this->shippingFactory = $shippingFactory;
        $this->carrierFactory = $carrierFactory;
        $this->rateRequestFactory = $rateRequestFactory;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->priceCurrency = $priceCurrency;
        $this->configHelper = $configHelper;
    }

    /**
     * Init a new shipping
     *
     * @param array $params optional options for load a specific product
     * StoreInterceptor store    Magento store instance
     * string           currency Currency iso code for conversion
     */
    public function init(array $params)
    {
        $this->store = $params['store'];
        $this->currency = $params['currency'];
        $this->storeCurrency = $this->store->getCurrentCurrencyCode();
        $shippingData = $this->getShippingData();
        $this->shippingCarrier = $shippingData['shipping_carrier'] ?? null;
        $this->shippingMethod = $shippingData['shipping_method'] ?? null;
        $this->shippingIsFixed = $shippingData['shipping_is_fixed'] ?? null;
        $this->shippingCountryCode = $this->configHelper->get(
            ConfigHelper::DEFAULT_EXPORT_SHIPPING_COUNTRY,
            $this->store->getId()
        );
        $conversion = $this->currency !== $this->storeCurrency;
        $this->defaultShippingPrice = $this->configHelper->get(
            ConfigHelper::DEFAULT_EXPORT_SHIPPING_PRICE,
            $this->store->getId()
        );
        if ($this->defaultShippingPrice !== null && $conversion) {
            $this->defaultShippingPrice = $this->priceCurrency->convertAndRound(
                $this->defaultShippingPrice,
                $this->storeCurrency,
                $this->currency
            );
        }
    }

    /**
     * Load a new shipping with specific params
     *
     * @param array $params optional options for load a specific shipping
     * ProductInterceptor product Magento product instance
     */
    public function load(array $params)
    {
        $this->product = $params['product'];
        if ($this->defaultShippingPrice !== null || $this->shippingCarrier === null) {
            $this->shippingCost = $this->defaultShippingPrice;
        } elseif ($this->shippingIsFixed && $this->shippingCostFixed !== null) {
            $this->shippingCost = $this->shippingCostFixed;
        } else {
            $this->shippingCost = $this->getProductShippingCost();
        }
    }

    /**
     * Get shipping method
     *
     * @return string
     */
    public function getShippingMethod(): string
    {
        return $this->shippingMethod;
    }

    /**
     * Get shipping cost
     *
     * @return float
     */
    public function getShippingCost(): float
    {
        return $this->shippingCost;
    }

    /**
     * Clean product shipping for a next product
     */
    public function clean()
    {
        $this->product = null;
        $this->shippingCost = null;
    }

    /**
     * Get shipping carrier and method
     *
     * @return array
     */
    private function getShippingData(): array
    {
        $shippingData = [];
        $shippingMethod = $this->configHelper->get(ConfigHelper::DEFAULT_EXPORT_CARRIER_ID, $this->store->getId());
        if ($shippingMethod !== null) {
            $shippingMethod = explode('_', $shippingMethod);
            $carrier = $this->carrierFactory->get($shippingMethod[0]);
            $shippingData['shipping_carrier'] = $carrier ? $carrier->getCarrierCode() : '';
            $shippingData['shipping_is_fixed'] = $carrier && $carrier->isFixed();
            $shippingData['shipping_method'] = ucfirst($shippingMethod[1]);
        }
        return $shippingData;
    }

    /**
     * Get shipping Cost for a product
     *
     * @return float|boolean
     */
    private function getProductShippingCost()
    {
        $shippingCost = 0;
        $conversion = $this->currency !== $this->storeCurrency;
        try {
            $shippingRateRequest = $this->getShippingRateRequest();
        } catch (Exception $e) {
            // without $shippingRateRequest, we can't find shipping cost
            return $shippingCost;
        }
        $shippingFactory = $this->shippingFactory->create();
        $result = $shippingFactory->collectCarrierRates($this->shippingCarrier, $shippingRateRequest)
            ->getResult();
        if ($result === null || $result->getError()) {
            return false;
        }
        $rates = $result->getAllRates();
        if (!empty($rates)) {
            foreach ($rates as $rate) {
                $shippingCost = $rate->getPrice();
                break;
            }
            if ($conversion) {
                $shippingCost = $this->priceCurrency->convertAndRound(
                    $shippingCost,
                    $this->storeCurrency,
                    $this->currency
                );
            } else {
                $shippingCost = $this->priceCurrency->round($shippingCost);
            }
        }
        if ($this->shippingIsFixed) {
            $this->shippingCostFixed = $shippingCost;
        }
        return $shippingCost;
    }

    /**
     * Get shipping rate request for a product
     *
     * @return RateRequest
     *
     * @throws Exception
     */
    private function getShippingRateRequest(): RateRequest
    {
        $quoteItem = $this->quoteItemFactory->create();
        $quoteItem->setStoreId($this->store->getId());
        $quoteItem->setOptions($this->product->getCustomOptions())->setProduct($this->product);
        $request = $this->rateRequestFactory->create();
        if (!$request->getOrig()) {
            $request->setCountryId($this->shippingCountryCode)
                ->setRegionId('')
                ->setCity('')
                ->setPostcode('');
        }
        $request->setAllItems([$quoteItem]);
        $request->setDestCountryId($this->shippingCountryCode);
        $request->setDestRegionId('');
        $request->setDestRegionCode('');
        $request->setDestPostcode('');
        $request->setPackageValue($this->product->getPrice());
        $request->setPackageValueWithDiscount($this->product->getFinalPrice());
        $request->setPackageWeight($this->product->getWeight());
        $request->setFreeMethodWeight(0);
        $request->setPackageQty(1);
        $request->setStoreId($this->store->getId());
        $request->setWebsiteId($this->store->getWebsiteId());
        $request->setBaseCurrency($this->store->getBaseCurrency());
        $request->setPackageCurrency($this->store->getCurrentCurrency());
        return $request;
    }
}
