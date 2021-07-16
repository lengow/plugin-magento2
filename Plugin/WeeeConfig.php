<?php
/**
 * Copyright 2021 Lengow SAS
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
 * @subpackage  Plugin
 * @author      Team module <team-module@lengow.com>
 * @copyright   2021 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Plugin;

use Closure;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Weee\Model\Config;

class WeeeConfig
{
    /**
     * @var BackendSession Backend session instance
     */
    protected $backendSession;

    /**
     * Constructor
     *
     * @param BackendSession $backendSession Backend session instance
     */
    public function __construct(BackendSession $backendSession)
    {
        $this->backendSession = $backendSession;
    }

    /**
     * This method is executed each time magento call his own isEnabled method
     * It allow lengow orders to be imported without any FPT rules
     *
     * @param Config $subject Magento Weee Config base class
     * @param Closure $proceed Callable (have to be called otherwise magento prevent the execution of the next plugins)
     *
     * @return boolean
     */
    public function aroundIsEnabled(Config $subject, Closure $proceed) {
        if ($this->backendSession->getIsFromlengow()) {
            return false;
        }
        return $proceed();
    }
}
