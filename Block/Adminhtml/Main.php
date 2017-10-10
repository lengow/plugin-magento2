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

class Main extends Template
{
    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context Magento block context instance
     * @param array $data additional params
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     */
    public function __construct(
        Context $context,
        array $data = [],
        ConfigHelper $configHelper
    ) {
        parent::__construct($context, $data);
        $this->_configHelper = $configHelper;
    }
}
