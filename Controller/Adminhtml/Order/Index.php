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

use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Import as LengowImport;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;

class Index extends Action
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface Magento store manager
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory Magento json factory instance
     */
    protected $_resultJsonFactory;

    /**
     * @var \Lengow\Connector\Model\Import Lengow import instance
     */
    protected $_import;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Import Lengow import helper instance
     */
    protected $_importHelper;

    /**
     * @var \Lengow\Connector\Helper\Sync Lengow sync helper instance
     */
    protected $_syncHelper;

    /**
     * @var \Lengow\Connector\Model\Import\OrderFactory Lengow order factory instance
     */
    protected $_lengowOrderFactory;

    /**
     * Constructor
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Magento store manager
     * @param \Magento\Backend\App\Action\Context $context Magento action context instance
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory Magento json factory instance
     * @param \Lengow\Connector\Helper\Sync $syncHelper Lengow sync helper instance
     * @param \Lengow\Connector\Helper\Import $importHelper Lengow import helper instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Model\Import $import Lengow import instance
     * @param \Lengow\Connector\Model\Import\OrderFactory $lengowOrderFactory Lengow order factory instance
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
    )
    {
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
                            $params = ['type' => LengowImport::TYPE_MANUAL];
                            $this->_import->init($params);
                            $results = $this->_import->exec();
                            $informations = $this->getInformations();
                            $informations['messages'] = $this->getMessages($results);
                            return $this->_resultJsonFactory->create()->setData(['informations' => $informations]);
                        case 're_import':
                            $orderLengowId = $this->getRequest()->getParam('order_lengow_id');
                            if ($orderLengowId !== null) {
                                $result = $this->_lengowOrderFactory->create()->reImportOrder((int)$orderLengowId);
                                $informations = $this->getInformations();
                                $informations['messages'] = $result;
                                return $this->_resultJsonFactory->create()->setData(['informations' => $informations]);
                            }
                            break;
                        case 're_send':
                            $orderLengowId = $this->getRequest()->getParam('order_lengow_id');
                            if ($orderLengowId !== null) {
                                $result = $this->_lengowOrderFactory->create()->reSendOrder((int)$orderLengowId);
                                $informations = $this->getInformations();
                                $informations['messages'] = $result;
                                return $this->_resultJsonFactory->create()->setData(['informations' => $informations]);
                            }
                            break;
                        case 'load_information':
                            $informations = $this->getInformations();
                            return $this->_resultJsonFactory->create()->setData(['informations' => $informations]);
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
                if ((int)$storeId > 0) {
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
     * Get all order informations
     *
     * @return array
     */
    public function getInformations()
    {
        $informations = [];
        $order = $this->_lengowOrderFactory->create();
        $informations['order_with_error'] = $this->_dataHelper->decodeLogMessage(
            $this->_dataHelper->setLogMessage(
                'You have %1 order(s) with errors',
                [$order->countOrderWithError()]
            )
        );
        $informations['order_to_be_sent'] = $this->_dataHelper->decodeLogMessage(
            $this->_dataHelper->setLogMessage(
                'You have %1 order(s) waiting to be sent',
                [$order->countOrderToBeSent()]
            )
        );
        $informations['last_importation'] = $this->_importHelper->getLastImportDatePrint();

        return $informations;
    }
}
