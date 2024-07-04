<?php
/**
 * Copyright 2020 Lengow SAS
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
 * @copyright   2020 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
namespace Lengow\Connector\Plugin;

use Magento\Tax\Model\Config as TaxConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Backend\Model\Session as BackendSession;

/**
 * class ShippingConfigPlugin
 */
class ShippingTaxConfigPlugin
{

    /**
     *
     * @var BackendSession $backendSession
     */
    protected $backendSession;

    /**
     *
     * @param BackendSession $backendSession
     */
    public function __construct(BackendSession $backendSession)
    {
        $this->backendSession= $backendSession;
    }

    /**
     * afterGetShippingTaxClass
     *
     * @param TaxConfig $subject
     * @param int $result
     *
     * @return int $result
     */
    public function afterGetShippingTaxClass(TaxConfig $subject, int $result)
    {
        if ($this->backendSession->getIsFromlengow()
                && (int) $this->backendSession->getIsLengowB2b() === 1) {
            return 0;
        }
        return $result;
    }
}
