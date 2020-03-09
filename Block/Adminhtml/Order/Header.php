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

namespace Lengow\Connector\Block\Adminhtml\Order;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Import as ImportHelper;

class Header extends Template
{
    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var ImportHelper Lengow import helper instance
     */
    protected $_importHelper;

    /**
     * Constructor
     *
     * @param Context $context Magento block context instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param ImportHelper $importHelper Lengow import helper instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        array $data = []
    )
    {
        $this->_configHelper = $configHelper;
        $this->_importHelper = $importHelper;
        parent::__construct($context, $data);
    }

    /**
     * Debug Mode is enable
     *
     * @return boolean
     */
    public function debugModeIsEnabled()
    {
        return $this->_configHelper->debugModeIsActive();
    }

    /**
     * Get Lengow import helper instance
     *
     * @return ImportHelper
     */
    public function getImportHelper()
    {
        return $this->_importHelper;
    }

}
