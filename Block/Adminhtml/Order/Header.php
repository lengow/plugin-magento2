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
use Magento\Framework\Escaper;

class Header extends Template
{
    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var ImportHelper Lengow import helper instance
     */
    private $importHelper;

    /**
     * @var Escaper $escaper Magento escaper instance
     */
    private $escaper;

    /**
     * Constructor
     *
     * @param Context $context Magento block context instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param ImportHelper $importHelper Lengow import helper instance
     * @param Escaper $escaper Magento escaper instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        Escaper $escaper,
        array $data = []
    ) {
        $this->configHelper = $configHelper;
        $this->importHelper = $importHelper;
        $this->escaper = $escaper;
        parent::__construct($context, $data);
    }

    /**
     * Debug Mode is enable
     *
     * @return bool
     */
    public function debugModeIsEnabled(): bool
    {
        return $this->configHelper->debugModeIsActive();
    }

    /**
     * Get Lengow import helper instance
     *
     * @return ImportHelper
     */
    public function getImportHelper(): ImportHelper
    {
        return $this->importHelper;
    }

    /**
     * Get Magento escaper instance
     */
    public function getEscaper(): Escaper
    {
        return $this->escaper;
    }
}
