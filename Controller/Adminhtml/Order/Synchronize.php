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

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;

class Synchronize extends Action
{
    /**
     * @var \Lengow\Connector\Model\Import\Order Lengow order instance
     */
    protected $_lengowOrder;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Model\Import\OrderFactory Lengow order instance
     */
    protected $_lengowOrderFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context Magento action context instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Model\Import\Order $lengowOrder Lengow order instance
     * @param \Lengow\Connector\Model\Import\OrderFactory $lengowOrderFactory Lengow order instance
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        LengowOrder $lengowOrder,
        LengowOrderFactory $lengowOrderFactory
    )
    {
        $this->_dataHelper = $dataHelper;
        $this->_lengowOrder = $lengowOrder;
        $this->_lengowOrderFactory = $lengowOrderFactory;
        parent::__construct($context);
    }

    /**
     * Synchronize action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $lengowOrderId = $this->getRequest()->getParam('lengow_order_id');
        $lengowOrder = $this->_lengowOrderFactory->create()->load($lengowOrderId);
        if ($lengowOrder) {
            $synchro = $this->_lengowOrder->synchronizeOrder($lengowOrder);
            if ($synchro) {
                $synchroMessage = $this->_dataHelper->setLogMessage(
                    'order successfully synchronised with Lengow webservice (ORDER ID %1)',
                    [$lengowOrder->getData('order_sku')]
                );
            } else {
                $synchroMessage = $this->_dataHelper->setLogMessage(
                    'WARNING! Order could NOT be synchronised with Lengow webservice (ORDER ID %1)',
                    [$lengowOrder->getData('order_sku')]
                );
            }
            $this->_dataHelper->log('Import', $synchroMessage, false, $lengowOrder->getData('marketplace_sku'));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
    }
}
