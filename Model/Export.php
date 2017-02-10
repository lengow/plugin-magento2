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
use Lengow\Connector\Model\Exception as LengowException;

/**
 * Lengow export
 */
class Export
{
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
    protected $_productTypes = [];

    /**
     * @var array product ids to be exported
     */
    protected $_productIds = [];

    /**
     * @var boolean update export date.
     */
    protected $_updateExportDate;

    /**
     * @var string export type (manual, cron or magento cron)
     */
    protected $_exportType;

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
     * Constructor
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Magento store manager instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DataHelper $dataHelper,
        ConfigHelper $configHelper
    )
    {
        $this->_storeManager = $storeManager;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
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
        // Get store and store id
        $storeId = isset($params['store_id']) ? (int)$params['store_id'] : false;
        $this->_store = $this->_storeManager->getStore($storeId);
        $this->_storeId = $this->_store->getId();
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
        $this->_setFormat(isset($params['format']) ? $params['format'] : 'csv');
        $this->_setProductIds(isset($params['product_ids']) ? $params['product_ids'] : false);
        $this->_setProductTypes(isset($params['product_types']) ? $params['product_types'] : false);
        $this->_setLogOutput(isset($params['log_output']) ? $params['log_output'] : true);
        $this->_setCurrency(isset($params['currency']) ? $params['currency'] : false);
        $this->_setType(isset($params['type']) ? $params['type'] : false);
    }

    /**
     * Set format to export
     *
     * @param string $format export format
     */
    protected function _setFormat($format)
    {
        $this->_format = !in_array($format, $this->_availableFormats) ? 'csv' : $format;
    }

    /**
     * Set product ids to export
     *
     * @param boolean|array $productIds product ids to export
     */
    protected function _setProductIds($productIds)
    {
        if ($productIds) {
            $exportedIds = explode(',', $productIds);
            foreach ($exportedIds as $id) {
                if (is_numeric($id) && $id > 0) {
                    $this->_productIds[] = (int)$id;
                }
            }
        }
    }

    /**
     * Set product types to export
     *
     * @param boolean|array $productTypes product types to export
     */
    protected function _setProductTypes($productTypes)
    {
        if ($productTypes) {
            $exportedTypes = explode(',', $productTypes);
            foreach ($exportedTypes as $type) {
                if (array_key_exists($type, $this->_availableProductTypes)) {
                    $this->_productTypes[] = $type;
                }
            }
        }
        if (count($this->_productTypes) == 0) {
            $this->_productTypes = explode(',', $this->_configHelper->get('product_type', $this->_storeId));
        }
    }

    /**
     * Set Log output for export
     *
     * @param boolean $logOutput see logs or not
     */
    protected function _setLogOutput($logOutput)
    {
        $this->_logOutput = $this->_stream ? false : $logOutput;
    }

    /**
     * Set currency for export
     *
     * @param boolean|string $currency see logs or not
     */
    protected function _setCurrency($currency)
    {
        $this->_currency = $currency ? $currency : $this->_store->getCurrentCurrencyCode();
    }

    /**
     * Set export type
     *
     * @param boolean|string $type export type (manual, cron or magento cron)
     */
    protected function _setType($type)
    {
        if (!$type) {
            $type = $this->_updateExportDate ? 'cron' : 'manual';
        }
        $this->_exportType = $type;
    }
}
