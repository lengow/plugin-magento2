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
use Magento\Framework\HTTP\PhpEnvironment\ServerAddress;
use Lengow\Connector\Helper\Config as ConfigHelper;

class Security extends AbstractHelper
{
    /**
     * @var array lengow authorized ips
     */
    protected $_ipsLengow = [
        '127.0.0.1',
        '10.0.4.150',
        '46.19.183.204',
        '46.19.183.217',
        '46.19.183.218',
        '46.19.183.219',
        '46.19.183.222',
        '52.50.58.130',
        '89.107.175.172',
        '89.107.175.185',
        '89.107.175.186',
        '89.107.175.187',
        '90.63.241.226',
        '109.190.189.175',
        '146.185.41.180',
        '146.185.41.177',
        '185.61.176.129',
        '185.61.176.130',
        '185.61.176.131',
        '185.61.176.132',
        '185.61.176.133',
        '185.61.176.134',
        '185.61.176.137',
        '185.61.176.138',
        '185.61.176.139',
        '185.61.176.140',
        '185.61.176.141',
        '185.61.176.142',
    ];

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\ServerAddress Magento server address instance
     */
    protected $_serverAddress;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context Magento context instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Magento\Framework\HTTP\PhpEnvironment\ServerAddress $serverAddress Magento server address instance
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ServerAddress $serverAddress
    ) {
        $this->_configHelper = $configHelper;
        $this->_serverAddress = $serverAddress;
        parent::__construct($context);
    }

    /**
     * Check Webservice access (export and cron)
     *
     * @param string $token store token
     * @param integer $storeId Magento store id
     *
     * @return boolean
     */
    public function checkWebserviceAccess($token, $storeId = 0)
    {
        if (!(bool)$this->_configHelper->get('ip_enable') && $this->checkToken($token, $storeId)) {
            return true;
        }
        if ($this->checkIp()) {
            return true;
        }
        return false;
    }

    /**
     * Check if token is correct
     *
     * @param string $token store token
     * @param integer $storeId Magento store id
     *
     * @return boolean
     */
    public function checkToken($token, $storeId = 0)
    {
        $storeToken = $this->_configHelper->getToken($storeId);
        if ($token === $storeToken) {
            return true;
        }
        return false;
    }

    /**
     * Check if current IP is authorized
     *
     * @return boolean
     */
    public function checkIp()
    {
        $authorizedIps = $this->getAuthorizedIps();
        $hostnameIp = $this->getRemoteIp();
        if (in_array($hostnameIp, $authorizedIps)) {
            return true;
        }
        return false;
    }

    /**
     * Get authorized IPS
     *
     * @return array
     */
    public function getAuthorizedIps()
    {
        $ips = $this->_configHelper->get('authorized_ip');
        if (!is_null($ips) && (bool)$this->_configHelper->get('ip_enable')) {
            $ips = trim(str_replace(["\r\n", ',', '-', '|', ' '], ';', $ips), ';');
            $ips = explode(';', $ips);
            $authorizedIps = array_merge($ips, $this->_ipsLengow);
        } else {
            $authorizedIps = $this->_ipsLengow;
        }
        $authorizedIps[] = $this->getServerIp();
        return $authorizedIps;
    }

    /**
     * Get server IP
     *
     * @return string
     */
    public function getServerIp()
    {
        return $this->_serverAddress->getServerAddress();
    }

    /**
     * Get remote IP
     *
     * @return string
     */
    public function getRemoteIp()
    {
        return $this->_remoteAddress->getRemoteAddress();
    }
}
