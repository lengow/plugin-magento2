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
use Lengow\Connector\Model\Connector as LengowConnector;
use Lengow\Connector\Helper\Config as ConfigHelper;

class Footer extends Template
{
    /**
     * @var SecurityHelper Lengow security helper instance
     */
    private $securityHelper;

    private $configHelper;

    /**
     * Constructor
     *
     * @param Context $context Magento block context instance
     * @param SecurityHelper $securityHelper Lengow security helper instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        SecurityHelper $securityHelper,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        $this->securityHelper = $securityHelper;
        $this->configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function getPluginVersion(): string
    {
        return $this->securityHelper->getPluginVersion();
    }

    /**
     * Get preprod warning
     *
     * @return string
     */
    public function isPreprodPlugin(): string
    {
        return !$this->configHelper->isProdEnvironment();
    }

    /**
     * Get developer mode warning
     */
    public function isDeveloperModePlugin(): bool
    {
        return $this->configHelper->isDeveloperMode();
    }

    /**
     * Get plugin copyright
     *
     * @return string
     */
    public function getPluginCopyright(): string
    {
        return 'copyright © ' . date('Y');
    }
}
