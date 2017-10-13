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
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface as PriceCurrency;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Model\Connector as Connector;
use Lengow\Connector\Model\Export as Export;

class Sync extends AbstractHelper
{
    /**
     * @var \Magento\Framework\Json\Helper\Data Magento json helper instance
     */
    protected $_jsonHelper;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface Magento price currency instance
     */
    protected $_priceCurrency;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Helper\Security Lengow security helper instance
     */
    protected $_securityHelper;

    /**
     * @var \Lengow\Connector\Model\Connector Lengow connector instance
     */
    protected $_connector;

    /**
     * @var \Lengow\Connector\Model\Export Lengow export instance
     */
    protected $_export;

    /**
     * @var integer cache time for statistic, account status and cms options
     */
    protected $_cacheTime = 18000;

    /**
     * @var array valid sync actions
     */
    protected $_syncActions = [
        'order',
        'action',
        'catalog',
        'option'
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context Magento context instance
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper Magento json helper instance
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency Magento price currency instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Helper\Security $securityHelper Lengow security helper instance
     * @param \Lengow\Connector\Model\Connector $connector Lengow connector instance
     * @param \Lengow\Connector\Model\Export $export Lengow export instance
     */
    public function __construct(
        Context $context,
        JsonHelper $jsonHelper,
        PriceCurrency $priceCurrency,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        SecurityHelper $securityHelper,
        Connector $connector,
        Export $export
    ) {
        $this->_jsonHelper = $jsonHelper;
        $this->_priceCurrency = $priceCurrency;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_securityHelper = $securityHelper;
        $this->_connector = $connector;
        $this->_export = $export;
        parent::__construct($context);
    }

    /**
     * Is sync action
     *
     * @param string $action sync action
     *
     * @return boolean
     */
    public function isSyncAction($action)
    {
        return in_array($action, $this->_syncActions);
    }

    /**
     * Get Sync Data (Inscription / Update)
     *
     * @return array
     */
    public function getSyncData()
    {
        $data = [
            'domain_name' => $_SERVER["SERVER_NAME"],
            'token' => $this->_configHelper->getToken(),
            'type' => 'magento',
            'version' => $this->_securityHelper->getMagentoVersion(),
            'plugin_version' =>  $this->_securityHelper->getPluginVersion(),
            'email' => $this->scopeConfig->getValue('trans_email/ident_general/email'),
            'cron_url' => $this->_dataHelper->getCronUrl(),
            'return_url' => 'http://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"],
            'shops' => []
        ];
        $stores = $this->_configHelper->getAllStore();
        foreach ($stores as $store) {
            $storeId = (int)$store->getId();
            $this->_export->init(['store_id' => $storeId]);
            $data['shops'][$storeId] = [
                'token' =>  $this->_configHelper->getToken($storeId),
                'shop_name' =>  $store->getName(),
                'domain_url' => $store->getBaseUrl(),
                'feed_url' =>  $this->_dataHelper->getExportUrl($storeId),
                'total_product_number' => $this->_export->getTotalProduct(),
                'exported_product_number' => $this->_export->getTotalExportedProduct(),
                'enabled' => $this->_configHelper->storeIsActive($storeId)
            ];
        }
        return $data;
    }

    /**
     * Set store configuration key from Lengow
     *
     * @param array $params Lengow API credentials
     */
    public function sync($params)
    {
        $this->_configHelper->setAccessIds(
            [
                'account_id' => $params['account_id'],
                'access_token' => $params['access_token'],
                'secret_token' => $params['secret_token']
            ],
            false
        );
        foreach ($params['shops'] as $storeToken => $storeCatalogIds) {
            $store = $this->_configHelper->getStoreByToken($storeToken);
            if ($store) {
                $this->_configHelper->setCatalogIds($storeCatalogIds['catalog_ids'], (int)$store->getId(), false);
                $this->_configHelper->setActiveStore((int)$store->getId(), false);
            }
        }
        // Clean config cache to valid configuration
        $this->_configHelper->cleanConfigCache();
    }

    /**
     * Sync Lengow catalogs for order synchronisation
     */
    public function syncCatalog()
    {
        if ($this->_configHelper->isNewMerchant()) {
            return false;
        }
        $result = $this->_connector->queryApi('get', '/v3.1/cms');
        if (isset($result->cms)) {
            $cmsToken = $this->_configHelper->getToken();
            foreach ($result->cms as $cms) {
                if ($cms->token === $cmsToken) {
                    foreach ($cms->shops as $cmsShop) {
                        $store = $this->_configHelper->getStoreByToken($cmsShop->token);
                        if ($store) {
                            $this->_configHelper->setCatalogIds($cmsShop->catalog_ids, (int)$store->getId(), false);
                            $this->_configHelper->setActiveStore((int)$store->getId(), false);
                        }
                    }
                    break;
                }
            }
        }
        // Clean config cache to valid configuration
        $this->_configHelper->cleanConfigCache();
    }

    /**
     * Get Status Account
     *
     * @param boolean $force force cache update
     *
     * @return array|false
     */
    public function getStatusAccount($force = false)
    {
        if ($this->_configHelper->isNewMerchant()) {
            return false;
        }
        if (!$force) {
            $updatedAt = $this->_configHelper->get('last_status_update');
            if (!is_null($updatedAt) && (time() - strtotime($updatedAt)) < $this->_cacheTime) {
                return json_decode($this->_configHelper->get('account_status'), true);
            }
        }
        $result = $this->_connector->queryApi('get', '/v3.0/plans');
        if (isset($result->isFreeTrial)) {
            $status = [];
            $status['type'] = $result->isFreeTrial ? 'free_trial' : '';
            $status['day'] = (int)$result->leftDaysBeforeExpired;
            $status['expired'] = (bool)$result->isExpired;
            if ($status['day'] < 0) {
                $status['day'] = 0;
            }
            if ($status) {
                $this->_configHelper->set('account_status', $this->_jsonHelper->jsonEncode($status));
                $this->_configHelper->set('last_status_update', date('Y-m-d H:i:s'));
                return $status;
            }
        } else {
            if ($this->_configHelper->get('last_status_update')) {
                return json_decode($this->_configHelper->get('account_status'), true);
            }
        }
        return false;
    }

    /**
     * Get Statistic for all stores
     *
     * @param boolean $force force cache update
     *
     * @return array
     */
    public function getStatistic($force = false)
    {
        if (!$force) {
            $updatedAt = $this->_configHelper->get('last_statistic_update');
            if (!is_null($updatedAt) && (time() - strtotime($updatedAt)) < $this->_cacheTime) {
                return json_decode($this->_configHelper->get('order_statistic'), true);
            }
        }
        $allCurrencyCodes = $this->_configHelper->getAllAvailableCurrencyCodes();
        $result = $this->_connector->queryApi(
            'get',
            '/v3.0/stats',
            [
                'date_from' => date('c', strtotime(date('Y-m-d') . ' -10 years')),
                'date_to' => date('c'),
                'metrics' => 'year'
            ]
        );
        if (isset($result->level0)) {
            $stats = $result->level0[0];
            $return = [
                'total_order' => $stats->revenue,
                'nb_order' => (int)$stats->transactions,
                'currency' => $result->currency->iso_a3,
                'available' => false
            ];
        } else {
            if ($this->_configHelper->get('last_statistic_update')) {
                return json_decode($this->_configHelper->get('order_statistic'), true);
            } else {
                return [
                    'total_order' => 0,
                    'nb_order' => 0,
                    'currency' => '',
                    'available' => false
                ];
            }
        }
        if ($return['total_order'] > 0 || $return['nb_order'] > 0) {
            $return['available'] = true;
        }
        if ($return['currency'] && in_array($return['currency'], $allCurrencyCodes)) {
            $return['total_order'] = $this->_priceCurrency->format(
                (float)$return['total_order'],
                false,
                2,
                null,
                $return['currency']
            );
        } else {
            $return['total_order'] = number_format($return['total_order'], 2, ',', ' ');
        }
        $this->_configHelper->set('order_statistic', $this->_jsonHelper->jsonEncode($return));
        $this->_configHelper->set('last_statistic_update', date('Y-m-d H:i:s'));
        return $return;
    }
}
