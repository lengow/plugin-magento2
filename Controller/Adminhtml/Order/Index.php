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
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Model\Import as LengowImport;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;

class Index extends Action
{
    /**
     * @var StoreManagerInterface Magento store manager
     */
    protected $_storeManager;

    /**
     * @var JsonFactory Magento json factory instance
     */
    protected $_resultJsonFactory;

    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var LengowImport Lengow import helper instance
     */
    protected $_importHelper;

    /**
     * @var SyncHelper Lengow sync helper instance
     */
    protected $_syncHelper;

    /**
     * @var LengowImport Lengow import instance
     */
    protected $_import;

    /**
     * @var LengowOrderFactory Lengow order factory instance
     */
    protected $_lengowOrderFactory;

    /**
     * Constructor
     *
     * @param StoreManagerInterface $storeManager Magento store manager
     * @param Context $context Magento action context instance
     * @param JsonFactory $resultJsonFactory Magento json factory instance
     * @param SyncHelper $syncHelper Lengow sync helper instance
     * @param ImportHelper $importHelper Lengow import helper instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param LengowImport $import Lengow import instance
     * @param LengowOrderFactory $lengowOrderFactory Lengow order factory instance
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Context $context,
        JsonFactory $resultJsonFactory,
        SyncHelper $syncHelper,
        ImportHelper $importHelper,
        DataHelper $dataHelper,
        LengowImport $import,
        LengowOrderFactory $lengowOrderFactory
    ) {
        $this->_storeManager = $storeManager;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_syncHelper = $syncHelper;
        $this->_importHelper = $importHelper;
        $this->_dataHelper = $dataHelper;
        $this->_import = $import;
        $this->_lengowOrderFactory = $lengowOrderFactory;
        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return mixed
     */
    public function execute()
    {
        if ($this->_syncHelper->pluginIsBlocked()) {
            $this->_redirect('lengow/home/index');
        } else {
            if ($this->getRequest()->getParam('isAjax')) {
                $action = $this->getRequest()->getParam('action');
                if ($action) {
                    switch ($action) {
                        case 'import_all':
                            $params = [LengowImport::PARAM_TYPE => LengowImport::TYPE_MANUAL];
                            $this->_import->init($params);
                            $results = $this->_import->exec();
                            $information = $this->getInformation();
                            $information['messages'] = $this->getMessages($results);
                            return $this->_resultJsonFactory->create()->setData(['informations' => $information]);
                        case 're_import':
                            $orderLengowId = $this->getRequest()->getParam('order_lengow_id');
                            if ($orderLengowId !== null) {
                                $result = $this->_lengowOrderFactory->create()->reImportOrder((int) $orderLengowId);
                                $information = $this->getInformation();
                                $information['messages'] = $result;
                                return $this->_resultJsonFactory->create()->setData(['informations' => $information]);
                            }
                            break;
                        case 're_send':
                            $orderLengowId = $this->getRequest()->getParam('order_lengow_id');
                            if ($orderLengowId !== null) {
                                $result = $this->_lengowOrderFactory->create()->reSendOrder((int) $orderLengowId);
                                $information = $this->getInformation();
                                $information['messages'] = $result;
                                return $this->_resultJsonFactory->create()->setData(['informations' => $information]);
                            }
                            break;
                        case 'load_information':
                            $information = $this->getInformation();
                            return $this->_resultJsonFactory->create()->setData(['informations' => $information]);
                            break;
                    }
                }
            } else {
                $this->_view->loadLayout();
                $this->_view->renderLayout();
            }
        }
    }

    /**
     * Get Messages
     *
     * @param array $results results from import process
     *
     * @return array
     */
    public function getMessages($results)
    {
        $messages = [];
        // if global error return this
        if (isset($results['error'][0])) {
            $messages[] = $this->_dataHelper->decodeLogMessage($results['error'][0]);
            return $messages;
        }
        if (isset($results['order_new']) && $results['order_new'] > 0) {
            $messages[] = $this->_dataHelper->decodeLogMessage(
                '%1 order(s) imported',
                true,
                [$results['order_new']]
            );
        }
        if (isset($results['order_update']) && $results['order_update'] > 0) {
            $messages[] = $this->_dataHelper->decodeLogMessage(
                '%1 order(s) updated',
                true,
                [$results['order_update']]
            );
        }
        if (isset($results['order_error']) && $results['order_error'] > 0) {
            $messages[] = $this->_dataHelper->decodeLogMessage(
                '%1 order(s) with errors',
                true,
                [$results['order_error']]
            );
        }
        if (empty($messages)) {
            $messages[] = $this->_dataHelper->decodeLogMessage('No new notification on order');
        }
        if (isset($results['error'])) {
            foreach ($results['error'] as $storeId => $values) {
                if ((int) $storeId > 0) {
                    try {
                        $store = $this->_storeManager->getStore($storeId);
                        $storeName = $store->getName() . ' (' . $store->getId() . ') : ';
                    } catch (\Exception $e) {
                        $storeName = 'Store id ' . $storeId;
                    }
                    $messages[] = $storeName . $this->_dataHelper->decodeLogMessage($values);
                }
            }
        }
        return $messages;
    }

    /**
     * Get all order information
     *
     * @return array
     */
    public function getInformation()
    {
        $information = [];
        $order = $this->_lengowOrderFactory->create();
        $information['order_with_error'] = $this->_dataHelper->decodeLogMessage(
            $this->_dataHelper->setLogMessage(
                'You have %1 order(s) with errors',
                [$order->countOrderWithError()]
            )
        );
        $information['order_to_be_sent'] = $this->_dataHelper->decodeLogMessage(
            $this->_dataHelper->setLogMessage(
                'You have %1 order(s) waiting to be sent',
                [$order->countOrderToBeSent()]
            )
        );
        $information['last_importation'] = $this->_importHelper->getLastImportDatePrint();

        return $information;
    }
}
