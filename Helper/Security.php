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
use Magento\Framework\App\ProductMetadataInterface as ProductMetadata;
use Magento\Framework\HTTP\PhpEnvironment\ServerAddress;
use Magento\Framework\Module\ModuleListInterface as ModuleList;
use Lengow\Connector\Helper\Config as ConfigHelper;

class Security extends AbstractHelper
{
    /**
     * @var string Lengow module name
     */
    const MODULE_NAME = 'Lengow_Connector';

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
     * @var ServerAddress Magento server address instance
     */
    protected $_serverAddress;

    /**
     * @var ModuleList Magento module list instance
     */
    protected $_moduleList;

    /**
     * @var ProductMetadata Magento product metadata instance
     */
    protected $_productMetadata;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * Constructor
     *
     * @param Context $context Magento context instance
     * @param ServerAddress $serverAddress Magento server address instance
     * @param ModuleList $moduleList Magento module list instance
     * @param ProductMetadata $productMetadata Magento product metadata instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     */
    public function __construct(
        Context $context,
        ServerAddress $serverAddress,
        ModuleList $moduleList,
        ProductMetadata $productMetadata,
        ConfigHelper $configHelper
    ) {
        $this->_serverAddress = $serverAddress;
        $this->_moduleList = $moduleList;
        $this->_productMetadata = $productMetadata;
        $this->_configHelper = $configHelper;
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
        if ($ips !== null && (bool)$this->_configHelper->get('ip_enable')) {
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

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getPluginVersion()
    {
        return $this->_moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    /**
     * Get Magento version
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->_productMetadata->getVersion();
    }
}
