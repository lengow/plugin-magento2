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
use Lengow\Connector\Model\Connector as LengowConnector;

/**
 * ToolboxController
 */
class Index extends Action
{
    /**
     * @var JsonHelper Magento json helper instance
     */
    private $jsonHelper;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var SecurityHelper Lengow security helper instance
     */
    private $securityHelper;

    /**
     * @var ToolboxHelper Lengow toolbox helper instance
     */
    private $toolboxHelper;

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
     *
     * List params
     * string  toolbox_action   Toolbox specific action
     * string  type             Type of data to display
     * string  created_from     Synchronization of orders since
     * string  created_to       Synchronization of orders until
     * string  date             Log date to download
     * string  marketplace_name Lengow marketplace name to synchronize
     * string  marketplace_sku  Lengow marketplace order id to synchronize
     * string  process          Type of process for order action
     * boolean force            Force synchronization order even if there are errors (1) or not (0)
     * integer shop_id          Shop id to synchronize
     * integer days             Synchronization interval time
     */
    public function execute()
    {
        $token = $this->getRequest()->getParam(ToolboxHelper::PARAM_TOKEN);
        if ($this->securityHelper->checkWebserviceAccess($token)) {
            // check if toolbox action is valid
            $action = $this->getRequest()->getParam(ToolboxHelper::PARAM_TOOLBOX_ACTION, ToolboxHelper::ACTION_DATA);
            if ($this->toolboxHelper->isToolboxAction($action)) {
                switch ($action) {
                    case ToolboxHelper::ACTION_LOG:
                        $date = $this->getRequest()->getParam(ToolboxHelper::PARAM_DATE);
                        $this->toolboxHelper->downloadLog($date);
                        break;
                    case ToolboxHelper::ACTION_ORDER:
                        $process = $this->getRequest()
                            ->getParam(ToolboxHelper::PARAM_PROCESS, ToolboxHelper::PROCESS_TYPE_SYNC);
                        if ($process === ToolboxHelper::PROCESS_TYPE_GET_DATA) {
                            $result = $this->toolboxHelper->getOrderData(
                                $this->getRequest()->getParam(ToolboxHelper::PARAM_MARKETPLACE_SKU),
                                $this->getRequest()->getParam(ToolboxHelper::PARAM_MARKETPLACE_NAME),
                                $this->getRequest()->getParam(ToolboxHelper::PARAM_TYPE, ToolboxHelper::DATA_TYPE_ORDER)
                            );
                        } else {
                            $result = $this->toolboxHelper->syncOrders(
                                [
                                    ToolboxHelper::PARAM_CREATED_TO => $this->getRequest()
                                        ->getParam(ToolboxHelper::PARAM_CREATED_TO),
                                    ToolboxHelper::PARAM_CREATED_FROM => $this->getRequest()
                                        ->getParam(ToolboxHelper::PARAM_CREATED_FROM),
                                    ToolboxHelper::PARAM_DAYS => $this->getRequest()
                                        ->getParam(ToolboxHelper::PARAM_DAYS),
                                    ToolboxHelper::PARAM_FORCE => $this->getRequest()
                                        ->getParam(ToolboxHelper::PARAM_FORCE),
                                    ToolboxHelper::PARAM_MARKETPLACE_NAME => $this->getRequest()
                                        ->getParam(ToolboxHelper::PARAM_MARKETPLACE_NAME),
                                    ToolboxHelper::PARAM_MARKETPLACE_SKU => $this->getRequest()
                                        ->getParam(ToolboxHelper::PARAM_MARKETPLACE_SKU),
                                    ToolboxHelper::PARAM_SHOP_ID => $this->getRequest()
                                        ->getParam(ToolboxHelper::PARAM_SHOP_ID),
                                ]
                            );
                        }
                        if (isset($result[ToolboxHelper::ERRORS][ToolboxHelper::ERROR_CODE])) {
                            $errorCode = $result[ToolboxHelper::ERRORS][ToolboxHelper::ERROR_CODE];
                            if ($errorCode === LengowConnector::CODE_404) {
                                $this->getResponse()->setStatusHeader(404, '1.1', 'Not Found');
                            } else {
                                $this->getResponse()->setStatusHeader(403, '1.1', 'Forbidden');
                            }
                        }
                        $this->getResponse()->setBody($this->jsonHelper->jsonEncode($result));
                        $this->getResponse()->setHeader('Content-Type','application/json', true);
                        break;
                    default:
                        $type = $this->getRequest()->getParam(ToolboxHelper::PARAM_TYPE, ToolboxHelper::DATA_TYPE_CMS);
                        $this->getResponse()->setBody(
                            $this->jsonHelper->jsonEncode($this->toolboxHelper->getData($type))
                        );
                        $this->getResponse()->setHeader('Content-Type','application/json', true);
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
