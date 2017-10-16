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
use Magento\Framework\Controller\ResultFactory;
use Lengow\Connector\Helper\Sync as SyncHelper;

class Refresh extends Action
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
     * Refresh account status
     */
    public function execute()
    {
        $this->_syncHelper->getStatusAccount(true);
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('lengow/*/');
    }
}
