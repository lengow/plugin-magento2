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
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Connector as ModelConnector;


class Sync extends AbstractHelper
{

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_modelConnector;

    /**
     * Constructor
     *
     * @param Context $context
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Model\Connector $modelConnector
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ModelConnector $modelConnector
    ){
        $this->_configHelper = $configHelper;
        $this->_modelConnector = $modelConnector;
        parent::__construct($context);
    }

    /**
     * Check that a store is activated and has account id and tokens non-empty
     *
     * @param integer $storeId Magento store id
     *
     * @return boolean
     */
    public function checkSyncStore($storeId)
    {
        return $this->_configHelper->get('store_enable', $storeId)
               && $this->_modelConnector->validAuthenticationByStore($storeId);
    }
}
