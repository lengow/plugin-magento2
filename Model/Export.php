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

use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Model\Configuration as CatalogInventoryConfiguration;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
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

/**
 * Lengow export
 */
class Export
{
    /**
     * @var string manual export type
     */
    const TYPE_MANUAL = 'manual';

    /**
     * @var string cron export type
     */
    const TYPE_CRON = 'cron';

    /**
     * @var string Magento cron export type
     */
    const TYPE_MAGENTO_CRON = 'magento cron';

    /**
     * @var StoreManagerInterface Magento store manager instance
     */
    protected $_storeManager;

    /**
     * @var DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var ScopeConfigInterface Magento scope config instance
     */
    protected $_scopeConfig;

    /**
     * @var ProductStatus Magento product status instance
     */
    protected $_productStatus;

    /**
     * @var ProductCollectionFactory Magento product collection factory
     */
    protected $_productCollectionFactory;

    /**
     * @var JsonHelper Magento json helper instance
     */
    protected $_jsonHelper;

    /**
     * @var WebsiteFactory Magento website factory instance
     */
    protected $_websiteFactory;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var LengowFeedFactory Lengow feed factory instance
     */
    protected $_feedFactory;

    /**
     * @var LengowProductFactory Lengow product factory instance
     */
    protected $_productFactory;

    /**
     * @var array all available params for export
     */
    protected $_exportParams = [
        'mode',
        'format',
        'stream',
        'offset',
        'limit',
        'selection',
        'out_of_stock',
        'product_ids',
        'product_types',
        'product_status',
        'store',
        'code',
        'currency',
        'locale',
        'legacy_fields',
        'log_output',
        'update_export_date',
        'get_params',
    ];

    /**
     * @var array available formats for export
     */
    protected $_availableFormats = [
        LengowFeed::FORMAT_CSV,
        LengowFeed::FORMAT_YAML,
        LengowFeed::FORMAT_XML,
        LengowFeed::FORMAT_JSON,
    ];

    /**
     * @var array available formats for export
     */
    protected $_availableProductTypes = [
        'configurable',
        'simple',
        'downloadable',
        'grouped',
        'virtual',
    ];

    /**
     * @var array default fields for export
     */
    protected $_defaultFields = [
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
    protected $_store;

    /**
     * @var integer Magento store id
     */
    protected $_storeId;

    /**
     * @var integer amount of products to export
     */
    protected $_limit;

    /**
     * @var integer offset of total product
     */
    protected $_offset;

    /**
     * @var string format to return
     */
    protected $_format;

    /**
     * @var boolean stream return
     */
    protected $_stream;

    /**
     * @var string currency iso code for conversion
     */
    protected $_currency;

    /**
     * @var boolean export Lengow selection
     */
    protected $_selection;

    /**
     * @var boolean export out of stock product
     */
    protected $_outOfStock;

    /**
     * @var boolean include active products
     */
    protected $_inactive;

    /**
     * @var boolean see log or not
     */
    protected $_logOutput;

    /**
     * @var array export product types
     */
    protected $_productTypes;

    /**
     * @var array product ids to be exported
     */
    protected $_productIds;

    /**
     * @var boolean update export date.
     */
    protected $_updateExportDate;

    /**
     * @var string export type (manual, cron or magento cron)
     */
    protected $_exportType;

    /**
     * @var boolean get params available.
     */
    protected $_getParams;

    /**
     * @var SourceRepositoryInterface
     */
    protected $sourceRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

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
     * @param SourceRepositoryInterface $sourceRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
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
        SourceRepositoryInterface $sourceRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        $this->_storeManager = $storeManager;
        $this->_dateTime = $dateTime;
        $this->_scopeConfig = $scopeConfig;
        $this->_productStatus = $productStatus;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_jsonHelper = $jsonHelper;
        $this->_websiteFactory = $websiteFactory;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_feedFactory = $feedFactory;
        $this->_productFactory = $productFactory;
        $this->sourceRepository = $sourceRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
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
    public function init($params)
    {
        $this->_storeId = isset($params['store_id']) ? (int)$params['store_id'] : 0;
        try {
            $this->_store = $this->_storeManager->getStore($this->_storeId);
        } catch (\Exception $e) {
            $this->_store = $this->_storeManager->getDefaultStoreView();
        }
        $this->_limit = isset($params['limit']) ? (int)$params['limit'] : 0;
        $this->_offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        $this->_stream = isset($params['stream'])
            ? (bool)$params['stream']
            : !(bool)$this->_configHelper->get('file_enable', $this->_storeId);
        $this->_selection = isset($params['selection'])
            ? (bool)$params['selection']
            : (bool)$this->_configHelper->get('selection_enable', $this->_storeId);
        $this->_inactive = isset($params['inactive'])
            ? (bool)$params['inactive']
            : (bool)$this->_configHelper->get('product_status', $this->_storeId);
        $this->_outOfStock = isset($params['out_of_stock']) ? $params['out_of_stock'] : true;
        $this->_updateExportDate = isset($params['update_export_date']) ? (bool)$params['update_export_date'] : true;
        $this->_format = $this->_setFormat(isset($params['format']) ? $params['format'] : LengowFeed::FORMAT_CSV);
        $this->_productIds = $this->_setProductIds(isset($params['product_ids']) ? $params['product_ids'] : false);
        $this->_productTypes = $this->_setProductTypes(
            isset($params['product_types']) ? $params['product_types'] : false
        );
        $this->_logOutput = $this->_setLogOutput(isset($params['log_output']) ? $params['log_output'] : true);
        $this->_currency = $this->_setCurrency(isset($params['currency']) ? $params['currency'] : false);
        $this->_exportType = $this->_setType(isset($params['type']) ? $params['type'] : false);
        $this->_getParams = isset($params['get_params']) ? (bool)$params['get_params'] : false;
    }

    /**
     * Get total available products
     *
     * @return integer
     **/
    public function getTotalProduct()
    {
        $productCollection = $this->_productCollectionFactory->create()
            ->setStoreId($this->_storeId)
            ->addStoreFilter($this->_storeId)
            ->addAttributeToFilter('type_id', ['nlike' => 'bundle']);
        return $productCollection->getSize();
    }

    /**
     * Get total exported products
     *
     * @return integer
     **/
    public function getTotalExportedProduct()
    {
        $productCollection = $this->_getQuery();
        return $productCollection->getSize();
    }

    /**
     * Execute the export
     **/
    public function exec()
    {
        try {
            // start timer
            $timeStart = $this->_microtimeFloat();
            // clean logs
            $this->_dataHelper->cleanLog();
            $this->_dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->_dataHelper->setLogMessage('## start %1 export ##', [$this->_exportType]),
                $this->_logOutput
            );
            $this->_dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->_dataHelper->setLogMessage(
                    'start export in store %1 (%2)',
                    [
                        $this->_store->getName(),
                        $this->_storeId,
                    ]
                ),
                $this->_logOutput
            );
            // get fields to export
            $fields = $this->_getFields();
            // get products to be exported
            $productCollection = $this->_getQuery();
            $products = $productCollection->getData();
            $this->_dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->_dataHelper->setLogMessage('%1 product(s) found', [count($products)]),
                $this->_logOutput
            );
            $this->_export($products, $fields);
            if ($this->_updateExportDate) {
                $this->_configHelper->set('last_export', $this->_dateTime->gmtTimestamp(), $this->_storeId);
            }
            $timeEnd = $this->_microtimeFloat();
            $this->_dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->_dataHelper->setLogMessage('memory usage: %1 Mb', [round(memory_get_usage() / 1000000, 2)]),
                $this->_logOutput
            );
            $this->_dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->_dataHelper->setLogMessage('execution time: %1 seconds', [round($timeEnd - $timeStart, 4)]),
                $this->_logOutput
            );
            $this->_dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->_dataHelper->setLogMessage('## end %1 export ##', [$this->_exportType]),
                $this->_logOutput
            );
        } catch (LengowException $e) {
            $errorMessage = $e->getMessage();
        } catch (\Exception $e) {
            $errorMessage = 'Magento error: "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
        }
        if (isset($errorMessage)) {
            $decodedMessage = $this->_dataHelper->decodeLogMessage($errorMessage, false);
            $this->_dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->_dataHelper->setLogMessage('export failed - %1', [$decodedMessage]),
                $this->_logOutput
            );
        }
    }

    /**
     * Get all sources options
     *
     * @return mixed
     */
    public function getAllSources()
    {
        $options = [];
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder->create();
        $sources = $this->sourceRepository->getList($searchCriteria)->getItems();
        foreach ($sources as $source) {
            $options[] = $source->getSourceCode();
        }
        return $options;
    }

    /**
     * Check if $field is a custom multi-stock field
     *
     * @param $field  field to compare
     * @param $sources source list
     *
     * @return bool
     */
    protected function compareSource($field, $sources) {
        foreach ($sources as $source) {
            if (strcmp($field, 'quantity_' . $source) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Export products
     *
     * @param array $products list of products to be exported
     * @param array $fields list of fields to export
     *
     * @throws \Exception|LengowException
     */
    protected function _export($products, $fields)
    {
        $isFirst = true;
        $productCount = 0;
        // get modulo for export counter
        $productModulo = $this->_getProductModulo(count($products));
        // get all stock sources
        $sources = $this->getAllSources();
        // if multi-stock
        if (count($sources) > 1) {
            foreach ($sources as $source) {
                $fields[] = 'quantity_' . $source;
            }
        }
        // get the maximum of character for yaml format
        $maxCharacter = $this->_getMaxCharacterSize($fields);
        // init product to export
        $lengowProduct = $this->_productFactory->create();
        $lengowProduct->init([
            'store' => $this->_store,
            'currency' => $this->_currency,
            'parentFields' => $this->_configHelper->getParentSelectedAttributes($this->_storeId)
        ]);
        // init feed to export
        $feed = $this->_feedFactory->create();
        $feed->init(
            [
                'stream' => $this->_stream,
                'format' => $this->_format,
                'store_code' => $this->_store->getCode(),
            ]
        );
        $feed->write(LengowFeed::HEADER, $fields);
        foreach ($products as $product) {
            $productData = [];
            $lengowProduct->load(
                [
                    'product_id' => (int)$product['entity_id'],
                    'product_type' => $product['type_id'],
                ]
            );
            if (!$this->_inactive && !$lengowProduct->isEnableForExport()) {
                $lengowProduct->clean();
                continue;
            }
            foreach ($fields as $field) {
                if (isset($this->_defaultFields[$field])) {
                    $productData[$field] = $lengowProduct->getData($this->_defaultFields[$field]);
                } else {
                    // case multi-stock
                    if ($this->compareSource($field, $sources)) {
                        $quantities = $lengowProduct->getSourceItemDetailBySKU($lengowProduct->getData('sku'));
                        foreach ($quantities as $source) {
                            // if source is enabled & is the one
                            if (('quantity_' . $source['source_code']) === $field && $source['status']) {
                                $productData[$field] = $source['quantity'];
                                break;
                            }
                        }
                        if (empty($productData[$field])) {
                            $productData[$field] = 0;
                        }
                    } else {
                        // Default
                        $productData[$field] = $lengowProduct->getData($field);
                    }
                }
            }
            // write product data
            $feed->write(LengowFeed::BODY, $productData, $isFirst, $maxCharacter);
            $productCount++;
            $this->_setCounterLog($productModulo, $productCount);
            // clean data for next product
            $lengowProduct->clean();
            unset($productData);
            $isFirst = false;
        }
        $success = $feed->end();
        if (!$success) {
            throw new LengowException(
                $this->_dataHelper->setLogMessage('unable to access the folder %1', [$feed->getFolderPath()])
            );
        }
        // product counter
        $counters = $lengowProduct->getCounters();
        $this->_dataHelper->log(
            DataHelper::CODE_EXPORT,
            $this->_dataHelper->setLogMessage(
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
            $this->_logOutput
        );
        // warning for simple product associated with configurable products disabled
        if ($counters['simple_disabled'] > 0) {
            $this->_dataHelper->log(
                DataHelper::CODE_EXPORT,
                $this->_dataHelper->setLogMessage(
                    'WARNING! %1 simple product(s) associated with configurable products are disabled',
                    [$counters['simple_disabled']]
                ),
                $this->_logOutput
            );
        }
        // link generation
        if (!$this->_stream) {
            $feedUrl = $feed->getUrl();
            if ($feedUrl) {
                $this->_dataHelper->log(
                    DataHelper::CODE_EXPORT,
                    $this->_dataHelper->setLogMessage(
                        'the export for the store %1 (%2) generated the following file: %3',
                        [
                            $this->_store->getName(),
                            $this->_storeId,
                            $feedUrl,
                        ]
                    ),
                    $this->_logOutput
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
    public function getExportParams()
    {
        $params = [];
        $availableStores = [];
        $availableCodes = [];
        $availableCurrencies = [];
        $availableLanguages = [];
        foreach ($this->_websiteFactory->create()->getCollection() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $availableStores[] = $store->getId();
                    $availableCodes[] = $store->getCode();
                    $currencyCodes = $store->getAvailableCurrencyCodes();
                    foreach ($currencyCodes as $currencyCode) {
                        if (!in_array($currencyCode, $availableCurrencies)) {
                            $availableCurrencies[] = $currencyCode;
                        }
                    }
                    $storeLanguage = $this->_scopeConfig->getValue(
                        'general/locale/code',
                        ScopeInterface::SCOPE_STORE,
                        $store->getId()
                    );
                    if (!in_array($storeLanguage, $availableLanguages)) {
                        $availableLanguages[] = $storeLanguage;
                    }
                }
            }
        }
        foreach ($this->_exportParams as $param) {
            switch ($param) {
                case 'mode':
                    $authorizedValue = ['size', 'total'];
                    $type = 'string';
                    $example = 'size';
                    break;
                case 'format':
                    $authorizedValue = $this->_availableFormats;
                    $type = 'string';
                    $example = LengowFeed::FORMAT_CSV;
                    break;
                case 'store':
                    $authorizedValue = $availableStores;
                    $type = 'integer';
                    $example = 1;
                    break;
                case 'code':
                    $authorizedValue = $availableCodes;
                    $type = 'string';
                    $example = 'french';
                    break;
                case 'currency':
                    $authorizedValue = $availableCurrencies;
                    $type = 'string';
                    $example = 'EUR';
                    break;
                case 'locale':
                    $authorizedValue = $availableLanguages;
                    $type = 'string';
                    $example = 'fr_FR';
                    break;
                case 'offset':
                case 'limit':
                    $authorizedValue = 'all integers';
                    $type = 'integer';
                    $example = 100;
                    break;
                case 'product_ids':
                    $authorizedValue = 'all integers';
                    $type = 'string';
                    $example = '101,108,215';
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
        return $this->_jsonHelper->jsonEncode($params);
    }

    /**
     * Get fields to export
     *
     * @return array
     */
    protected function _getFields()
    {
        $fields = [];
        foreach ($this->_defaultFields as $key => $defaultField) {
            $fields[] = $key;
        }
        $selectedAttributes = $this->_configHelper->getSelectedAttributes($this->_storeId);
        foreach ($selectedAttributes as $selectedAttribute) {
            if (!in_array($selectedAttribute, $fields)) {
                $fields[] = $selectedAttribute;
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
    protected function _setFormat($format)
    {
        return !in_array($format, $this->_availableFormats) ? LengowFeed::FORMAT_CSV : $format;
    }

    /**
     * Set product ids to export
     *
     * @param boolean|array $productIds product ids to export
     *
     * @return array
     */
    protected function _setProductIds($productIds)
    {
        $ids = [];
        if ($productIds) {
            $exportedIds = explode(',', $productIds);
            foreach ($exportedIds as $id) {
                if (is_numeric($id) && $id > 0) {
                    $ids[] = (int)$id;
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
    protected function _setProductTypes($productTypes)
    {
        $types = [];
        if ($productTypes) {
            $exportedTypes = explode(',', $productTypes);
            foreach ($exportedTypes as $type) {
                if (in_array($type, $this->_availableProductTypes)) {
                    $types[] = $type;
                }
            }
        }
        if (empty($types)) {
            $types = explode(',', $this->_configHelper->get('product_type', $this->_storeId));
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
    protected function _setLogOutput($logOutput)
    {
        return $this->_stream ? false : $logOutput;
    }

    /**
     * Set currency for export
     *
     * @param boolean|string $currency see logs or not
     *
     * @return string
     */
    protected function _setCurrency($currency)
    {
        $availableCurrencies = $this->_store->getAvailableCurrencyCodes();
        if (!$currency || !in_array($currency, $availableCurrencies)) {
            $currency = $this->_store->getCurrentCurrencyCode();
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
    protected function _setType($type)
    {
        if (!$type) {
            $type = $this->_updateExportDate ? self::TYPE_CRON : self::TYPE_MANUAL;
        }
        return $type;
    }

    /**
     * Set the product counter log
     *
     * @param integer $productModulo product modulo
     * @param integer $productCount product counter
     *
     */
    protected function _setCounterLog($productModulo, $productCount)
    {
        $logMessage = $this->_dataHelper->setLogMessage('%1 product(s) exported', [$productCount]);
        // save 10 logs maximum in database
        if ($productCount % $productModulo === 0) {
            $this->_dataHelper->log(DataHelper::CODE_EXPORT, $logMessage);
        }
        if (!$this->_stream && $this->_logOutput) {
            if ($productCount % 50 === 0) {
                $countMessage = $this->_dataHelper->decodeLogMessage($logMessage, false);
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
    protected function _getProductModulo($productTotal)
    {
        $productModulo = (int)($productTotal / 10);
        return $productModulo < 50 ? 50 : $productModulo;
    }

    /**
     * Get max character size for yaml format
     *
     * @param array $fields list of fields to export
     *
     * @return integer
     */
    protected function _getMaxCharacterSize($fields)
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
     */
    protected function _microtimeFloat()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * Get products collection for export
     *
     * @return ProductCollection
     */
    protected function _getQuery()
    {
        // export only specific products types for one store
        $productCollection = $this->_productCollectionFactory->create()
            ->setStoreId($this->_storeId)
            ->addStoreFilter($this->_storeId)
            ->addAttributeToFilter('type_id', ['in' => $this->_productTypes]);
        // export only enabled products
        if (!$this->_inactive) {
            $productCollection->addAttributeToFilter('status', ['in' => $this->_productStatus->getVisibleStatusIds()]);
        }
        // export only selected products
        if ($this->_selection) {
            $productCollection->addAttributeToFilter('lengow_product', 1, 'left');
        }
        // export out of stock products
        if (!$this->_outOfStock) {
            try {
                $config = (int)$this->_scopeConfig->isSetFlag(CatalogInventoryConfiguration::XML_PATH_MANAGE_STOCK);
                $condition = '({{table}}.`is_in_stock` = 1) '
                    . ' OR IF({{table}}.`use_config_manage_stock` = 1, ' . $config . ', {{table}}.`manage_stock`) = 0';
                $productCollection->joinTable(
                    'cataloginventory_stock_item',
                    'product_id=entity_id',
                    ['qty' => 'qty', 'is_in_stock' => 'is_in_stock'],
                    $condition
                );
            } catch (\Exception $e) {
                $this->_dataHelper->log(
                    DataHelper::CODE_EXPORT,
                    $this->_dataHelper->setLogMessage(
                        'the junction with the %1 table did not work',
                        ['cataloginventory_stock_item']
                    ),
                    $this->_logOutput
                );
            }
        }
        // export specific products with id
        if (!empty($this->_productIds)) {
            $productCollection->addAttributeToFilter('entity_id', ['in' => $this->_productIds]);
        }
        // export with limit & offset
        if ($this->_limit > 0) {
            if ($this->_offset > 0) {
                $productCollection->getSelect()->limit($this->_limit, $this->_offset);
            } else {
                $productCollection->getSelect()->limit($this->_limit);
            }
        }
        return $productCollection;
    }
}
