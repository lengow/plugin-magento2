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
use Magento\Framework\Locale\Resolver as Locale;
use Magento\Framework\TranslateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Model\Export as LengowExport;

class Index extends Action
{
    /**
     * @var Locale Magento locale resolver instance
     */
    protected $_locale;

    /**
     * @var TranslateInterface Magento translate instance
     */
    protected $_translate;

    /**
     * @var StoreManagerInterface Magento store manager instance
     */
    protected $_storeManager;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var SecurityHelper Lengow security helper instance
     */
    protected $_securityHelper;

    /**
     * @var LengowExport Lengow export instance
     */
    protected $_export;

    /**
     * Constructor
     *
     * @param Context $context Magento action context instance
     * @param StoreManagerInterface $storeManager Magento store manager instance
     * @param Locale $locale Magento locale resolver instance
     * @param TranslateInterface $translate Magento translate instance
     * @param SecurityHelper $securityHelper Lengow security helper instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param LengowExport $export Lengow export instance
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Locale $locale,
        TranslateInterface $translate,
        SecurityHelper $securityHelper,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        LengowExport $export
    ) {
        $this->_storeManager = $storeManager;
        $this->_locale = $locale;
        $this->_translate = $translate;
        $this->_securityHelper = $securityHelper;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_export = $export;
        parent::__construct($context);
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
         * string  language           Translate content with a specific locale
         * boolean log_output         See logs (1) or not (0)
         * boolean update_export_date Change last export date in data base (1) or not (0)
         * boolean get_params         See export parameters and authorized values in json format (1) or not (0)
         */
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        // get params data
        $mode = $this->getRequest()->getParam(LengowExport::PARAM_MODE);
        $token = $this->getRequest()->getParam(LengowExport::PARAM_TOKEN);
        $getParams = $this->getRequest()->getParam(LengowExport::PARAM_GET_PARAMS);
        $format = $this->getRequest()->getParam(LengowExport::PARAM_FORMAT);
        $stream = $this->getRequest()->getParam(LengowExport::PARAM_STREAM);
        $offset = $this->getRequest()->getParam(LengowExport::PARAM_OFFSET);
        $limit = $this->getRequest()->getParam(LengowExport::PARAM_LIMIT);
        $selection = $this->getRequest()->getParam(LengowExport::PARAM_SELECTION);
        $outOfStock = $this->getRequest()->getParam(LengowExport::PARAM_OUT_OF_STOCK);
        $productIds = $this->getRequest()->getParam(LengowExport::PARAM_PRODUCT_IDS);
        $productTypes = $this->getRequest()->getParam(LengowExport::PARAM_PRODUCT_TYPES);
        $inactive = $this->getRequest()->getParam(LengowExport::PARAM_INACTIVE);
        $logOutput = $this->getRequest()->getParam(LengowExport::PARAM_LOG_OUTPUT);
        $currency = $this->getRequest()->getParam(LengowExport::PARAM_CURRENCY);
        $updateExportDate = $this->getRequest()->getParam(LengowExport::PARAM_UPDATE_EXPORT_DATE);
        // get store data
        $storeCode = $this->getRequest()->getParam(LengowExport::PARAM_CODE);
        if (in_array($storeCode, $this->_configHelper->getAllStoreCode(), true)) {
            $storeId = $this->_storeManager->getStore($storeCode)->getId();
        } else {
            $storeId = (int) $this->getRequest()->getParam(LengowExport::PARAM_STORE);
            if (!in_array($storeId, $this->_configHelper->getAllStoreId(), true)) {
                $storeId = $this->_storeManager->getStore()->getId();
            }
        }
        // get locale data
        $language = $this->getRequest()->getParam(LengowExport::PARAM_LEGACY_LANGUAGE) ?: $this->getRequest()
            ->getParam(LengowExport::PARAM_LANGUAGE);
        if ($language) {
            // changing locale works!
            $this->_locale->setLocale($language);
            // needed to add this
            $this->_translate->setLocale($language);
            // translation now works
            $this->_translate->loadData('frontend', true);
        }
        if ($this->_securityHelper->checkWebserviceAccess($token, $storeId)) {
            try {
                // config store
                $this->_storeManager->setCurrentStore($storeId);
                $params = [
                    LengowExport::PARAM_STORE_ID => $storeId,
                    LengowExport::PARAM_FORMAT => $format,
                    LengowExport::PARAM_PRODUCT_TYPES => $productTypes,
                    LengowExport::PARAM_INACTIVE => $inactive,
                    LengowExport::PARAM_OUT_OF_STOCK => $outOfStock,
                    LengowExport::PARAM_SELECTION => $selection,
                    LengowExport::PARAM_STREAM => $stream,
                    LengowExport::PARAM_LIMIT => $limit,
                    LengowExport::PARAM_OFFSET => $offset,
                    LengowExport::PARAM_PRODUCT_IDS => $productIds,
                    LengowExport::PARAM_CURRENCY => $currency,
                    LengowExport::PARAM_UPDATE_EXPORT_DATE => $updateExportDate,
                    LengowExport::PARAM_LOG_OUTPUT => $logOutput,
                ];
                $this->_export->init($params);
                if ($getParams) {
                    $this->getResponse()->setBody($this->_export->getExportParams());
                } elseif ($mode === 'size') {
                    $this->getResponse()->setBody((string) $this->_export->getTotalExportProduct());
                } elseif ($mode === 'total') {
                    $this->getResponse()->setBody((string) $this->_export->getTotalProduct());
                } else {
                    $this->_export->exec();
                }
            } catch (\Exception $e) {
                $errorMessage = '[Magento error]: "' . $e->getMessage() . '" ' . $e->getFile() . ' | ' . $e->getLine();
                $this->_dataHelper->log(DataHelper::CODE_EXPORT, $errorMessage);
                $this->getResponse()->setStatusHeader(500, '1.1', 'Internal Server Error');
                $this->getResponse()->setBody($errorMessage);
            }
        } else {
            if ($this->_configHelper->get(ConfigHelper::AUTHORIZED_IP_ENABLED)) {
                $errorMessage = __('unauthorised IP: %1', [$this->_securityHelper->getRemoteIp()]);
            } else {
                $errorMessage = $token !== ''
                    ? __('unauthorised access for this token: %1', [$token])
                    : __('unauthorised access: token parameter is empty');
            }
            $this->getResponse()->setStatusHeader(403, '1.1', 'Forbidden');
            $this->getResponse()->setBody($errorMessage->__toString());
        }
    }
}
