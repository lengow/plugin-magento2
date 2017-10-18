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

namespace Lengow\Connector\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Lengow\Connector\Helper\Sync as SyncHelper;

class Index extends Action
{
    /**
     * @var \Lengow\Connector\Helper\Sync Lengow sync helper instance
     */
    protected $_syncHelper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context Magento action context instance
     * @param \Lengow\Connector\Helper\Sync $syncHelper Lengow sync helper instance
     */
    public function __construct(
        Context $context,
        SyncHelper $syncHelper
    ) {
        $this->_syncHelper = $syncHelper;
        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return void
     */
    public function execute()
    {
        if ($this->_syncHelper->pluginIsBlocked()) {
            $this->_redirect('lengow/home/index');
        } else {
            $this->_view->loadLayout();
            $this->_view->renderLayout();
        }
    }
}
