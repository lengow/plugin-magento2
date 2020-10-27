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
use Magento\Framework\Locale\Resolver as Locale;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Model\Connector as LengowConnector;

class Main extends Template
{
    /**
     * @var Locale Magento locale resolver instance
     */
    protected $_locale;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var SyncHelper Lengow sync helper instance
     */
    protected $_syncHelper;

    /**
     * @var array Lengow status account
     */
    protected $_statusAccount = [];

    /**
     * Constructor
     *
     * @param Context $context Magento block context instance
     * @param Locale $locale Magento locale resolver instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param SyncHelper $syncHelper Lengow sync helper instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        Locale $locale,
        ConfigHelper $configHelper,
        SyncHelper $syncHelper,
        array $data = []
    ) {
        $this->_locale = $locale;
        $this->_configHelper = $configHelper;
        $this->_syncHelper = $syncHelper;
        $this->_statusAccount = $this->_syncHelper->getStatusAccount();
        parent::__construct($context, $data);
    }

    /**
     * Check if is a new merchant
     *
     * @return boolean
     */
    public function isNewMerchant()
    {
        return $this->_configHelper->isNewMerchant();
    }

    /**
     * Check if isSync parameter is present
     *
     * @return string|null
     */
    public function isSync()
    {
        return $this->getRequest()->getParam('isSync');
    }

    /**
     * Free trial is expired
     *
     * @return boolean
     */
    public function freeTrialIsExpired()
    {
        if ((isset($this->_statusAccount['type']) && $this->_statusAccount['type'] === 'free_trial')
            && (isset($this->_statusAccount['expired']) && $this->_statusAccount['expired'])
        ) {
            return true;
        }
        return false;
    }

    /**
     * Get current locale
     *
     * @return string
     */
    public function getIsoCode()
    {
        return strtolower(substr($this->_locale->getLocale(), 0, 2));
    }

    /**
     * Get Lengow url
     *
     * @return string
     */
    public function getLengowUrl()
    {
        return LengowConnector::LENGOW_URL;
    }

    /**
     * Get Lengow solution url
     *
     * @return string
     */
    public function getLengowSolutionUrl()
    {
        return '//my.' . LengowConnector::LENGOW_URL;
    }
}
