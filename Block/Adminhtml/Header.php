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
 * @subpackage  Block
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;

class Header extends Template
{
    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Helper\Sync Lengow sync helper instance
     */
    protected $_syncHelper;

    /**
     * @var array Lengow status account
     */
    protected $_statusAccount = array();

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context Magento block context instance
     * @param array $data additional params
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Helper\Sync $syncHelper Lengow sync helper instance
     */
    public function __construct(
        Context $context,
        array $data = [],
        ConfigHelper $configHelper,
        SyncHelper $syncHelper
    ) {
        parent::__construct($context, $data);
        $this->_configHelper = $configHelper;
        $this->_syncHelper = $syncHelper;
        $this->_statusAccount = $this->_syncHelper->getStatusAccount();
    }

    /**
     * Preprod mode is enabled
     *
     * @return boolean
     */
    public function preprodModeIsEnabled()
    {
        return (bool)$this->_configHelper->get('preprod_mode_enable');
    }

    /**
     * Free trial is enabled
     *
     * @return boolean
     */
    public function freeTrialIsEnabled()
    {
        if ((isset($this->_statusAccount['type']) && $this->_statusAccount['type'] === 'free_trial')
            && (isset($this->_statusAccount['expired']) && !$this->_statusAccount['expired'])
        ) {
            return true;
        }
        return false;
    }

    /**
     * Recovers the number of days of free trial
     *
     * @return integer
     */
    public function getFreeTrialDays()
    {
        return isset($this->_statusAccount['day']) ? (int)$this->_statusAccount['day']  : 0;
    }
}
