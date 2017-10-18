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

namespace Lengow\Connector\Controller\Cron;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Model\Import as ImportModel;

/**
 * CronController
 */
class Index extends Action
{
    /**
     * @var \Magento\Framework\Json\Helper\Data Magento json helper instance
     */
    protected $_jsonHelper;

    /**
     * @var \Lengow\Connector\Helper\Security Lengow security helper instance
     */
    protected $_securityHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Helper\Sync Lengow sync helper instance
     */
    protected $_syncHelper;

    /**
     * @var \Lengow\Connector\Model\Import Lengow import model instance
     */
    protected $_importModel;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context Magento action context instance
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper Magento json helper instance
     * @param \Lengow\Connector\Helper\Security $securityHelper Lengow security helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Helper\Sync $syncHelper Lengow sync helper instance
     * @param \Lengow\Connector\Model\Import $importModel Lengow import model instance
     */
    public function __construct(
        Context $context,
        JsonHelper $jsonHelper,
        SecurityHelper $securityHelper,
        ConfigHelper $configHelper,
        SyncHelper $syncHelper,
        ImportModel $importModel
    )
    {
        parent::__construct($context);
        $this->_jsonHelper = $jsonHelper;
        $this->_securityHelper = $securityHelper;
        $this->_configHelper = $configHelper;
        $this->_syncHelper = $syncHelper;
        $this->_importModel = $importModel;
    }

    /**
     * Cron Process (Import orders, check actions and send stats)
     */
    public function execute()
    {
        /**
         * List params
         * string  sync                Number of products exported
         * integer days                Import period
         * integer limit               Number of orders to import
         * integer store_id            Store id to import
         * string  $marketplace_sku    Lengow marketplace order id to import
         * string  marketplace_name    Lengow marketplace name to import
         * integer delivery_address_id Lengow delivery address id to import
         * boolean preprod_mode        Activate preprod mode
         * boolean log_output          See logs (1) or not (0)
         * boolean get_sync            See synchronisation parameters in json format (1) or not (0)
         */
        $token = $this->getRequest()->getParam('token');
        if ($this->_securityHelper->checkWebserviceAccess($token)) {
            // get all store datas for synchronisation with Lengow
            if ($this->getRequest()->getParam('get_sync') == 1) {
                $storeDatas = $this->_syncHelper->getSyncData();
                $this->getResponse()->setBody($this->_jsonHelper->jsonEncode($storeDatas));
            } else {
                // get sync action if exists
                $sync = $this->getRequest()->getParam('sync');
                // sync catalogs id between Lengow and Magento
                if (!$sync || $sync === 'catalog') {
                    $this->_syncHelper->syncCatalog();
                }
                // sync orders between Lengow and Magento
                if (is_null($sync) || $sync === 'order') {
                    // array of params for import order
                    $params = [];
                    // check if the GET parameters are available
                    if (!is_null($this->getRequest()->getParam('preprod_mode'))) {
                        $params['preprod_mode'] = (bool)$this->getRequest()->getParam('preprod_mode');
                    }
                    if (!is_null($this->getRequest()->getParam('log_output'))) {
                        $params['log_output'] = (bool)$this->getRequest()->getParam('log_output');
                    }
                    if (!is_null($this->getRequest()->getParam('days'))) {
                        $params['days'] = (int)$this->getRequest()->getParam('days');
                    }
                    if (!is_null($this->getRequest()->getParam('limit'))) {
                        $params['limit'] = (int)$this->getRequest()->getParam('limit');
                    }
                    if (!is_null($this->getRequest()->getParam('marketplace_sku'))) {
                        $params['marketplace_sku'] = (string)$this->getRequest()->getParam('marketplace_sku');
                    }
                    if (!is_null($this->getRequest()->getParam('marketplace_name'))) {
                        $params['marketplace_name'] = (string)$this->getRequest()->getParam('marketplace_name');
                    }
                    if (!is_null($this->getRequest()->getParam('delivery_address_id'))) {
                        $params['delivery_address_id'] = (int)$this->getRequest()->getParam('delivery_address_id');
                    }
                    if (!is_null($this->getRequest()->getParam('store_id'))) {
                        $params['store_id'] = (int)$this->getRequest()->getParam('store_id');
                    }
                    $params['type'] = 'cron';
                    // Import orders
                    $this->_importModel->init($params);
                    $this->_importModel->exec();
                }
                // sync action between Lengow and Magento
                if (is_null($sync) || $sync === 'action') {
                    //TODO actions
                }
                // sync options between Lengow and Magento
                if (is_null($sync) || $sync === 'option') {
                    //TODO options
                }
                // sync option is not valid
                if ($sync && !$this->_syncHelper->isSyncAction($sync)) {
                    $this->getResponse()->setStatusHeader('400', '1.1', 'Bad Request');
                    $this->getResponse()->setBody(__('Action: %1 is not a valid action', [$sync]));
                }
            }
        } else {
            if ((bool)$this->_configHelper->get('ip_enable')) {
                $errorMessage = __('unauthorised IP: %1', [$this->_securityHelper->getRemoteIp()]);
            } else {
                $errorMessage = strlen($token) > 0
                    ? __('unauthorised access for this token: %1', [$token])
                    : __('unauthorised access: token parameter is empty');
            }
            $this->getResponse()->setStatusHeader('403', '1.1', 'Forbidden');
            $this->getResponse()->setBody($errorMessage);
        }
    }
}
