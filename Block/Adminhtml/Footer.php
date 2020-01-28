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
use Lengow\Connector\Helper\Security as SecurityHelper;

class Footer extends Template
{
    /**
     * @var \Lengow\Connector\Helper\Security Lengow security helper instance
     */
    protected $_securityHelper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context Magento block context instance
     * @param \Lengow\Connector\Helper\Security $securityHelper Lengow security helper instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        SecurityHelper $securityHelper,
        array $data = []
    ) {
        $this->_securityHelper = $securityHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getPluginVersion()
    {
        return $this->_securityHelper->getPluginVersion();
    }

    /**
     * Get plugin copyright
     *
     * @return string
     */
    public function getPluginCopyright()
    {
        return 'copyright © ' . date('Y');
    }
}
