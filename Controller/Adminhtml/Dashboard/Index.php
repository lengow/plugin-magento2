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

namespace Lengow\Connector\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Lengow\Connector\Helper\Config as ConfigHelper;

class Index extends Action
{
    /**
     * @var JsonFactory Magento json factory instance
     */
    private $resultJsonFactory;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * Constructor
     *
     * @param Context $context Magento action context instance
     * @param JsonFactory $resultJsonFactory Magento json factory instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ConfigHelper $configHelper
    ) {
        $this->configHelper = $configHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Load and render layout
     *
     * @return mixed
     */
    public function execute()
    {
        if ($this->configHelper->isNewMerchant()) {
            $this->_redirect('lengow/home/index');
        } elseif ($this->getRequest()->getParam('isAjax')) {
            $action = $this->getRequest()->getParam('action');
            if ($action === 'remind_me_later') {
                $timestamp = time() + (7 * 86400);
                $this->configHelper->set(ConfigHelper::LAST_UPDATE_PLUGIN_MODAL, $timestamp);
                return $this->resultJsonFactory->create()->setData(['success' => true]);
            }
        } else {
            $this->_view->loadLayout();
            $this->_view->renderLayout();
        }
    }
}
