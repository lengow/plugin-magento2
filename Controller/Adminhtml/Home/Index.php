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
 * @subpackage  Controller
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Controller\Adminhtml\Home;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Lengow\Connector\Helper\Config as ConfigHelper;

class Index extends Action
{
    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * Constructor
     *
     * @param Context $context Magento action context instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     */
    public function __construct(Context $context, ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
        parent::__construct($context);
    }

    /**
     * Load and render layout
     */
    public function execute()
    {
        if ($this->configHelper->isNewMerchant()) {
            $this->_view->loadLayout();
            $this->_view->renderLayout();
        } else {
            $this->_redirect('lengow/dashboard/index');
        }
    }
}
