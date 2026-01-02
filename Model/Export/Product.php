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
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Catalog\Api\Data\ProductInterface;
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
use Magento\BundleImportExport\Model\Export\Product\Type\Bundle;

/**
 * Lengow export product
 */
class Product
{
    /**
     * @var ProductRepository Magento product repository instance
     */
    private $productRepository;

    /**
     * @var Configurable Magento product configurable instance
     */
    private $configurableProduct;

    /**
     * @var StockRegistryInterface Magento stock registry instance
     */
    private $stockRegistry;

    /**
     * @var Locale Magento locale resolver instance
     */
    private $locale;

    /**
     * @var DateTime Magento datetime instance
     */
    private $dateTime;

    /**
     * @var TimezoneInterface Magento datetime timezone instance
     */
    private $timezone;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var LengowCategory Lengow export category instance
     */
    private $category;

    /**
     * @var LengowPrice Lengow export price instance
     */
    private $price;

    /**
     * @var LengowShipping Lengow export shipping instance
     */
    private $shipping;

    /**
     * @var ProductInterface Magento product instance
     */
    private $product;

    /**
     * @var ProductInterface Magento product instance
     */
    private $parentProduct;

    /**
     * @var StoreInterceptor Magento store instance
     */
    private $store;

    /**
     * @var string locale iso code
     */
    private $localeIsoCode;

    /**
     * @var string currency code for conversion
     */
    private $currency;

    /**
     * @var string product type
     */
    private $type;

    /**
     * @var array all children ids for grouped product
     */
    private $childrenIds;

    /**
     * @var array all product prices data
     */
    private $prices;

    /**
     * @var array all product discount data
     */
    private $discounts;

    /**
     * @var array all product images
     */
    private $images;

    /**
     * @var string base image url
     */
    private $baseImageUrl;

    /**
     * @var string product variation list
     */
    private $variationList;

    /**
     * @var integer product quantity
     */
    private $quantity;

    /**
     * @var boolean get parent data for simple product not visible individually
     */
    private $getParentData;

    /**
     * @var array cache configurable products
     */
    private $cacheConfigurableProducts = [];

    /**
     * @var integer clear configurable product cache counter
     */
    private $clearCacheConfigurable = 0;

    /**
     * @var integer counter for simple product
     */
    private $simpleCounter = 0;

    /**
     * @var integer counter for simple product disabled
     */
    private $simpleDisabledCounter = 0;

    /**
     * @var integer counter for configurable product
     */
    private $configurableCounter = 0;

    /**
     * @var integer counter for grouped product
     */
    private $groupedCounter = 0;

    /**
     * @var integer counter for virtual product
     */
    private $virtualCounter = 0;

    /**
     * @var integer counter for downloadable product
     */
    private $downloadableCounter = 0;

    /**
     * @var array Parent field to select parents attributes to export instead of child's one
     */
    private $parentFields = [];

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SecurityHelper Lengow security helper instance
     */
    private $securityHelper;

    /**
     * @var array All stock sources for this specific product
     */
    private $quantities;

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
        $this->productRepository = $productRepository;
        $this->configurableProduct = $configurableProduct;
        $this->stockRegistry = $stockRegistry;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->locale = $locale;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->price = $price;
        $this->shipping = $shipping;
        $this->category = $category;
        $this->securityHelper = $securityHelper;
    }

    /**
     * Init a new product
     *
     * @param array $params optional options for load a specific product
     * StoreInterceptor store    Magento store instance
     * string           currency Currency iso code for conversion
     */
    public function init(array $params): void
    {
        $this->store = $params['store'];
        $this->currency = $params['currency'];
        $this->localeIsoCode = $this->locale->getLocale();
        $this->baseImageUrl = $this->dataHelper->getMediaUrl() . 'catalog/product';
        $this->price->init(['store' => $this->store, 'currency' => $this->currency]);
        $this->category->init(['store' => $this->store]);
        $this->shipping->init(['store' => $this->store, 'currency' => $this->currency]);
        $this->parentFields = $params['parentFields'];
    }

    /**
     * Load a new product with a specific params
     *
     * @param array $params optional options for load a specific product
     * string  product_type Magento product type
     * integer product_id   Magento product id
     *
     * @throws Exception
     */
    public function load(array $params): void
    {

        $this->type = $params['product_type'];
        $this->product = $this->getProduct($params['product_id']);
        $this->parentProduct = $this->getParentProduct();
        $this->childrenIds = $this->getChildrenIds();
        $this->images = $this->getImages();
        $this->variationList = $this->getVariationList();
        $this->quantities = []; // reset sources
        $this->quantity = $this->getQuantity();
        if ($this->type === Grouped::TYPE_CODE) {
            $groupedPrices = $this->getGroupedPricesAndDiscounts();
            $this->prices = $groupedPrices['prices'];
            $this->discounts = $groupedPrices['discounts'];
        } elseif($this->type === 'bundle') {
            $bundlePrices = $this->getBundlePricesAndDiscounts();
            $this->prices = $bundlePrices['prices'];
            $this->discounts = $bundlePrices['discounts'];

        } else {
            $this->price->load(['product' => $this->product]);
            $this->prices = $this->price->getPrices();
            $this->discounts = $this->price->getDiscounts();
        }
        $this->category->load(['product' => $this->getParentData ? $this->parentProduct : $this->product]);
        $this->shipping->load(['product' => $this->product]);
        $this->setCounter();

    }

    /**
     * Retrieves stock sources that are assigned to product sku
     *
     * @param string $sku product sku
     * @return SourceItemInterface[]
     */
    public function getSourceItemDetailBySKU(string $sku): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->create();
        // We use object manager here because SourceRepositoryInterface is only available for version >= 2.3
        $objectManager = ObjectManager::getInstance();
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
    public function getData(string $field)
    {
        switch ($field) {
            case 'id':
                return $this->product->getId();
            case 'sku':
                return $this->product->getSku();
            case 'name':
                $name = $this->getParentData ? $this->parentProduct->getName() : $this->product->getName();
                return $this->dataHelper->cleanData($name);
            case 'child_name':
                return $this->dataHelper->cleanData($this->product->getName());
            case 'quantity':
                return $this->quantity;
            case 'status':
                return (int) $this->product->getStatus() === ProductStatus::STATUS_DISABLED ? 'Disabled' : 'Enabled';
            case 'category':
                return $this->category->getCategoryBreadcrumb();
            case 'url':
                $routeParams = ['_nosid' => true, '_query' => ['___store' => $this->store->getCode()]];
                return $this->getParentData
                    ? $this->parentProduct->getUrlInStore($routeParams)
                    : $this->product->getUrlInStore($routeParams);
            case 'price_excl_tax':
            case 'price_incl_tax':
            case 'price_before_discount_excl_tax':
            case 'price_before_discount_incl_tax':
                return $this->prices[$field];
            case 'discount_amount':
            case 'discount_percent':
            case 'discount_start_date':
            case 'discount_end_date':
                return $this->discounts[$field];
            case 'shipping_method':
                return $this->shipping->getShippingMethod();
            case 'shipping_cost':
                return $this->shipping->getShippingCost();
            case 'currency':
                return $this->currency;
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
                return $this->images[$field];
            case 'type':
                $type = $this->type;
                if ($type === 'simple' && $this->parentProduct) {
                    $type = 'child';
                } elseif ($type === 'configurable') {
                    $type = 'parent';
                }
                return $type;
            case 'parent_id':
                return $this->parentProduct ? $this->parentProduct->getId() : '';
            case 'variation':
                return $this->variationList;
            case 'language':
                return $this->localeIsoCode;
            case 'description':
                $description = $this->getParentData
                    ? $this->parentProduct->getDescription()
                    : $this->product->getDescription();
                return $this->dataHelper->cleanHtml($this->dataHelper->cleanData($description));
            case 'description_html':
                $description = $this->getParentData
                    ? $this->parentProduct->getDescription()
                    : $this->product->getDescription();
                return $this->dataHelper->cleanData($description);
            case 'description_short':
                $descriptionShort = $this->getParentData
                    ? $this->parentProduct->getShortDescription()
                    : $this->product->getShortDescription();
                return $this->dataHelper->cleanHtml($this->dataHelper->cleanData($descriptionShort));
            case 'description_short_html':
                $descriptionShort = $this->getParentData
                    ? $this->parentProduct->getShortDescription()
                    : $this->product->getShortDescription();
                return $this->dataHelper->cleanData($descriptionShort);
            case (bool) preg_match('`quantity_multistock_.+`', $field):
                return (isset($this->quantities[$field]) && $this->quantities[$field]['status'])
                    ? (int) $this->quantities[$field]['quantity']
                    : 0;
            default:
                return $this->dataHelper->cleanData($this->getAttributeValue($field));
        }
    }

    /**
     * Check if a simple product is enabled
     *
     * @return boolean
     */
    public function isEnableForExport(): bool
    {
        if ($this->type === 'simple'
            && $this->parentProduct
            && (int) $this->parentProduct->getStatus() === ProductStatus::STATUS_DISABLED
        ) {
            $this->simpleDisabledCounter++;
            return false;
        }
        return true;
    }

    /**
     * Clean data for next product
     */
    public function clean(): void
    {
        if ($this->type !== Configurable::TYPE_CODE) {
            $this->product->clearInstance();
        }
        $this->product = null;
        $this->parentProduct = null;
        $this->type = null;
        $this->childrenIds = null;
        $this->prices = null;
        $this->discounts = null;
        $this->images = null;
        $this->variationList = null;
        $this->quantity = null;
        $this->getParentData = false;
        $this->price->clean();
        $this->shipping->clean();
        $this->category->clean();
    }

    /**
     * Get all counters for different product types
     *
     * @return array
     */
    public function getCounters(): array
    {
        $simpleTotal = $this->simpleCounter - $this->simpleDisabledCounter;
        $total = $simpleTotal + $this->configurableCounter + $this->groupedCounter
            + $this->virtualCounter + $this->downloadableCounter;
        return [
            'total' => $total,
            'simple' => $this->simpleCounter,
            'simple_enable' => $simpleTotal,
            'simple_disabled' => $this->simpleDisabledCounter,
            'configurable' => $this->configurableCounter,
            'grouped' => $this->groupedCounter,
            'virtual' => $this->virtualCounter,
            'downloadable' => $this->downloadableCounter,
        ];
    }

    /**
     * Set product counter for different product types
     */
    private function setCounter(): void
    {
        switch ($this->type) {
            case 'simple':
                $this->simpleCounter++;
                break;
            case 'configurable':
                $this->configurableCounter++;
                break;
            case 'virtual':
                $this->virtualCounter++;
                break;
            case 'downloadable':
                $this->downloadableCounter++;
                break;
            case 'grouped':
                $this->groupedCounter++;
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
     * @return ProductInterface|null
     *
     * @throws Exception
     */
    protected function getProduct(int $productId, bool $forceReload = false): ?ProductInterface
    {
        if ($this->type === Configurable::TYPE_CODE) {
            $product = $this->getConfigurableProduct($productId);
        } else {
            $product = $this->productRepository->getById($productId, false, $this->store->getId(), $forceReload);
        }
        if (!$product instanceof ProductInterface) {
            return null;
        }
        return $product;
    }

    /**
     * Get parent product for simple product
     *
     * @return ProductInterface|null
     *
     * @throws Exception
     */
    private function getParentProduct(): ?ProductInterface
    {
        $parentProduct = null;
        if ($this->type === 'simple') {
            $parentIds = $this->configurableProduct->getParentIdsByChild($this->product->getId());
            if (!empty($parentIds)) {
                $parentProduct = $this->getConfigurableProduct((int) $parentIds[0]);
                if ($parentProduct
                    && (int) $this->product->getVisibility() === ProductVisibility::VISIBILITY_NOT_VISIBLE
                ) {
                    $this->getParentData = true;
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
     * @return ProductInterface|null
     *
     * @throws Exception
     */
    protected function getConfigurableProduct(int $parentId): ?ProductInterface
    {
        if (!isset($this->cacheConfigurableProducts[$parentId])) {
            if ($this->clearCacheConfigurable > 300) {
                $this->clearCacheConfigurable = 0;
                $this->cacheConfigurableProducts = [];
            }
            $parentProduct = $this->productRepository->getById($parentId, false, $this->store->getId());
            if ($parentProduct && $parentProduct->getTypeId() ===  Configurable::TYPE_CODE) {
                $this->cacheConfigurableProducts[$parentId] = $parentProduct;
                $this->clearCacheConfigurable++;
            } else {
                return null;
            }
        }
        $productParentCached = $this->cacheConfigurableProducts[$parentId];

        if (!$productParentCached instanceof ProductInterface) {
            return null;
        }

        return $productParentCached;
    }

    /**
     * Get product images
     *
     * @return array
     */
    private function getImages(): array
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
        if ($this->parentProduct
            && $this->parentProduct->getMediaGalleryImages() !== null
            && $this->configHelper->get(ConfigHelper::EXPORT_PARENT_IMAGE_ENABLED, (int) $this->store->getId())
        ) {
            $parentImages = $this->parentProduct->getMediaGalleryImages()->toArray();
        }
        if ($this->product->getMediaGalleryImages() !== null) {
            $images = $this->product->getMediaGalleryImages()->toArray();
            $images = $images['items'] ?? [];
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
        $imageUrls['image_default'] = $this->product->getImage() !== null
            ? $this->baseImageUrl . $this->product->getImage()
            : '';
        return $imageUrls;
    }

    /**
     * Get product variation list for a configurable product
     *
     * @return string
     */
    private function getVariationList(): string
    {
        $variationList = '';
        $variations = false;
        // get variation only for configurable product and child
        if ($this->type ===  Configurable::TYPE_CODE) {
            $variations = $this->product->getTypeInstance()->getConfigurableAttributesAsArray($this->product);
        } elseif ($this->type === 'simple' && $this->parentProduct) {
            $variations = $this->parentProduct->getTypeInstance()
                ->getConfigurableAttributesAsArray($this->parentProduct);
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
    private function getChildrenIds()
    {
        $childrenIds = [];
        if ($this->type === Grouped::TYPE_CODE) {
            $childrenIds = array_reduce(
                $this->product->getTypeInstance()->getChildrenIds($this->product->getId()),
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
    private function getQuantity(): int
    {
        if ($this->isMsiEnabled()) {
            $stock = $this->getStockFromMsi($this->product->getSku());
            if (null !== $stock) {
                // if multi-stock, return total of all stock quantities
                return $stock;
            }
        }

        if ($this->type === Grouped::TYPE_CODE && !empty($this->childrenIds)) {
            $quantities = [];
            foreach ($this->childrenIds as $childrenId) {
                $quantities[] = $this->stockRegistry->getStockItem($childrenId, $this->store->getId())->getQty();
            }
            return min($quantities) > 0 ? (int) min($quantities) : 0;
        }
        if ($this->type === 'bundle') {
            $quantities = [];
            $quantityIds = [];
            $bundleOptions = $this->getBundleOptionsProductIds($this->product);
            foreach ($bundleOptions as $option) {
                foreach ($option as $dataProduct) {
                    $productId = $dataProduct['product_id'];
                    $defaultQty = ($dataProduct['default_qty'] > 0) ? $dataProduct['default_qty'] : 1;
                    if ($this->isMsiEnabled()) {
                        try {
                            $product = $this->productRepository->getById($productId, false, $this->store->getId(), true);
                            $stockQty = $this->getStockFromMsi($product->getSku());
                            if (null === $stockQty) {
                                // no stock from msi
                                $stockQty = $this->stockRegistry->getStockItem($productId, $this->store->getId())->getQty();
                            }
                        } catch (NoSuchEntityException $e) {
                            $stockQty = 0;
                        }
                    } else {
                        $stockQty = $this->stockRegistry->getStockItem($productId, $this->store->getId())->getQty();
                    }

                    // an option can have the same product as another
                    // let adjust after we have the final quantities
                    $quantityIds[$productId] = isset($quantityIds[$productId])
                        ? $quantityIds[$productId] + $defaultQty
                        : $defaultQty;
                    $quantities[$productId] = $stockQty;
                }
            }

            foreach ($quantityIds as $pid => $quantity) {
                if ($quantity > 1) {
                    $quantities[$pid] = floor($quantities[$pid] / $quantity);
                }
            }

            if (0 === count($quantities)) {
                return 0;
            }

            return min($quantities) > 0 ? (int) min($quantities) : 0;
        }
        return (int) $this->stockRegistry->getStockItem($this->product->getId(), $this->store->getId())->getQty();
    }

    private function isMsiEnabled(): bool
    {
        return $this->configHelper->moduleIsEnabled('Magento_Inventory')
            && version_compare($this->securityHelper->getMagentoVersion(), '2.3.0', '>=');
    }

    /**
     * Return total of all stock quantities, or null if no source found
     */
    private function getStockFromMsi(string $sku): ?int
    {
        $res = $this->getSourceItemDetailBySKU($sku);
        if (!count($res)) {
            return null;
        }

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

    /**
     * get all the selection products used in bundle product
     * @param $product
     * @return mixed
     */
    private function getBundleOptionsProductIds($product)
    {
        $bundleOptions = [];
        $optionIds = [];
        $selectionCollection = $product->getTypeInstance()
            ->getSelectionsCollection(
                $product->getTypeInstance()->getOptionsIds($product),
                $product
            );
        foreach ($selectionCollection as $selection) {
            if (in_array($selection->getOptionId(), $optionIds)) {
                continue;
            }
            $optionIds[] = $selection->getOptionId();
        }

        // default product selection in many options
        if (count($optionIds) > 1) {
            foreach ($selectionCollection as $selection) {
                if (!$selection->getIsDefault()) {
                    continue;
                }
                $bundleOptions[$selection->getOptionId()][] = [
                    'product_id' => (int)  $selection->getProductId(),
                    'default_qty' => (int) $selection->getSelectionQty()
                ];
            }
        } else {
            // all product selection in one option
            foreach ($selectionCollection as $selection) {
                $bundleOptions[$selection->getOptionId()][] = [
                    'product_id' => (int) $selection->getProductId(),
                    'default_qty' => (int) $selection->getSelectionQty()
                ];
            }
        }

        return $bundleOptions;
    }

    /**
     * Get product attribute value
     *
     * @param string|null $field name an of specific attribute
     *
     * @return string|null
     */
    private function getAttributeValue(?string $field = null): ?string
    {
        $attributeValue = '';
        $attributeValueParent = '';
        $productParent = null;
        $attributeParent = null;

        if ($this->parentProduct && in_array($field, $this->parentFields, true)) {
            $productParent = $this->parentProduct;
            $attributeParent = $productParent->getData($field);
        }

        $product = $this->product;
        $attribute = $product->getData($field);

        if ($attribute !== null) {
            if (is_array($attribute)) {
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

        if ($attributeParent !== null) {
            if (is_array($attributeParent)) {
                foreach ($attributeParent as $key => $value) {
                    // checks whether an array-form product attribute contains another array
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    if (!is_numeric($key)) {
                        $value = $key . ': ' . $value;
                    }
                    $attributeValueParent .= $value . ', ';
                }
                $attributeValueParent = rtrim($attributeValueParent, ', ');
            } else {
                $attributeValueParent = $productParent->getResource()
                    ->getAttribute($field)
                    ->getFrontend()
                    ->getValue($productParent);
            }
        }
        if (!empty($attributeParent) && !empty($attributeValueParent)) {
            return is_array($attributeValueParent) ? json_encode($attributeValueParent) : $attributeValueParent;
        }
        if ($this->product->getTypeId() === 'bundle' && $field === 'quantity_and_stock_status') {
            $attributeValue =  str_replace('qty: 0','qty: '.$this->getQuantity(), $attributeValue);
        }
        return is_array($attributeValue) ? json_encode($attributeValue) : $attributeValue;
    }

    /**
     * Get prices and discounts for grouped products
     *
     * @throws Exception
     *
     * @return array
     */
    private function getGroupedPricesAndDiscounts(): array
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
        if (!empty($this->childrenIds)) {
            foreach ($this->childrenIds as $childrenId) {
                $children = $this->getProduct($childrenId, true);
                $this->price->load(['product' => $children]);
                $childrenPrices = $this->price->getPrices();
                foreach ($childrenPrices as $key => $value) {
                    $prices[$key] += $value;
                }
                $childrenDiscount = $this->price->getDiscounts();
                if ($childrenDiscount['discount_start_date'] !== '') {
                    $startTimestamps[] = $this->dateTime->gmtTimestamp($childrenDiscount['discount_start_date']);
                }
                if ($childrenDiscount['discount_end_date'] !== '') {
                    $endTimestamps[] = $this->dateTime->gmtTimestamp($childrenDiscount['discount_end_date']);
                }
                $this->price->clean();
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
            ? $this->timezone->date($startTimestamp)->format(DataHelper::DATE_FULL)
            : '';
        $discountEndDate = $endTimestamp !== 0
            ? $this->timezone->date($endTimestamp)->format(DataHelper::DATE_FULL)
            : '';
        $discounts = [
            'discount_amount' => $discountAmount,
            'discount_percent' => $discountPercent,
            'discount_start_date' => $discountStartDate,
            'discount_end_date' => $discountEndDate,
        ];
        return ['prices' => $prices, 'discounts' => $discounts];
    }

    /**
     * Will return bundle prices and discounts
     */
    private function getBundlePricesAndDiscounts()
    {

        $bundleOptions = $this->getBundleOptionsProductIds($this->product);

        $prices = [
            'price_excl_tax' => 0,
            'price_incl_tax' => 0,
            'price_before_discount_excl_tax' => 0,
            'price_before_discount_incl_tax' => 0,
        ];
        $discounts = [
            'discount_amount' => 0,
            'discount_percent' => 0,
            'discount_start_date' => null,
            'discount_end_date' => null,
        ];

        $childrenQty = [];
        foreach ($bundleOptions as $option) {
            foreach ($option as $dataProduct) {
                $this->childrenIds[] = $dataProduct['product_id'];
                $childrenQty[$dataProduct['product_id']] = $dataProduct['default_qty'];
            }
        }

        if (!empty($this->childrenIds)) {
            foreach ($this->childrenIds as $index => $childrenId) {

                $children = $this->getProduct($childrenId, true);
                $this->price->load(['product' => $children]);
                $childrenPrices = $this->price->getPrices();

                foreach ($childrenPrices as $key => $value) {
                    $defaultQty = $childrenQty[$childrenId] ?? 1;
                    $prices[$key] += $value * $defaultQty;
                }
                $this->price->clean();
                $this->price->load(['product' => $this->product]);
                $childrenDiscount = $this->price->getDiscounts();
                $this->price->clean();
                unset($this->childrenIds[$index]);

            }
        }
        $discountPercent = $childrenDiscount['discount_percent'] ?? 0;
        $discountAmount = 0;
        $discountAmountHT = 0;
        if ($discountPercent > 0) {
            $discountAmount = round(
                $prices['price_before_discount_incl_tax'] * $childrenDiscount['discount_percent'] / 100,
                3
            );
            $discountAmountHT = round(
                $prices['price_before_discount_excl_tax'] * $childrenDiscount['discount_percent'] / 100,
                3
            );

        } else {
            $discountAmount = $childrenDiscount['discount_amount'] ?? 0;
            $discountAmountHT = $childrenDiscount['discount_amount'] ?? 0;
        }


        if (!empty($childrenDiscount['discount_start_date'])) {
            $discountStart = new \DateTime($childrenDiscount['discount_start_date']);
            $end = $childrenDiscount['discount_end_date'] ?? 'now';
            $discountEnd = new \DateTime($end);
            if (empty($childrenDiscount['discount_end_date'])) {
                $discountEnd->add(new \DateInterval('P1Y'));
            }
            $now = new \DateTime();
            if ($now < $discountStart) {
                $discountAmount = 0;
                $discountPercent = 0;
                $discountAmountHT = 0;
            }

            if (!empty($childrenDiscount['discount_end_date']) && $now > $discountEnd) {
                $discountAmount = 0;
                $discountPercent = 0;
                $discountAmountHT = 0;
            }
            $discounts = [
                'discount_start_date' => $this->timezone->date($discountStart->getTimestamp())->format(DataHelper::DATE_FULL),
                'discount_end_date' => $this->timezone->date($discountEnd->getTimestamp())->format(DataHelper::DATE_FULL),
            ];


        }
        $discounts['discount_amount'] = $discountAmount;
        $discounts['discount_percent'] = $discountPercent;
        $prices['price_incl_tax'] = $prices['price_before_discount_incl_tax'] - $discountAmount;
        $prices['price_excl_tax'] = $prices['price_before_discount_excl_tax'] - $discountAmountHT;


        return ['prices' => $prices, 'discounts' => $discounts];

    }
}

