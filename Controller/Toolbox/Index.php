<?php
/**
 * Copyright 2021 Lengow SAS
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
 * @copyright   2021 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Controller\Toolbox;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Helper\Toolbox as ToolboxHelper;

/**
 * ToolboxController
 */
class Index extends Action
{
    /**
     * @var JsonHelper Magento json helper instance
     */
    protected $jsonHelper;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $configHelper;

    /**
     * @var SecurityHelper Lengow security helper instance
     */
    protected $securityHelper;

    /**
     * @var ToolboxHelper Lengow toolbox helper instance
     */
    protected $toolboxHelper;

    /**
     * Constructor
     *
     * @param Context $context Magento action context instance
     * @param JsonHelper $jsonHelper Magento json helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param SecurityHelper $securityHelper Lengow security helper instance
     * @param ToolboxHelper $toolboxHelper Lengow toolbox helper instance
     */
    public function __construct(
        Context $context,
        JsonHelper $jsonHelper,
        ConfigHelper $configHelper,
        SecurityHelper $securityHelper,
        ToolboxHelper $toolboxHelper
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->configHelper = $configHelper;
        $this->securityHelper = $securityHelper;
        $this->toolboxHelper = $toolboxHelper;
        parent::__construct($context);
    }

    /**
     * Get all plugin data for toolbox
     */
    public function execute()
    {
        /**
         * List params
         * string toolbox_action toolbox specific action
         * string type           type of data to display
         * string date           date of the log to export
         */
        $token = $this->getRequest()->getParam(ToolboxHelper::PARAM_TOKEN);
        if ($this->securityHelper->checkWebserviceAccess($token)) {
            // check if toolbox action is valid
            $action = $this->getRequest()->getParam(ToolboxHelper::PARAM_TOOLBOX_ACTION) ?: ToolboxHelper::ACTION_DATA;
            if ($this->toolboxHelper->isToolboxAction($action)) {
                switch ($action) {
                    case ToolboxHelper::ACTION_LOG:
                        $date = $this->getRequest()->getParam(ToolboxHelper::PARAM_DATE);
                        $this->toolboxHelper->downloadLog($date);
                        break;
                    default:
                        $type = $this->getRequest()->getParam(ToolboxHelper::PARAM_TYPE);
                        $this->getResponse()->setBody(
                            $this->jsonHelper->jsonEncode($this->toolboxHelper->getData($type))
                        );
                }
            } else {
                $errorMessage = __('Action: %1 is not a valid action', [$action]);
                $this->getResponse()->setStatusHeader(400, '1.1', 'Bad Request');
                $this->getResponse()->setBody($errorMessage->__toString());
            }
        } else {
            if ($this->configHelper->get(ConfigHelper::AUTHORIZED_IP_ENABLED)) {
                $errorMessage = __('unauthorised IP: %1', [$this->securityHelper->getRemoteIp()]);
            } else {
                $errorMessage = $token !== ''
                    ? __('unauthorised access for this token: %1', [$token])
                    : __('unauthorised access: token parameter is empty');
            }
            $this->getResponse()->setStatusHeader(403, '1.1', 'Forbidden');
            $this->getResponse()->setBody($errorMessage->__toString());
        }
    }
}
