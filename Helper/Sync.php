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
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Connector as Connector;

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
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Model\Connector Lengow connector instance
     */
    protected $_connector;

    /**
     * @var integer cache time for statistic, account status and cms options
     */
    protected $_cacheTime = 18000;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context Magento context instance
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper Magento json helper instance
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency Magento price currency instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Model\Connector $modelConnector Lengow connector instance
     */
    public function __construct(
        Context $context,
        JsonHelper $jsonHelper,
        PriceCurrency $priceCurrency,
        ConfigHelper $configHelper,
        Connector $modelConnector
    ) {
        $this->_jsonHelper = $jsonHelper;
        $this->_priceCurrency = $priceCurrency;
        $this->_configHelper = $configHelper;
        $this->_connector = $modelConnector;
        parent::__construct($context);
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
            $status = array();
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
