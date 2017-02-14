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

use Magento\Store\Model\StoreManagerInterface;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\CatalogInventory\Model\Configuration as CatalogInventoryConfiguration;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

/**
 * Lengow export
 */
class Export
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface Magento store manager instance
     */
    protected $_storeManager;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface Magento scope config instance
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status Magento product status instance
     */
    protected $_productStatus;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory Magento product collection factory
     */
    protected $_productCollectionFactory;

    /**
     * @var array available formats for export
     */
    protected $_availableFormats = [
        'csv',
        'json',
        'yaml',
        'xml',
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
     * @var \Magento\Store\Model\Store\Interceptor Magento store instance
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
     * Constructor
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Magento store manager instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig Magento scope config instance
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus Magento product status instance
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        ScopeConfigInterface $scopeConfig,
        ProductStatus $productStatus,
        ProductCollectionFactory $productCollectionFactory
    )
    {
        $this->_storeManager = $storeManager;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_productStatus = $productStatus;
        $this->_productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Make a new Lengow Export
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
        $this->_store = $this->_storeManager->getStore($this->_storeId);
        $this->_limit = isset($params['limit']) ? (int) $params['limit'] : 0;
        $this->_offset = isset($params['offset']) ? (int) $params['offset'] : 0;
        $this->_stream = isset($params['stream'])
            ? (bool)$params['stream']
            : !(bool)$this->_configHelper->get('file_enable', $this->_storeId);
        $this->_selection = isset( $params['selection'] )
            ? (bool)$params['selection']
            : (bool)$this->_configHelper->get('selection_enable', $this->_storeId);
        $this->_inactive = isset( $params['inactive'] )
            ? (bool)$params['inactive']
            : (bool)$this->_configHelper->get('product_status', $this->_storeId);
        $this->_outOfStock = isset( $params['out_of_stock'] ) ? $params['out_of_stock'] : true;
        $this->_updateExportDate = isset($params['update_export_date']) ? (bool)$params['update_export_date'] : true;
        $this->_format = $this->_setFormat(isset($params['format']) ? $params['format'] : 'csv');
        $this->_productIds = $this->_setProductIds(isset($params['product_ids']) ? $params['product_ids'] : false);
        $this->_productTypes = $this->_setProductTypes(
            isset($params['product_types']) ? $params['product_types'] : false
        );
        $this->_logOutput = $this->_setLogOutput(isset($params['log_output']) ? $params['log_output'] : true);
        $this->_currency = $this->_setCurrency(isset($params['currency']) ? $params['currency'] : false);
        $this->_exportType = $this->_setType(isset($params['type']) ? $params['type'] : false);
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
        return !in_array($format, $this->_availableFormats) ? 'csv' : $format;
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
        if (count($types) == 0) {
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
            $type = $this->_updateExportDate ? 'cron' : 'manual';
        }
        return $type;
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
     * Get products collection for export
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _getQuery()
    {
        // Export only specific products types for one store
        $productCollection = $this->_productCollectionFactory->create()
            ->setStoreId($this->_storeId)
            ->addStoreFilter($this->_storeId)
            ->addAttributeToFilter('type_id', ['in' => $this->_productTypes]);
        // Export only enabled products
        if (!$this->_inactive) {
            $productCollection->addAttributeToFilter('status', ['in' => $this->_productStatus->getVisibleStatusIds()]);
        }
        // Export only selected products
        if ($this->_selection) {
            $productCollection->addAttributeToFilter('lengow_product', 1);
        }
        // Export out of stock products
        if (!$this->_outOfStock) {
            $config = (int)$this->_scopeConfig->isSetFlag(CatalogInventoryConfiguration::XML_PATH_MANAGE_STOCK);
            $condition = '({{table}}.`is_in_stock` = 1) '
                .' OR IF({{table}}.`use_config_manage_stock` = 1, '.$config.', {{table}}.`manage_stock`) = 0';
            $productCollection->joinTable(
                'cataloginventory_stock_item',
                'product_id=entity_id',
                ['qty' => 'qty', 'is_in_stock' => 'is_in_stock'],
                $condition
            );
        }
        // Export specific products with id
        if (count($this->_productIds) > 0) {
            $productCollection->addAttributeToFilter('entity_id', ['in' => $this->_productIds]);
        }
        // Export with limit & offset
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
