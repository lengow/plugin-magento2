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
     * Constructor
     *
     * @param Context $context
     * @param ResourceConnection $resource
     * @param LogFactory $logFactory
     */
    public function __construct(
        Context $context,
        ResourceConnection $resource,
        LogFactory $logFactory
    ){
        parent::__construct($context);
        $this->_resource = $resource;
        $this->_logFactory = $logFactory;
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
     * @param string $message log message
     * @param array  $params  log parameters
     *
     * @return string
     */
    public function decodeLogMessage($message, $params = null)
    {
        if (preg_match('/^([^\[\]]*)(\[(.*)\]|)$/', $message, $result)) {
            if (isset($result[1])) {
                $key = $result[1];
                if (isset($result[3]) && is_null($params)) {
                    $strParam = $result[3];
                    $params = explode('|', $strParam);
                }
                $phrase = __($key, $params);
                $message = $phrase->__toString();
            }
        }
        return $message;
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
        $finalMessage = ''.(empty($marketplaceSku) ? '' : 'order '.$marketplaceSku.' : ');
        $finalMessage.= $message;
        if ($display) {
            echo $finalMessage.'<br />';
            flush();
        }
        $log = $this->_logFactory->create();
        return $log->createLog(array('message' => $finalMessage, 'category' => $category));
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
        $connection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $table = $connection->getTableName('lengow_log');
        $query = "DELETE FROM ".$table." WHERE `date` < DATE_SUB(NOW(),INTERVAL ".$nbDays." DAY)";
        $connection->query($query);
    }

}

