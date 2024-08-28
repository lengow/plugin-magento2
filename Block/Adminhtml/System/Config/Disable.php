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
namespace Lengow\Connector\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Lengow\Connector\Helper\Config as LengowConfig;
use Magento\Config\Block\System\Config\Form\Field as ConfigFormField;
use Magento\Backend\Block\Template\Context;
class Disable extends ConfigFormField
{
    /**
     * @var LengowConfig
     */
    protected $lengowConfig;

    /**
     * Disable constructor
     */
    public function __construct(
        Context $context,
        LengowConfig $lengowConfig,
        array $data = []
    ) {
        $this->lengowConfig = $lengowConfig;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve element HTML markup
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        if (!$this->lengowConfig->isDeveloperMode()) {
            return $element->getElementHtml();
        }
        $element->setDisabled('disabled');
        $element->setComment(__('This field is disabled because you are in developer mode.'));
        return $element->getElementHtml();
    }
}