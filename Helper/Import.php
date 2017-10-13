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
use Magento\Framework\Stdlib\DateTime\DateTime;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Import\Ordererror;
use Lengow\Connector\Model\Import\Marketplace;

class Import extends AbstractHelper
{
    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $_configHelper = null;

    /**
     * @var array marketplaces collection
     */
    public static $marketplaces = [];

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Model\Import\Ordererror Lengow ordererror instance
     */
    protected $_orderError;

    /**
     * @var \Lengow\Connector\Model\Import\Marketplace Lengow marketplace instance
     */
    protected $_marketplace;

    /**
     * @var array valid states lengow to create a Lengow order
     */
    protected $_lengowStates = [
        'waiting_shipment',
        'shipped',
        'closed'
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context Magento context instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime Magento datetime instance
     * @param \Lengow\Connector\Model\Import\Ordererror $orderError Lengow orderError instance
     * @param \Lengow\Connector\Model\Import\Marketplace $marketplace Lengow marketplace instance
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        Ordererror $orderError,
        DateTime $dateTime,
        Marketplace $marketplace
    )
    {
        $this->_configHelper = $configHelper;
        $this->_dataHelper = $dataHelper;
        $this->_dateTime = $dateTime;
        $this->_orderError = $orderError;
        $this->_marketplace = $marketplace;
        parent::__construct($context);
    }

    /**
     * Check if import is already in process
     *
     * @return boolean
     */
    public function importIsInProcess()
    {
        $timestamp = $this->_configHelper->get('import_in_progress');
        if ($timestamp > 0) {
            // security check : if last import is more than 60 seconds old => authorize new import to be launched
            if (($timestamp + (60 * 1)) < time()) {
                $this->setImportEnd();
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Get Rest time to make re import order
     *
     * @return boolean
     */
    public function restTimeToImport()
    {
        $timestamp = $this->_configHelper->get('import_in_progress');
        if ($timestamp > 0) {
            return $timestamp + (60 * 1) - time();
        }
        return false;
    }

    /**
     * Set import to "in process" state
     *
     * @return boolean
     */
    public function setImportInProcess()
    {
        return $this->_configHelper->set('import_in_progress', time());
    }

    /**
     * Set import to finished
     *
     * @return boolean
     */
    public function setImportEnd()
    {
        return $this->_configHelper->set('import_in_progress', -1);
    }

    /**
     * Record the date of the last import
     *
     * @param string $type last import type (cron or manual)
     *
     * @return boolean
     */
    public function updateDateImport($type)
    {
        if ($type === 'cron') {
            $this->_configHelper->set('last_import_cron', $this->_dateTime->gmtTimestamp());
        } else {
            $this->_configHelper->set('last_import_manual', $this->_dateTime->gmtTimestamp());
        }
        return true;
    }

    /**
     * Get last import (type and timestamp)
     *
     * @return array
     */
    public function getLastImport()
    {
        $timestampCron = $this->_configHelper->get('last_import_cron');
        $timestampManual = $this->_configHelper->get('last_import_manual');

        if ($timestampCron && $timestampManual) {
            if ((int)$timestampCron > (int)$timestampManual) {
                return ['type' => 'cron', 'timestamp' => (int)$timestampCron];
            } else {
                return ['type' => 'manual', 'timestamp' => (int)$timestampManual];
            }
        } elseif ($timestampCron && !$timestampManual) {
            return ['type' => 'cron', 'timestamp' => (int)$timestampCron];
        } elseif ($timestampManual && !$timestampCron) {
            return ['type' => 'manual', 'timestamp' => (int)$timestampManual];
        }

        return ['type' => 'none', 'timestamp' => 'none'];
    }


    /**
     * Get Marketplace singleton
     *
     * @param string $name marketplace name
     *
     * @return array Lengow marketplace
     */
    public function getMarketplaceSingleton($name)
    {
        if (!array_key_exists($name, self::$marketplaces)) {
            $this->_marketplace->init(['name' => $name]);
            self::$marketplaces[$name] = $this->_marketplace;
        }
        return self::$marketplaces[$name];
    }

}
