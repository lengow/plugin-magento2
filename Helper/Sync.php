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
 * @subpackage  Helper
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Connector as Connector;

class Sync extends AbstractHelper
{
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context Magento context instance
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Get Sync Data (Inscription / Update)
     *
     * @return array
     */
    public function getSyncData()
    {
        //TODO
//        $data = array();
//        $data['domain_name']    = $_SERVER["SERVER_NAME"];
//        $data['token']          = $this->_configHelper->getToken();
//        $data['type']           = 'magento';
//        $data['version']        = Mage::getVersion();
//        $data['plugin_version'] = (string)Mage::getConfig()->getNode()->modules->Lengow_Connector->version;
//        $data['email']          = Mage::getStoreConfig('trans_email/ident_general/email');
//        $data['return_url']     = 'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
//        foreach (Mage::app()->getWebsites() as $website) {
//            foreach ($website->getGroups() as $group) {
//                $stores = $group->getStores();
//                foreach ($stores as $store) {
//                    $export = Mage::getModel('lengow/export', array("store_id" => $store->getId()));
//                    $data['shops'][$store->getId()]['token']                   = $this->_configHelper->getToken($store->getId());
//                    $data['shops'][$store->getId()]['name']                    = $store->getName();
//                    $data['shops'][$store->getId()]['domain']                  = $store->getBaseUrl();
//                    $data['shops'][$store->getId()]['feed_url']                = $this->_dataHelper->getExportUrl($store->getId());
//                    $data['shops'][$store->getId()]['cron_url']                = $this->_dataHelper->getCronUrl();
//                    $data['shops'][$store->getId()]['total_product_number']    = $export->getTotalProduct();
//                    $data['shops'][$store->getId()]['exported_product_number'] = $export->getTotalExportedProduct();
//                    $data['shops'][$store->getId()]['configured']              = $this->checkSyncStore($store->getId());
//                }
//            }
//        }
//        return $data;
    }
}
