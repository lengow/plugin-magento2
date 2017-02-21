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
use Magento\Framework\App\ResourceConnection;
use Lengow\Connector\Model\LogFactory as LogFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    /**
     * @var integer life of log files in days
     */
    const LOG_LIFE = 20;

    /**
     * @var LogFactory
     */
    protected $_logFactory;

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface Magento store manager instance
     */
    protected $_storeManager;

    /**
     * Constructor
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Magento store manager instance
     * @param Context $context
     * @param ResourceConnection $resource
     * @param LogFactory $logFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Context $context,
        ResourceConnection $resource,
        LogFactory $logFactory,
        DateTime $date
    ){
        $this->_resource = $resource;
        $this->_logFactory = $logFactory;
        $this->_date = $date;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Write log
     *
     * @param string  $category       Category
     * @param string  $message        log message
     * @param boolean $display        display on screen
     * @param string  $marketplaceSku lengow order id
     *
     * @return boolean
     */
    public function log($category, $message = "", $display = false, $marketplaceSku = null)
    {
        if (strlen($message) == 0) {
            return false;
        }
        $decodedMessage = $this->decodeLogMessage($message, false);
        $finalMessage = ''.(empty($marketplaceSku) ? '' : 'order '.$marketplaceSku.' : ');
        $finalMessage.= $decodedMessage;
        if ($display) {
            echo $finalMessage.'<br />';
            flush();
        }
        $log = $this->_logFactory->create();
        return $log->createLog(['message' => $finalMessage, 'category' => $category]);
    }

    /**
     * Set message with parameters for translation
     *
     * @param string $key    log key
     * @param array  $params log parameters
     *
     * @return string
     */
    public function setLogMessage($key, $params = null)
    {
        if (is_null($params) || (is_array($params) && count($params) == 0)) {
            return $key;
        }
        $allParams = [];
        foreach ($params as $value) {
            $value = str_replace('|', '', $value);
            $allParams[] = $value;
        }
        $message = $key.'['.join('|', $allParams).']';
        return $message;
    }

    /**
     * Decode message with params for translation
     *
     * @param string  $message        log message
     * @param boolean $useTranslation use Magento translation
     * @param array   $params         log parameters
     *
     * @return string
     */
    public function decodeLogMessage($message, $useTranslation = true, $params = null)
    {
        if (preg_match('/^([^\[\]]*)(\[(.*)\]|)$/', $message, $result)) {
            if (isset($result[1])) {
                $key = $result[1];
                if (isset($result[3]) && is_null($params)) {
                    $strParam = $result[3];
                    $params = explode('|', $strParam);
                }
                if ($useTranslation) {
                    $phrase = __($key, $params);
                    $message = $phrase->__toString();
                } else {
                    if (count($params) > 0) {
                        $ii = 1;
                        foreach ($params as $param) {
                            $key = str_replace('%'.$ii, $param, $key);
                            $ii++;
                        }
                    }
                    $message = $key;
                }
            }
        }
        return $message;
    }

    /**
     * Delete log files when too old
     *
     * @param integer $nbDays
     */
    public function cleanLog($nbDays = 20)
    {
        if ($nbDays <= 0) {
            $nbDays = self::LOG_LIFE;
        }
        $connection = $this->_resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $table = $connection->getTableName('lengow_log');
        $query = 'DELETE FROM '.$table.' WHERE `date` < DATE_SUB(NOW(),INTERVAL '.$nbDays.' DAY)';
        $connection->query($query);
    }

    /**
     * Get export Url
     *
     * @param integer $storeId          Magento store id
     * @param array   $additionalParams additional parameters for export url
     *
     * @return string
     */
    public function getExportUrl($storeId, $additionalParams = [])
    {
        $defaultParams = [
            'store'         => $storeId,
            '_nosid'        => true,
            '_store_to_url' => false,
        ];
        if (count($additionalParams) > 0) {
            $defaultParams = array_merge($defaultParams, $additionalParams);
        }
        $this->_urlBuilder->setScope($storeId);
        return $this->_urlBuilder->getUrl('lengow/feed', $defaultParams);
    }

    /**
     * Get date in local date
     *
     * @param integer $timestamp linux timestamp
     * @param boolean $second    see seconds or not
     *
     * @return string in gmt format
     */
    public function getDateInCorrectFormat($timestamp, $second = false)
    {
        if ($second) {
            $format = 'l d F Y @ H:i:s';
        } else {
            $format = 'l d F Y @ H:i';
        }
        return $this->_date->date($format, $timestamp);
    }

    /**
     * Get store
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        $storeId = (int)$this->_getRequest()->getParam('store', 0);
        if ($storeId == 0) {
            $storeId = $this->_storeManager->getDefaultStoreView()->getId();
        }
        return $this->_storeManager->getStore($storeId);
    }
}
