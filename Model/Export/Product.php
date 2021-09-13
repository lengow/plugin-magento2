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

use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\Catalog\Model\Product\Interceptor as ProductInterceptor;
use Magento\Store\Model\Store\Interceptor as StoreInterceptor;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ProductRepository;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Locale\Resolver as Locale;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Export\Price as LengowPrice;
use Lengow\Connector\Model\Export\Shipping as LengowShipping;
use Lengow\Connector\Model\Export\Category as LengowCategory;
use Lengow\Connector\Helper\Security as SecurityHelper;

/**
 * Lengow export product
 */
class Product
{
    /**
     * @var ProductRepository Magento product repository instance
     */
    protected $_productRepository;

    /**
     * @var Configurable Magento product configurable instance
     */
    protected $_configurableProduct;

    /**
     * @var StockRegistryInterface Magento stock registry instance
     */
    protected $_stockRegistry;

    /**
     * @var Locale Magento locale resolver instance
     */
    protected $_locale;

    /**
     * @var DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var TimezoneInterface Magento datetime timezone instance
     */
    protected $_timezone;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var LengowCategory Lengow export category instance
     */
    protected $_category;

    /**
     * @var LengowPrice Lengow export price instance
     */
    protected $_price;

    /**
     * @var LengowShipping Lengow export shipping instance
     */
    protected $_shipping;

    /**
     * @var ProductInterceptor Magento product instance
     */
    protected $_product;

    /**
     * @var ProductInterceptor Magento product instance
     */
    protected $_parentProduct;

    /**
     * @var StoreInterceptor Magento store instance
     */
    protected $_store;

    /**
     * @var string locale iso code
     */
    protected $_localeIsoCode;

    /**
     * @var string currency code for conversion
     */
    protected $_currency;

    /**
     * @var string product type
     */
    protected $_type;

    /**
     * @var array all children ids for grouped product
     */
    protected $_childrenIds;

    /**
     * @var array all product prices data
     */
    protected $_prices;

    /**
     * @var array all product discount data
     */
    protected $_discounts;

    /**
     * @var array all product images
     */
    protected $_images;

    /**
     * @var string base image url
     */
    protected $_baseImageUrl;

    /**
     * @var string product variation list
     */
    protected $_variationList;

    /**
     * @var integer product quantity
     */
    protected $_quantity;

    /**
     * @var boolean get parent data for simple product not visible individually
     */
    protected $_getParentData;

    /**
     * @var array cache configurable products
     */
    protected $_cacheConfigurableProducts = [];

    /**
     * @var integer clear configurable product cache counter
     */
    protected $_clearCacheConfigurable = 0;

    /**
     * @var integer counter for simple product
     */
    protected $_simpleCounter = 0;

    /**
     * @var integer counter for simple product disabled
     */
    protected $_simpleDisabledCounter = 0;

    /**
     * @var integer counter for configurable product
     */
    protected $_configurableCounter = 0;

    /**
     * @var integer counter for grouped product
     */
    protected $_groupedCounter = 0;

    /**
     * @var integer counter for virtual product
     */
    protected $_virtualCounter = 0;

    /**
     * @var integer counter for downloadable product
     */
    protected $_downloadableCounter = 0;

    /**
     * @var array Parent field to select parents attributes to export instead of child's one
     */
    protected $_parentFields = [];

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var SecurityHelper Lengow security helper instance
     */
    protected $securityHelper;

    /**
     * @var Array All stock sources for this specific product
     */
    protected $quantities;

    /**
     * Constructor
     *
     * @param ProductRepository $productRepository Magento product repository instance
     * @param Configurable $configurableProduct Magento configurable product instance
     * @param StockRegistryInterface $stockRegistry Magento stock registry instance
     * @param SearchCriteriaBuilder $searchCriteriaBuilder Magento search criteria builder instance
     * @param Locale $locale Magento locale resolver instance
     * @param DateTime $dateTime Magento datetime instance
     * @param TimezoneInterface $timezone Magento datetime timezone instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param LengowPrice $price Lengow product price instance
     * @param LengowShipping $shipping Lengow product shipping instance
     * @param LengowCategory $category Lengow product category instance
     * @param SecurityHelper $securityHelper Lengow security helper instance
     */
    public function __construct(
        ProductRepository $productRepository,
        Configurable $configurableProduct,
        StockRegistryInterface $stockRegistry,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Locale $locale,
        DateTime $dateTime,
        TimezoneInterface $timezone,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        LengowPrice $price,
        LengowShipping $shipping,
        LengowCategory $category,
        SecurityHelper $securityHelper
    ) {
        $this->_productRepository = $productRepository;
        $this->_configurableProduct = $configurableProduct;
        $this->_stockRegistry = $stockRegistry;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_locale = $locale;
        $this->_dateTime = $dateTime;
        $this->_timezone = $timezone;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_price = $price;
        $this->_shipping = $shipping;
        $this->_category = $category;
        $this->securityHelper = $securityHelper;
    }

    /**
     * init a new product
     *
     * @param array $params optional options for load a specific product
     * StoreInterceptor store    Magento store instance
     * string           currency Currency iso code for conversion
     */
    public function init($params)
    {
        $this->_store = $params['store'];
        $this->_currency = $params['currency'];
        $this->_localeIsoCode = $this->_locale->getLocale();
        $this->_baseImageUrl = $this->_dataHelper->getMediaUrl() . 'catalog/product';
        $this->_price->init(['store' => $this->_store, 'currency' => $this->_currency]);
        $this->_category->init(['store' => $this->_store]);
        $this->_shipping->init(['store' => $this->_store, 'currency' => $this->_currency]);
        $this->_parentFields = $params['parentFields'];
    }

    /**
     * Load a new product with a specific params
     *
     * @param array $params optional options for load a specific product
     * string  product_type Magento product type
     * integer product_id   Magento product id
     *
     * @throws \Exception
     */
    public function load($params)
    {
        $this->_type = $params['product_type'];
        $this->_product = $this->_getProduct($params['product_id']);
        $this->_parentProduct = $this->_getParentProduct();
        $this->_childrenIds = $this->_getChildrenIds();
        $this->_images = $this->_getImages();
        $this->_variationList = $this->_getVariationList();
        $this->quantities = []; // reset sources
        $this->_quantity = $this->_getQuantity();
        if ($this->_type === 'grouped') {
            $groupedPrices = $this->_getGroupedPricesAndDiscounts();
            $this->_prices = $groupedPrices['prices'];
            $this->_discounts = $groupedPrices['discounts'];
        } else {
            $this->_price->load(['product' => $this->_product]);
            $this->_prices = $this->_price->getPrices();
            $this->_discounts = $this->_price->getDiscounts();
        }
        $this->_category->load(['product' => $this->_getParentData ? $this->_parentProduct : $this->_product]);
        $this->_shipping->load(['product' => $this->_product]);
        $this->_setCounter();
    }

    /**
     * Retrieves stock sources that are assigned to product sku
     *
     * @param string $sku product sku
     * @return SourceItemInterface[]
     */
    public function getSourceItemDetailBySKU(string $sku)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->create();
        // We use object manager here because SourceRepositoryInterface is only available for version >= 2.3
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $sourceItemRepository = $objectManager->create('Magento\InventoryApi\Api\SourceItemRepositoryInterface');
        return $sourceItemRepository->getList($searchCriteria)->getItems();
    }

    /**
     * Get data of current product
     *
     * @param string $field field to export
     *
     * @return float|integer|string
     */
    public function getData($field)
    {
        switch ($field) {
            case 'id':
                return $this->_product->getId();
            case 'sku':
                return $this->_product->getSku();
            case 'name':
                $name = $this->_getParentData ? $this->_parentProduct->getName() : $this->_product->getName();
                return $this->_dataHelper->cleanData($name);
            case 'child_name':
                return $this->_dataHelper->cleanData($this->_product->getName());
            case 'quantity':
                return $this->_quantity;
            case 'status':
                return (int) $this->_product->getStatus() === ProductStatus::STATUS_DISABLED ? 'Disabled' : 'Enabled';
            case 'category':
                return $this->_category->getCategoryBreadcrumb();
            case 'url':
                $routeParams = ['_nosid' => true, '_query' => ['___store' => $this->_store->getCode()]];
                return $this->_getParentData
                    ? $this->_parentProduct->getUrlInStore($routeParams)
                    : $this->_product->getUrlInStore($routeParams);
            case 'price_excl_tax':
            case 'price_incl_tax':
            case 'price_before_discount_excl_tax':
            case 'price_before_discount_incl_tax':
                return $this->_prices[$field];
            case 'discount_amount':
            case 'discount_percent':
            case 'discount_start_date':
            case 'discount_end_date':
                return $this->_discounts[$field];
            case 'shipping_method':
                return $this->_shipping->getShippingMethod();
            case 'shipping_cost':
                return $this->_shipping->getShippingCost();
            case 'currency':
                return $this->_currency;
            case 'image_default':
            case 'image_url_1':
            case 'image_url_2':
            case 'image_url_3':
            case 'image_url_4':
            case 'image_url_5':
            case 'image_url_6':
            case 'image_url_7':
            case 'image_url_8':
            case 'image_url_9':
            case 'image_url_10':
                return $this->_images[$field];
            case 'type':
                $type = $this->_type;
                if ($type === 'simple' && $this->_parentProduct) {
                    $type = 'child';
                } elseif ($type === 'configurable') {
                    $type = 'parent';
                }
                return $type;
            case 'parent_id':
                return $this->_parentProduct ? $this->_parentProduct->getId() : '';
            case 'variation':
                return $this->_variationList;
            case 'language':
                return $this->_localeIsoCode;
            case 'description':
                $description = $this->_getParentData
                    ? $this->_parentProduct->getDescription()
                    : $this->_product->getDescription();
                return $this->_dataHelper->cleanHtml($this->_dataHelper->cleanData($description));
            case 'description_html':
                $description = $this->_getParentData
                    ? $this->_parentProduct->getDescription()
                    : $this->_product->getDescription();
                return $this->_dataHelper->cleanData($description);
            case 'description_short':
                $descriptionShort = $this->_getParentData
                    ? $this->_parentProduct->getShortDescription()
                    : $this->_product->getShortDescription();
                return $this->_dataHelper->cleanHtml($this->_dataHelper->cleanData($descriptionShort));
            case 'description_short_html':
                $descriptionShort = $this->_getParentData
                    ? $this->_parentProduct->getShortDescription()
                    : $this->_product->getShortDescription();
                return $this->_dataHelper->cleanData($descriptionShort);
            case (preg_match('`quantity_multistock_.+`', $field) ? true : false):
                return (isset($this->quantities[$field]) && $this->quantities[$field]['status'])
                    ? (int) $this->quantities[$field]['quantity']
                    : 0;
            default:
                return $this->_dataHelper->cleanData($this->_getAttributeValue($field));
        }
    }

    /**
     * Check if a simple product is enable
     *
     * @return boolean
     */
    public function isEnableForExport()
    {
        if ($this->_type === 'simple'
            && $this->_parentProduct
            && (int) $this->_parentProduct->getStatus() === ProductStatus::STATUS_DISABLED
        ) {
            $this->_simpleDisabledCounter++;
            return false;
        }
        return true;
    }

    /**
     * Clean data for next product
     */
    public function clean()
    {
        if ($this->_type !== 'configurable') {
            $this->_product->clearInstance();
        }
        $this->_product = null;
        $this->_parentProduct = null;
        $this->_type = null;
        $this->_childrenIds = null;
        $this->_prices = null;
        $this->_discounts = null;
        $this->_images = null;
        $this->_variationList = null;
        $this->_quantity = null;
        $this->_getParentData = false;
        $this->_price->clean();
        $this->_shipping->clean();
        $this->_category->clean();
    }

    /**
     * Get all counters for different product types
     *
     * @return array
     */
    public function getCounters()
    {
        $simpleTotal = $this->_simpleCounter - $this->_simpleDisabledCounter;
        $total = $simpleTotal + $this->_configurableCounter + $this->_groupedCounter
            + $this->_virtualCounter + $this->_downloadableCounter;
        return [
            'total' => $total,
            'simple' => $this->_simpleCounter,
            'simple_enable' => $simpleTotal,
            'simple_disabled' => $this->_simpleDisabledCounter,
            'configurable' => $this->_configurableCounter,
            'grouped' => $this->_groupedCounter,
            'virtual' => $this->_virtualCounter,
            'downloadable' => $this->_downloadableCounter,
        ];
    }

    /**
     * Set product counter for different product types
     */
    protected function _setCounter()
    {
        switch ($this->_type) {
            case 'simple':
                $this->_simpleCounter++;
                break;
            case 'configurable':
                $this->_configurableCounter++;
                break;
            case 'virtual':
                $this->_virtualCounter++;
                break;
            case 'downloadable':
                $this->_downloadableCounter++;
                break;
            case 'grouped':
                $this->_groupedCounter++;
                break;
            default:
                break;
        }
    }

    /**
     * Get product
     *
     * @param integer $productId Magento product is
     * @param boolean $forceReload force reload for product repository
     *
     * @throws \Exception
     *
     * @return ProductInterceptor
     */
    protected function _getProduct($productId, $forceReload = false)
    {
        if ($this->_type === 'configurable') {
            $product = $this->_getConfigurableProduct($productId);
        } else {
            $product = $this->_productRepository->getById($productId, false, $this->_store->getId(), $forceReload);
        }
        return $product;
    }

    /**
     * Get parent product for simple product
     *
     * @throws \Exception
     *
     * @return ProductInterceptor|null
     */
    protected function _getParentProduct()
    {
        $parentProduct = null;
        if ($this->_type === 'simple') {
            $parentIds = $this->_configurableProduct->getParentIdsByChild($this->_product->getId());
            if (!empty($parentIds)) {
                $parentProduct = $this->_getConfigurableProduct((int) $parentIds[0]);
                if ($parentProduct
                    && (int) $this->_product->getVisibility() === ProductVisibility::VISIBILITY_NOT_VISIBLE
                ) {
                    $this->_getParentData = true;
                }
            }
        }
        return $parentProduct;
    }

    /**
     * Get parent product with temporary cache
     *
     * @param integer $parentId Magento parent entity id
     *
     * @throws \Exception
     *
     * @return ProductInterceptor|null
     */
    protected function _getConfigurableProduct($parentId)
    {
        if (!isset($this->_cacheConfigurableProducts[$parentId])) {
            if ($this->_clearCacheConfigurable > 300) {
                $this->_clearCacheConfigurable = 0;
                $this->_cacheConfigurableProducts = [];
            }
            $parentProduct = $this->_productRepository->getById($parentId, false, $this->_store->getId());
            if ($parentProduct && $parentProduct->getTypeId() === 'configurable') {
                $this->_cacheConfigurableProducts[$parentId] = $parentProduct;
                $this->_clearCacheConfigurable++;
            } else {
                return null;
            }
        }
        return $this->_cacheConfigurableProducts[$parentId];
    }

    /**
     * Get product images
     *
     * @return array
     */
    protected function _getImages()
    {
        $urls = [];
        $images = [];
        $imageUrls = [];
        $parentImages = false;
        // create image urls array
        for ($i = 1; $i < 11; $i++) {
            $imageUrls['image_url_' . $i] = '';
        }
        // get product and parent images
        if ($this->_parentProduct
            && $this->_parentProduct->getMediaGalleryImages() !== null
            && $this->_configHelper->get(ConfigHelper::EXPORT_PARENT_IMAGE_ENABLED, $this->_store->getId())
        ) {
            $parentImages = $this->_parentProduct->getMediaGalleryImages()->toArray();
        }
        if ($this->_product->getMediaGalleryImages() !== null) {
            $images = $this->_product->getMediaGalleryImages()->toArray();
            $images = isset($images['items']) ? $images['items'] : [];
        }
        $images = $parentImages ? array_merge($parentImages['items'], $images) : $images;
        // cleans the array of images to avoid duplicates
        foreach ($images as $image) {
            if (!in_array($image['url'], $urls, true)) {
                $urls[] = $image['url'];
            }
        }
        // retrieves up to 10 images per product
        $counter = 1;
        foreach ($urls as $url) {
            $imageUrls['image_url_' . $counter] = $url;
            if ($counter === 10) {
                break;
            }
            $counter++;
        }
        // get default image if exist
        $imageUrls['image_default'] = $this->_product->getImage() !== null
            ? $this->_baseImageUrl . $this->_product->getImage()
            : '';
        return $imageUrls;
    }

    /**
     * Get product variation list for a configurable product
     *
     * @return string
     */
    protected function _getVariationList()
    {
        $variationList = '';
        $variations = false;
        // get variation only for configurable product and child
        if ($this->_type === 'configurable') {
            $variations = $this->_product->getTypeInstance()->getConfigurableAttributesAsArray($this->_product);
        } elseif ($this->_type === 'simple' && $this->_parentProduct) {
            $variations = $this->_parentProduct->getTypeInstance()
                ->getConfigurableAttributesAsArray($this->_parentProduct);
        }
        if ($variations) {
            foreach ($variations as $variation) {
                $variationList .= strtolower($variation['frontend_label']) . ', ';
            }
            $variationList = rtrim($variationList, ', ');
        }
        return $variationList;
    }

    /**
     * Get children ids for a grouped product
     *
     * @return string
     */
    protected function _getChildrenIds()
    {
        $childrenIds = [];
        if ($this->_type === 'grouped') {
            $childrenIds = array_reduce(
                $this->_product->getTypeInstance()->getChildrenIds($this->_product->getId()),
                static function (array $reduce, $value) {
                    return array_merge($reduce, $value);
                },
                []
            );
        }
        return $childrenIds;
    }

    /**
     * Get quantity for grouped products
     *
     * @return integer
     */
    protected function _getQuantity()
    {
        if ($this->_configHelper->moduleIsEnabled('Magento_Inventory')
            && version_compare($this->securityHelper->getMagentoVersion(), '2.3.0', '>=')
        ) {
            // Check if product is multi-stock
            $res = $this->getSourceItemDetailBySKU($this->_product->getSku());
            // if multi-stock, return total of all stock quantities
            if (count($res) >= 1) {
                $total = 0;
                foreach ($res as $item) {
                    $dataSource = $item->getData();
                    $this->quantities['quantity_multistock_' . $dataSource['source_code']] = $dataSource;
                    if ($dataSource['status']) {
                        $total += (int) $dataSource['quantity'];
                    }
                }
                return $total;
            }
        }
        if ($this->_type === 'grouped' && !empty($this->_childrenIds)) {
            $quantities = [];
            foreach ($this->_childrenIds as $childrenId) {
                $quantities[] = $this->_stockRegistry->getStockItem($childrenId, $this->_store->getId())->getQty();
            }
            $quantity = min($quantities) > 0 ? (int) min($quantities) : 0;
        } else {
            $quantity = (int) $this->_stockRegistry->getStockItem($this->_product->getId(), $this->_store->getId())
                ->getQty();
        }
        return $quantity;
    }

    /**
     * Get product attribute value
     *
     * @param string $field name a of specific attribute
     *
     * @return string|null
     */
    protected function _getAttributeValue($field)
    {
        $attributeValue = '';
        if ($this->_parentProduct && in_array($field, $this->_parentFields, true)) {
            $product = $this->_parentProduct;
        } else {
            $product = $this->_product;
        }
        $attribute = $product->getData($field);
        if ($attribute !== null) {
            if (is_array($attribute)) {
                $attributeValue = '';
                foreach ($attribute as $key => $value) {
                    // checks whether an array-form product attribute contains another array
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    if (!is_numeric($key)) {
                        $value = $key . ': ' . $value;
                    }
                    $attributeValue .= $value . ', ';
                }
                $attributeValue = rtrim($attributeValue, ', ');
            } else {
                $attributeValue = $product->getResource()
                    ->getAttribute($field)
                    ->getFrontend()
                    ->getValue($product);
            }
        }
        return $attributeValue;
    }

    /**
     * Get prices and discounts for grouped products
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function _getGroupedPricesAndDiscounts()
    {
        $startTimestamps = [];
        $endTimestamps = [];
        $startTimestamp = 0;
        $endTimestamp = 0;
        $prices = [
            'price_excl_tax' => 0,
            'price_incl_tax' => 0,
            'price_before_discount_excl_tax' => 0,
            'price_before_discount_incl_tax' => 0,
        ];
        if (!empty($this->_childrenIds)) {
            foreach ($this->_childrenIds as $childrenId) {
                $children = $this->_getProduct($childrenId, true);
                $this->_price->load(['product' => $children]);
                $childrenPrices = $this->_price->getPrices();
                foreach ($childrenPrices as $key => $value) {
                    $prices[$key] += $value;
                }
                $childrenDiscount = $this->_price->getDiscounts();
                if ($childrenDiscount['discount_start_date'] !== '') {
                    $startTimestamps[] = $this->_dateTime->gmtTimestamp($childrenDiscount['discount_start_date']);
                }
                if ($childrenDiscount['discount_end_date'] !== '') {
                    $endTimestamps[] = $this->_dateTime->gmtTimestamp($childrenDiscount['discount_end_date']);
                }
                $this->_price->clean();
            }
        }
        // get discount amount and percent
        $discountAmount = $prices['price_before_discount_incl_tax'] - $prices['price_incl_tax'];
        $discountAmount = $discountAmount > 0 ? $discountAmount : 0;
        $discountPercent = $discountAmount > 0
            ? round((($discountAmount * 100) / $prices['price_before_discount_incl_tax']), 2)
            : 0;
        // get discount end and start date
        if (!empty($endTimestamps)) {
            $endTimestamp = min($endTimestamps);
        }
        if (!empty($startTimestamps)) {
            $startTimestamp = max($startTimestamps);
            // reset start timestamp if end date is before start date
            if ($endTimestamp > 0 && $startTimestamp > $endTimestamp) {
                $startTimestamp = 0;
            }
        }
        $discountStartDate = $startTimestamp !== 0
            ? $this->_timezone->date($startTimestamp)->format('Y-m-d H:i:s')
            : '';
        $discountEndDate = $endTimestamp !== 0
            ? $this->_timezone->date($endTimestamp)->format('Y-m-d H:i:s')
            : '';
        $discounts = [
            'discount_amount' => $discountAmount,
            'discount_percent' => $discountPercent,
            'discount_start_date' => $discountStartDate,
            'discount_end_date' => $discountEndDate,
        ];
        return ['prices' => $prices, 'discounts' => $discounts];
    }
}
