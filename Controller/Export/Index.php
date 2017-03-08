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
 * @subpackage  Controller
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Controller\Export;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\TranslateInterface;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Export;

class Index extends Action
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface Magento store manager instance
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Locale\Resolver Magento locale resolver instance
     */
    protected $_locale;

    /**
     * @var \Magento\Framework\TranslateInterface Magento translate instance
     */
    protected $_translate;

    /**
     * @var \Lengow\Connector\Helper\Security Lengow security helper instance
     */
    protected $_securityHelper;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Model\Export Lengow export instance
     */
    protected $_export;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context Magento action context instance
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Magento store manager instance
     * @param \Magento\Framework\Locale\Resolver $locale Magento locale resolver instance
     * @param \Magento\Framework\TranslateInterface $translate Magento translate instance
     * @param \Lengow\Connector\Helper\Security $securityHelper Lengow security helper instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Model\Export $export Lengow export instance
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Resolver $locale,
        TranslateInterface $translate,
        SecurityHelper $securityHelper,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        Export $export
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_locale = $locale;
        $this->_translate = $translate;
        $this->_securityHelper = $securityHelper;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_export = $export;
    }

    public function execute()
    {
        /**
         * List params
         * string  mode               Number of products exported
         * string  format             Format of exported files ('csv','yaml','xml','json')
         * boolean stream             Stream file (1) or generate a file on server (0)
         * integer offset             Offset of total product
         * integer limit              Limit number of exported product
         * boolean selection          Export product selection (1) or all products (0)
         * boolean out_of_stock       Export out of stock product (1) Export only product in stock (0)
         * string  product_ids        List of product id separate with comma (1,2,3)
         * string  product_types      Type separate with comma (simple,configurable,downloadable,grouped,virtual)
         * boolean inactive           Export inactive product (1) or not (0)
         * string  code               Export a specific store with store code
         * integer store              Export a specific store with store id
         * string  currency           Convert prices with a specific currency
         * string  locale             Translate content with a specific locale
         * boolean log_output         See logs (1) or not (0)
         * boolean update_export_date Change last export date in data base (1) or not (0)
         * boolean get_params         See export parameters and authorized values in json format (1) or not (0)
         */
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        // get params data
        $mode = $this->getRequest()->getParam('mode');
        $getParams = $this->getRequest()->getParam('get_params');
        $format = $this->getRequest()->getParam('format', null);
        $stream = $this->getRequest()->getParam('stream', null);
        $offset = $this->getRequest()->getParam('offset', null);
        $limit = $this->getRequest()->getParam('limit', null);
        $selection = $this->getRequest()->getParam('selection', null);
        $outOfStock = $this->getRequest()->getParam('out_of_stock', null);
        $productIds = $this->getRequest()->getParam('product_ids', null);
        $productTypes = $this->getRequest()->getParam('product_types', null);
        $inactive = $this->getRequest()->getParam('inactive', null);
        $logOutput = $this->getRequest()->getParam('log_output', null);
        $currency = $this->getRequest()->getParam('currency', null);
        $updateExportDate = $this->getRequest()->getParam('update_export_date', null);
        // get store data
        $storeCode = $this->getRequest()->getParam('code', null);
        if (in_array($storeCode, $this->_configHelper->getAllStoreCode())) {
            $storeId = (int)$this->_storeManager->getStore($storeCode)->getId();
        } else {
            $storeId = (int)$this->getRequest()->getParam('store', null);
            if (!in_array($storeId, $this->_configHelper->getAllStoreId())) {
                $storeId = (int)$this->_storeManager->getStore()->getId();
            }
        }
        // get locale data
        if ($locale = $this->getRequest()->getParam('locale', null)) {
            // changing locale works!
            $this->_locale->setLocale($locale);
            // needed to add this
            $this->_translate->setLocale($locale);
            // translation now works
            $this->_translate->loadData('frontend', true);
        }
        if ($this->_securityHelper->checkIp()) {
            try {
                // config store
                $this->_storeManager->setCurrentStore($storeId);
                $params = [
                    'store_id'           => $storeId,
                    'format'             => $format,
                    'product_types'      => $productTypes,
                    'inactive'           => $inactive,
                    'out_of_stock'       => $outOfStock,
                    'selection'          => $selection,
                    'stream'             => $stream,
                    'limit'              => $limit,
                    'offset'             => $offset,
                    'product_ids'        => $productIds,
                    'currency'           => $currency,
                    'update_export_date' => $updateExportDate,
                    'log_output'         => $logOutput,
                ];
                $this->_export->init($params);
                if ($getParams) {
                    $this->getResponse()->setBody($this->_export->getExportParams());
                } elseif ($mode == 'size') {
                    $this->getResponse()->setBody($this->_export->getTotalExportedProduct());
                } elseif ($mode == 'total') {
                    $this->getResponse()->setBody($this->_export->getTotalProduct());
                } else {
                    $this->_export->exec();
                    exit(0);
                }
            } catch (\Exception $e) {
                $errorMessage = '[Magento error] "'.$e->getMessage().'" '.$e->getFile().' line '.$e->getLine();
                $this->_dataHelper->log('Export', $errorMessage);
                $this->getResponse()->setStatusHeader(500, '1.1', 'Internal Server Error');
                $this->getResponse()->setBody($errorMessage);
            }
        } else {
            $this->getResponse()->setStatusHeader(403, '1.1', 'Forbidden');
            $this->getResponse()->setBody(__('unauthorised IP: %1', [$this->_securityHelper->getRemoteIp()]));
        }
    }
}
