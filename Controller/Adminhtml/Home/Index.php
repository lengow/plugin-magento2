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
use Magento\Framework\Controller\Result\JsonFactory;
use Lengow\Connector\Helper\Sync as SyncHelper;

class Index extends Action
{
    /**
     * @var JsonFactory Magento json factory instance
     */
    protected $_resultJsonFactory;

    /**
     * @var SyncHelper Lengow sync helper instance
     */
    protected $_syncHelper;

    /**
     * Constructor
     *
     * @param Context $context Magento action context instance
     * @param JsonFactory $resultJsonFactory Magento json factory instance
     * @param SyncHelper $syncHelper Lengow sync helper instance
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SyncHelper $syncHelper
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_syncHelper = $syncHelper;
        parent::__construct($context);
    }

    /**
     * Load and render layout
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('isAjax')) {
            $action = $this->getRequest()->getParam('action');
            if ($action) {
                switch ($action) {
                    case 'get_sync_data':
                        return $this->_resultJsonFactory->create()->setData(
                            [
                                'function' => 'sync',
                                'parameters' => $this->_syncHelper->getSyncData(),
                            ]
                        );
                        break;
                    case 'sync':
                        $data = $this->getRequest()->getParam('data', 0);
                        $this->_syncHelper->sync($data);
                        $this->_syncHelper->getStatusAccount(true);
                        break;
                }
            }
        } else {
            $this->_view->loadLayout();
            $this->_view->renderLayout();
        }
    }
}
