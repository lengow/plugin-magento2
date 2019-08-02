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
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Import\OrderFactory;

class MassReImport extends Action
{
    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Model\Import\OrderFactory Lengow order factory instance
     */
    protected $_orderFactory;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context Magento action context instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Model\Import\OrderFactory $orderFactory Lengow order factory instance
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        OrderFactory $orderFactory
    )
    {
        $this->_dataHelper = $dataHelper;
        $this->_orderFactory = $orderFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $selectedIds = $this->getRequest()->getParam('selected', null);
        $excludedIds = $this->getRequest()->getParam('excluded', []);
        $excludedIds = $excludedIds === 'false' ? [] : $excludedIds;
        if (empty($selectedIds)) {
            $ids = [];
            $allLengowOrderIds = $this->_orderFactory->create()->getAllLengowOrderIds();
            if ($allLengowOrderIds) {
                foreach ($allLengowOrderIds as $lengowOrderId) {
                    if (!in_array($lengowOrderId['id'], $excludedIds)) {
                        $ids[] = $lengowOrderId['id'];
                    }
                }
            }
        } else {
            $ids = $selectedIds;
        }
        // if ids is empty -> do nothing
        if (!is_array($ids) || empty($ids)) {
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/index', ['_current' => true]);
        }
        // reimport all selected orders
        $totalReSent = 0;
        foreach ($ids as $orderLengowId) {
            $result = $this->_orderFactory->create()->reImportOrder((int)$orderLengowId);
            if ($result && isset($result['order_new']) && $result['order_new']) {
                $totalReSent++;
            }
        }
        // get the number of orders correctly reimported
        $this->messageManager->addSuccessMessage(
            $this->_dataHelper->decodeLogMessage(
                'A total of %1 order(s) in %2 selected have been imported.',
                true,
                [$totalReSent, count($ids)]
            )
        );
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/index', ['_current' => true]);
    }
}