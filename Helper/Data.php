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
use Magento\Framework\App\ObjectManager;

class Data extends AbstractHelper
{

    /**
     * @var integer life of log files in days
     */
    const LOG_LIFE = 20;

    /**
     * @var ObjectManager objectManager
     */
    protected $_objectManager;

    protected $_resource;

    public function __construct(
        Context $context,
        \Magento\Framework\App\ResourceConnection $resource
    ){
        $this->_objectManager = ObjectManager::getInstance();
        parent::__construct($context);
        $this->_resource = $resource;
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
        $log = $this->_objectManager->create('Lengow\Connector\Model\Log');
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
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $connection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $table = $resource->getTableName('lengow/log');
        $query = "DELETE FROM ".$table." WHERE `date` < DATE_SUB(NOW(),INTERVAL ".$nbDays." DAY)";
        $writeConnection->query($query);
    }

}

