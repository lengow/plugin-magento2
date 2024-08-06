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

namespace Lengow\Connector\Model;

use Exception;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Model\Configuration as CatalogInventoryConfiguration;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store\Interceptor as StoreInterceptor;
use Magento\Store\Model\WebsiteFactory;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Exception as LengowException;
use Lengow\Connector\Model\Export\Feed as LengowFeed;
use Lengow\Connector\Model\Export\FeedFactory as LengowFeedFactory;
use Lengow\Connector\Model\Export\ProductFactory as LengowProductFactory;
use Lengow\Connector\Helper\Security as SecurityHelper;

/**
 * Lengow export
 */
class Export
{
    /* Export GET params */
    public const PARAM_TOKEN = 'token';
    public const PARAM_MODE = 'mode';
    public const PARAM_FORMAT = 'format';
    public const PARAM_STREAM = 'stream';
    public const PARAM_OFFSET = 'offset';
    public const PARAM_LIMIT = 'limit';
    public const PARAM_TYPE = 'type';
    public const PARAM_SELECTION = 'selection';
    public const PARAM_OUT_OF_STOCK = 'out_of_stock';
    public const PARAM_PRODUCT_IDS = 'product_ids';
    public const PARAM_PRODUCT_TYPES = 'product_types';
    public const PARAM_INACTIVE = 'inactive';
    public const PARAM_STORE = 'store';
    public const PARAM_STORE_ID = 'store_id';
    public const PARAM_CODE = 'code';
    public const PARAM_CURRENCY = 'currency';
    public const PARAM_LANGUAGE = 'language';
    public const PARAM_LOG_OUTPUT = 'log_output';
    public const PARAM_UPDATE_EXPORT_DATE = 'update_export_date';
    public const PARAM_GET_PARAMS = 'get_params';

    /* Legacy export GET params for old versions */
    public const PARAM_LEGACY_LANGUAGE = 'locale';

    /* Export types */
    public const TYPE_MANUAL = 'manual';
    public const TYPE_CRON = 'cron';
    public const TYPE_MAGENTO_CRON = 'magento cron';

    /**
     * @var StoreManagerInterface Magento store manager instance
     */
    private $storeManager;

    /**
     * @var DateTime Magento datetime instance
     */
    private $dateTime;

    /**
     * @var ScopeConfigInterface Magento scope config instance
     */
    private $scopeConfig;

    /**
     * @var ProductStatus Magento product status instance
     */
    private $productStatus;

    /**
     * @var ProductCollectionFactory Magento product collection factory
     */
    private $productCollectionFactory;

    /**
     * @var JsonHelper Magento json helper instance
     */
    private $jsonHelper;

    /**
     * @var WebsiteFactory Magento website factory instance
     */
    private $websiteFactory;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var LengowFeedFactory Lengow feed factory instance
     */
    private $feedFactory;

    /**
     * @var LengowProductFactory Lengow product factory instance
     */
    private $productFactory;

    /**
     * @var array all available params for export
     */
    private $exportParams = [
        self::PARAM_MODE,
        self::PARAM_FORMAT,
        self::PARAM_STREAM,
        self::PARAM_OFFSET,
        self::PARAM_LIMIT,
        self::PARAM_SELECTION,
        self::PARAM_OUT_OF_STOCK,
        self::PARAM_PRODUCT_IDS,
        self::PARAM_PRODUCT_TYPES,
        self::PARAM_INACTIVE,
        self::PARAM_STORE,
        self::PARAM_CODE,
        self::PARAM_CURRENCY,
        self::PARAM_LANGUAGE,
        self::PARAM_LOG_OUTPUT,
        self::PARAM_UPDATE_EXPORT_DATE,
        self::PARAM_GET_PARAMS,
    ];

    /**
     * @var array available formats for export
     */
    private $availableFormats = [
        LengowFeed::FORMAT_CSV,
        LengowFeed::FORMAT_YAML,
        LengowFeed::FORMAT_XML,
        LengowFeed::FORMAT_JSON,
    ];

    /**
     * @var array available formats for export
     */
    private $availableProductTypes = [
        'configurable',
        'simple',
        'downloadable',
        'grouped',
        'virtual',
        'bundle'
    ];

    /**
     * @var array default fields for export
     */
    private $defaultFields = [
        'id' => 'id',
        'sku' => 'sku',
        'name' => 'name',
        'child_name' => 'child_name',
        'quantity' => 'quantity',
        'status' => 'status',
        'category' => 'category',
        'url' => 'url',
        'price_excl_tax' => 'price_excl_tax',
        'price_incl_tax' => 'price_incl_tax',
        'price_before_discount_excl_tax' => 'price_before_discount_excl_tax',
        'price_before_discount_incl_tax' => 'price_before_discount_incl_tax',
        'discount_amount' => 'discount_amount',
        'discount_percent' => 'discount_percent',
        'discount_start_date' => 'discount_start_date',
        'discount_end_date' => 'discount_end_date',
        'shipping_method' => 'shipping_method',
        'shipping_cost' => 'shipping_cost',
        'currency' => 'currency',
        'image_default' => 'image_default',
        'image_url_1' => 'image_url_1',
        'image_url_2' => 'image_url_2',
        'image_url_3' => 'image_url_3',
        'image_url_4' => 'image_url_4',
        'image_url_5' => 'image_url_5',
        'image_url_6' => 'image_url_6',
        'image_url_7' => 'image_url_7',
        'image_url_8' => 'image_url_8',
        'image_url_9' => 'image_url_9',
        'image_url_10' => 'image_url_10',
        'type' => 'type',
        'parent_id' => 'parent_id',
        'variation' => 'variation',
        'language' => 'language',
        'description' => 'description',
        'description_html' => 'description_html',
        'description_short' => 'description_short',
        'description_short_html' => 'description_short_html',
    ];

    /**
     * @var StoreInterceptor Magento store instance
     */
    private $store;

    /**
     * @var integer Magento store id
     */
    private $storeId;

    /**
     * @var integer amount of products to export
     */
    private $limit;

    /**
     * @var integer offset of total product
     */
    private $offset;

    /**
     * @var string format to return
     */
    private $format;

    /**
     * @var boolean stream return
     */
    private $stream;

    /**
     * @var string currency iso code for conversion
     */
    private $currency;

    /**
     * @var boolean export Lengow selection
     */
    private $selection;

    /**
     * @var boolean export out of stock product
     */
    private $outOfStock;

    /**
     * @var boolean include active products
     */
    private $inactive;

    /**
     * @var boolean see log or not
     */
    private $logOutput;

    /**
     * @var array export product types
     */
    private $productTypes;

    /**
     * @var array product ids to be exported
     */
    private $productIds;

    /**
     * @var boolean update export date.
     */
    private $updateExportDate;

    /**
     * @var string export type (manual, cron or magento cron)
     */
    private $exportType;

    /**
     * @var SecurityHelper Lengow security helper instance
     */
    private $securityHelper;

    /**
     * Constructor
     *
     * @param StoreManagerInterface $storeManager Magento store manager instance
     * @param DateTime $dateTime Magento datetime instance
     * @param ScopeConfigInterface $scopeConfig Magento scope config instance
     * @param ProductStatus $productStatus Magento product status instance
     * @param ProductCollectionFactory $productCollectionFactory
     * @param JsonHelper $jsonHelper Magento json helper instance
     * @param WebsiteFactory $websiteFactory Magento website factory instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param LengowFeedFactory $feedFactory Lengow feed factory instance
     * @param LengowProductFactory $productFactory Lengow product factory instance
     * @param SecurityHelper $securityHelper Lengow security helper instance
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DateTime $dateTime,
        ScopeConfigInterface $scopeConfig,
        ProductStatus $productStatus,
        ProductCollectionFactory $productCollectionFactory,
        JsonHelper $jsonHelper,
        WebsiteFactory $websiteFactory,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        LengowFeedFactory $feedFactory,
        LengowProductFactory $productFactory,
        SecurityHelper $securityHelper
    ) {
        $this->storeManager = $storeManager;
        $this->dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
        $this->productStatus = $productStatus;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->jsonHelper = $jsonHelper;
        $this->websiteFactory = $websiteFactory;
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->feedFactory = $feedFactory;
        $this->productFactory = $productFactory;
        $this->securityHelper = $securityHelper;
    }

    /**
     * Init a new export
     *
     * @param array $params optional options for init
     * integer store_id     ID of store
     * integer limit        The number of product to be exported
     * integer offset       From what product export
     * string  mode         Export mode => size: display only exported products, total: display all products
     * string  format       Export Format (csv|yaml|xml|json)
     * string  type         Type of export (manual, cron or magento cron)
     * string  product_type Type(s) of product
     * string  currency     Currency for export
     * string  product_ids  Ids product to export
     * boolean inactive     Export disabled products (1) | Export only enabled products (0)
     * boolean out_of_stock Export product in stock and out stock (1) | Export Only in stock product (0)
     * boolean selection    Export selected product (1) | Export all products (0)
     * boolean stream       Display file when call script (1) | Save File (0)
     * boolean log_output   See logs (only when stream = 0) (1) | no logs (0)
     */
    public function init(array $params): void
    {
        $this->dataHelper->registerShutdownFunction();
        $this->storeId = isset($params[self::PARAM_STORE_ID]) ? (int) $params[self::PARAM_STORE_ID] : 0;
        try {
            $this->store = $this->storeManager->getStore($this->storeId);
        } catch (Exception $e) {
            $this->store = $this->storeManager->getDefaultStoreView();
        }
        $this->limit = isset($params[self::PARAM_LIMIT]) ? (int) $params[self::PARAM_LIMIT] : 0;
        $this->offset = isset($params[self::PARAM_OFFSET]) ? (int) $params[self::PARAM_OFFSET] : 0;
        $this->stream = isset($params[self::PARAM_STREAM])
            ? (bool) $params[self::PARAM_STREAM]
            : !(bool) $this->configHelper->get(ConfigHelper::EXPORT_FILE_ENABLED, $this->storeId);
        $this->selection = isset($params[self::PARAM_SELECTION])
            ? (bool) $params[self::PARAM_SELECTION]
            : (bool) $this->configHelper->get(ConfigHelper::SELECTION_ENABLED, $this->storeId);
        $this->inactive = isset($params[self::PARAM_INACTIVE])
            ? (bool) $params[self::PARAM_INACTIVE]
            : (bool) $this->configHelper->get(ConfigHelper::INACTIVE_ENABLED, $this->storeId);
        $this->outOfStock = isset($params[self::PARAM_OUT_OF_STOCK])
                ? (bool) $params[self::PARAM_OUT_OF_STOCK]
                : $this->configHelper->get(ConfigHelper::OUT_OF_STOCK_ENABLED, $this->storeId) ;
        $this->updateExportDate = !isset($params[self::PARAM_UPDATE_EXPORT_DATE])
            || $params[self::PARAM_UPDATE_EXPORT_DATE];
        $this->format = $this->setFormat($params[self::PARAM_FORMAT] ?? LengowFeed::FORMAT_CSV);
        $this->productIds = $this->setProductIds($params[self::PARAM_PRODUCT_IDS] ?? false);
        $this->productTypes = $this->setProductTypes($params[self::PARAM_PRODUCT_TYPES] ?? false);
        $this->logOutput = $this->setLogOutput($params[self::PARAM_LOG_OUTPUT] ?? true);
        $this->currency = $this->setCurrency($params[self::PARAM_CURRENCY] ?? false);
        $this->exportType = $this->setType($params[self::PARAM_TYPE] ?? false);
    }

    /**
     * Get total available products
     *
     * @return integer
     **/
    public function getTotalProduct(): int
    {
        $productCollection = $this->productCollectionFactory->create()
            ->setStoreId($this->storeId)
            ->addStoreFilter($this->storeId);

        return $productCollection->getSize();
    }

    /**
     * Get total exported products
     *
     * @return integer
     **/
    public function getTotalExportProduct(): int
    {
        $productCollection = $this->getQuery();
        return $productCollection->getSize();
    }

    /**
     * Execute the export
     **/
    public function exec(): void
    {
        try {
            // start timer
            $timeStart = $this->microtimeFloat();
            // clean logs
            $this->dataHelper->cleanLog();
            $this->dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->dataHelper->setLogMessage('## start %1 export ##', [$this->exportType]),
                $this->logOutput
            );
            $this->dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->dataHelper->setLogMessage(
                    'start export in store %1 (%2)',
                    [
                        $this->store->getName(),
                        $this->storeId,
                    ]
                ),
                $this->logOutput
            );
            // get fields to export
            $fields = $this->getFields();
            // get products to be exported
            $productCollection = $this->getQuery();
            $products = $productCollection->getData();
            $this->dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->dataHelper->setLogMessage('%1 product(s) found', [count($products)]),
                $this->logOutput
            );
            $this->export($products, $fields);
            if ($this->updateExportDate) {
                $this->configHelper->set(
                    ConfigHelper::LAST_UPDATE_EXPORT,
                    $this->dateTime->gmtTimestamp(),
                    $this->storeId
                );
            }
            $timeEnd = $this->microtimeFloat();
            $this->dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->dataHelper->setLogMessage('memory usage: %1 Mb', [round(memory_get_usage() / 1000000, 2)]),
                $this->logOutput
            );
            $this->dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->dataHelper->setLogMessage('execution time: %1 seconds', [round($timeEnd - $timeStart, 4)]),
                $this->logOutput
            );
            $this->dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->dataHelper->setLogMessage('## end %1 export ##', [$this->exportType]),
                $this->logOutput
            );
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (Exception $e) {
            $errorMessage = '[Magento error]: "' . $e->getMessage()
                . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            $decodedMessage = $this->dataHelper->decodeLogMessage($errorMessage, false);
            $this->dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->dataHelper->setLogMessage('export failed - %1', [$decodedMessage]),
                $this->logOutput
            );
        }
    }

    /**
     * Export products
     *
     * @param array $products list of products to be exported
     * @param array $fields list of fields to export
     *
     * @throws Exception|LengowException
     */
    private function export(array $products, array $fields): void
    {
        $isFirst = true;
        $productCount = 0;
        // get modulo for export counter
        $productModulo = $this->getProductModulo(count($products));
        // get the maximum of character for yaml format
        $maxCharacter = $this->getMaxCharacterSize($fields);
        // init product to export
        $lengowProduct = $this->productFactory->create();
        $lengowProduct->init([
            'store' => $this->store,
            'currency' => $this->currency,
            'parentFields' => $this->configHelper->getParentSelectedAttributes($this->storeId)
        ]);
        // init feed to export
        $feed = $this->feedFactory->create();
        $feed->init(
            [
                'stream' => $this->stream,
                'format' => $this->format,
                'store_code' => $this->store->getCode(),
            ]
        );
        $feed->write(LengowFeed::HEADER, $fields);
        foreach ($products as $product) {
            $productData = [];
            $lengowProduct->load(
                [
                    'product_id' => (int) $product['entity_id'],
                    'product_type' => $product['type_id'],
                ]
            );
            if (!$this->inactive && !$lengowProduct->isEnableForExport()) {
                $lengowProduct->clean();
                continue;
            }
            foreach ($fields as $field) {
                if (isset($this->defaultFields[$field])) {
                    $productData[$field] = $lengowProduct->getData($this->defaultFields[$field]);
                } else {
                    $productData[$field] = $lengowProduct->getData($field);
                }
            }
            // write product data
            $feed->write(LengowFeed::BODY, $productData, $isFirst, $maxCharacter);
            $productCount++;
            $this->setCounterLog($productModulo, $productCount);
            // clean data for next product
            $lengowProduct->clean();
            unset($productData);
            $isFirst = false;
        }
        $success = $feed->end();
        if (!$success) {
            throw new LengowException(
                $this->dataHelper->setLogMessage('unable to access the folder %1', [$feed->getFolderPath()])
            );
        }
        // product counter
        $counters = $lengowProduct->getCounters();
        $this->dataHelper->log(
            DataHelper::CODE_EXPORT,
            $this->dataHelper->setLogMessage(
                '%1 product(s) exported, %2 simple product(s), %3 configurable product(s),
                %4 grouped product(s), %5 virtual product(s), %6 downloadable product(s)',
                [
                    $counters['total'],
                    $counters['simple_enable'],
                    $counters['configurable'],
                    $counters['grouped'],
                    $counters['virtual'],
                    $counters['downloadable'],
                ]
            ),
            $this->logOutput
        );
        // warning for simple product associated with configurable products disabled
        if ($counters['simple_disabled'] > 0) {
            $this->dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->dataHelper->setLogMessage(
                    'WARNING! %1 simple product(s) associated with configurable products are disabled',
                    [$counters['simple_disabled']]
                ),
                $this->logOutput
            );
        }
        // link generation
        if (!$this->stream) {
            $feedUrl = $feed->getUrl();
            if ($feedUrl) {
                $this->dataHelper->log(
                    DataHelper::CODE_EXPORT,
                    $this->dataHelper->setLogMessage(
                        'the export for the store %1 (%2) generated the following file: %3',
                        [
                            $this->store->getName(),
                            $this->storeId,
                            $feedUrl,
                        ]
                    ),
                    $this->logOutput
                );
            }
        }
        unset($lengowProduct, $feed);
    }

    /**
     * Get all export available parameters
     *
     * @return string
     */
    public function getExportParams(): string
    {
        $params = $availableStores = $availableCodes = $availableCurrencies = $availableLanguages = [];
        foreach ($this->websiteFactory->create()->getCollection() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $availableStores[] = (int) $store->getId();
                    $availableCodes[] = $store->getCode();
                    $currencyCodes = $store->getAvailableCurrencyCodes();
                    foreach ($currencyCodes as $currencyCode) {
                        if (!in_array($currencyCode, $availableCurrencies, true)) {
                            $availableCurrencies[] = $currencyCode;
                        }
                    }
                    $storeLanguage = $this->scopeConfig->getValue(
                        'general/locale/code',
                        ScopeInterface::SCOPE_STORE,
                        (int) $store->getId()
                    );
                    if (!in_array($storeLanguage, $availableLanguages, true)) {
                        $availableLanguages[] = $storeLanguage;
                    }
                }
            }
        }
        foreach ($this->exportParams as $param) {
            switch ($param) {
                case self::PARAM_MODE:
                    $authorizedValue = ['size', 'total'];
                    $type = 'string';
                    $example = 'size';
                    break;
                case self::PARAM_FORMAT:
                    $authorizedValue = $this->availableFormats;
                    $type = 'string';
                    $example = LengowFeed::FORMAT_CSV;
                    break;
                case self::PARAM_STORE:
                    $authorizedValue = $availableStores;
                    $type = 'integer';
                    $example = 1;
                    break;
                case self::PARAM_CODE:
                    $authorizedValue = $availableCodes;
                    $type = 'string';
                    $example = 'french';
                    break;
                case self::PARAM_CURRENCY:
                    $authorizedValue = $availableCurrencies;
                    $type = 'string';
                    $example = 'EUR';
                    break;
                case self::PARAM_LANGUAGE:
                    $authorizedValue = $availableLanguages;
                    $type = 'string';
                    $example = 'fr_FR';
                    break;
                case self::PARAM_OFFSET:
                case self::PARAM_LIMIT:
                    $authorizedValue = 'all integers';
                    $type = 'integer';
                    $example = 100;
                    break;
                case self::PARAM_PRODUCT_IDS:
                    $authorizedValue = 'all integers';
                    $type = 'string';
                    $example = '101,108,215';
                    break;
                case self::PARAM_PRODUCT_TYPES:
                    $authorizedValue = $this->availableProductTypes;
                    $type = 'string';
                    $example = 'configurable,simple,grouped';
                    break;
                default:
                    $authorizedValue = [0, 1];
                    $type = 'integer';
                    $example = 1;
                    break;
            }
            $params[$param] = [
                'authorized_values' => $authorizedValue,
                'type' => $type,
                'example' => $example,
            ];
        }
        return $this->jsonHelper->jsonEncode($params);
    }

    /**
     * Get fields to export
     *
     * @return array
     */
    private function getFields(): array
    {
        $fields = [];
        foreach (array_keys($this->defaultFields) as $key) {
            $fields[] = $key;
        }
        $selectedAttributes = $this->configHelper->getSelectedAttributes($this->storeId);
        foreach ($selectedAttributes as $selectedAttribute) {
            if (!in_array($selectedAttribute, $fields, true)) {
                $fields[] = $selectedAttribute;
            }
        }
        if ($this->configHelper->moduleIsEnabled('Magento_Inventory')
            && version_compare($this->securityHelper->getMagentoVersion(), '2.3.0', '>=')
        ) {
            $sources = $this->configHelper->getAllSources();
            // if multi-stock
            if (count($sources) > 1) {
                foreach ($sources as $source) {
                    $fields[] = 'quantity_multistock_' . $source;
                }
            }
        }
        return $fields;
    }

    /**
     * Set format to export
     *
     * @param string $format export format
     *
     * @return string
     */
    private function setFormat(string $format): string
    {
        return !in_array($format, $this->availableFormats, true) ? LengowFeed::FORMAT_CSV : $format;
    }

    /**
     * Set product ids to export
     *
     * @param boolean|array $productIds product ids to export
     *
     * @return array
     */
    private function setProductIds($productIds): array
    {
        $ids = [];
        if ($productIds) {
            $exportedIds = explode(',', $productIds);
            foreach ($exportedIds as $id) {
                if (is_numeric($id) && $id > 0) {
                    $ids[] = (int) $id;
                }
            }
        }
        return $ids;
    }

    /**
     * Set product types to export
     *
     * @param boolean|array $productTypes product types to export
     *
     * @return array
     */
    private function setProductTypes($productTypes): array
    {
        $types = [];
        if ($productTypes) {
            $exportedTypes = explode(',', $productTypes);
            foreach ($exportedTypes as $type) {
                if (in_array($type, $this->availableProductTypes, true)) {
                    $types[] = $type;
                }
            }
        }
        if (empty($types)) {
            $types = explode(',', $this->configHelper->get(ConfigHelper::EXPORT_PRODUCT_TYPES, $this->storeId));
        }
        return $types;
    }

    /**
     * Set Log output for export
     *
     * @param boolean $logOutput see logs or not
     *
     * @return boolean
     */
    private function setLogOutput(bool $logOutput): bool
    {
        return $this->stream ? false : $logOutput;
    }

    /**
     * Set currency for export
     *
     * @param boolean|string $currency see logs or not
     *
     * @return string
     */
    private function setCurrency($currency): string
    {
        $availableCurrencies = $this->store->getAvailableCurrencyCodes();
        if (!$currency || !in_array($currency, $availableCurrencies, true)) {
            $currency = $this->store->getCurrentCurrencyCode();
        }
        return $currency;
    }

    /**
     * Set export type
     *
     * @param boolean|string $type export type (manual, cron or magento cron)
     *
     * @return string
     */
    private function setType($type): string
    {
        if (!$type) {
            $type = $this->updateExportDate ? self::TYPE_CRON : self::TYPE_MANUAL;
        }
        return $type;
    }

    /**
     * Set the product counter log
     *
     * @param integer $productModulo product modulo
     * @param integer $productCount product counter
     */
    private function setCounterLog(int $productModulo, int $productCount): void
    {
        $logMessage = $this->dataHelper->setLogMessage('%1 product(s) exported', [$productCount]);
        // save 10 logs maximum in database
        if ($productCount % $productModulo === 0) {
            $this->dataHelper->log(DataHelper::CODE_EXPORT, $logMessage);
        }
        if (!$this->stream && $this->logOutput) {
            if ($productCount % 50 === 0) {
                $countMessage = $this->dataHelper->decodeLogMessage($logMessage, false);
                print_r('[Export] ' . $countMessage . '<br />');
            }
            flush();
        }
    }

    /**
     * Get product modulo for counter log
     *
     * @param integer $productTotal number of product to export
     *
     * @return integer
     */
    private function getProductModulo(int $productTotal): int
    {
        $productModulo = (int) ($productTotal / 10);
        return $productModulo < 50 ? 50 : $productModulo;
    }

    /**
     * Get max character size for yaml format
     *
     * @param array $fields list of fields to export
     *
     * @return integer
     */
    private function getMaxCharacterSize(array $fields = []): int
    {
        $maxCharacter = 0;
        foreach ($fields as $field) {
            if (strlen($field) > $maxCharacter) {
                $maxCharacter = strlen($field);
            }
        }
        return $maxCharacter;
    }

    /**
     * Get microtime float
     *
     * @return float
     */
    private function microtimeFloat(): float
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float) $usec + (float) $sec);
    }

    /**
     * Get products collection for export
     *
     * @return ProductCollection
     */
    private function getQuery(): ProductCollection
    {
        // export only specific products types for one store

        $productCollection = $this->productCollectionFactory->create()
            ->setStoreId($this->storeId)
            ->addStoreFilter($this->storeId)
            ->addAttributeToFilter('type_id', ['in' => $this->productTypes]);
        // export only enabled products
        if (!$this->inactive) {
            $productCollection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
        }
        // export only selected products
        if ($this->selection) {
            $productCollection->addAttributeToFilter('lengow_product', 1, 'left');
        }
        // export out of stock products
        if (!$this->outOfStock) {
            try {
                $config = (int) $this->scopeConfig->isSetFlag(CatalogInventoryConfiguration::XML_PATH_MANAGE_STOCK);
                $condition = '({{table}}.`is_in_stock` = 1) '
                    . ' OR IF({{table}}.`use_config_manage_stock` = 1, ' . $config . ', {{table}}.`manage_stock`) = 0';
                $productCollection->joinTable(
                    'cataloginventory_stock_item',
                    'product_id=entity_id',
                    ['qty' => 'qty', 'is_in_stock' => 'is_in_stock'],
                    $condition
                );
            } catch (Exception $e) {
                $this->dataHelper->log(
                    DataHelper::CODE_EXPORT,
                    $this->dataHelper->setLogMessage(
                        'the junction with the %1 table did not work',
                        ['cataloginventory_stock_item']
                    ),
                    $this->logOutput
                );
            }
        }
        // export specific products with id
        if (!empty($this->productIds)) {
            $productCollection->addAttributeToFilter('entity_id', ['in' => $this->productIds]);
        }
        // export with limit & offset
        if ($this->limit > 0) {
            if ($this->offset > 0) {
                $productCollection->getSelect()->limit($this->limit, $this->offset);
            } else {
                $productCollection->getSelect()->limit($this->limit);
            }
        }

        return $productCollection;
    }
}
