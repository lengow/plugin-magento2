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

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
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
    private $storeManager;

    /**
     * @var JsonFactory Magento json factory instance
     */
    private $resultJsonFactory;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var LengowImport Lengow import helper instance
     */
    private $importHelper;

    /**
     * @var SyncHelper Lengow sync helper instance
     */
    private $syncHelper;

    /**
     * @var LengowImport Lengow import instance
     */
    private $import;

    /**
     * @var LengowOrderFactory Lengow order factory instance
     */
    private $lengowOrderFactory;

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
        $this->storeManager = $storeManager;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->syncHelper = $syncHelper;
        $this->importHelper = $importHelper;
        $this->dataHelper = $dataHelper;
        $this->import = $import;
        $this->lengowOrderFactory = $lengowOrderFactory;
        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return Json|void
     */
    public function execute()
    {
        if ($this->syncHelper->pluginIsBlocked()) {
            $this->_redirect('lengow/home/index');
        } elseif ($this->getRequest()->getParam('isAjax')) {
            $action = $this->getRequest()->getParam('action');
            if ($action) {
                switch ($action) {
                    case 'import_all':
                        $params = [LengowImport::PARAM_TYPE => LengowImport::TYPE_MANUAL];
                        $this->import->init($params);
                        $results = $this->import->exec();
                        $information = $this->getInformation();
                        $information['messages'] = $this->getMessages($results);
                        return $this->resultJsonFactory->create()->setData(['informations' => $information]);
                    case 're_import':
                        $orderLengowId = $this->getRequest()->getParam('order_lengow_id');
                        if ($orderLengowId !== null) {
                            $this->lengowOrderFactory->create()->reImportOrder((int) $orderLengowId);
                            $information = $this->getInformation();
                            return $this->resultJsonFactory->create()->setData(['informations' => $information]);
                        }
                        break;
                    case 're_send':
                        $orderLengowId = $this->getRequest()->getParam('order_lengow_id');
                        if ($orderLengowId !== null) {
                            $this->lengowOrderFactory->create()->reSendOrder((int) $orderLengowId);
                            $information = $this->getInformation();
                            return $this->resultJsonFactory->create()->setData(['informations' => $information]);
                        }
                        break;
                    case 'load_information':
                        $information = $this->getInformation();
                        return $this->resultJsonFactory->create()->setData(['informations' => $information]);
                }
            }
        } else {
            $this->_view->loadLayout();
            $this->_view->renderLayout();
        }
    }

    /**
     * Get Messages
     *
     * @param array $results results from import process
     *
     * @return array
     */
    public function getMessages(array $results): array
    {
        $messages = [];
        // if global error return this
        if (isset($results[LengowImport::ERRORS][0])) {
            $messages[] = $this->dataHelper->decodeLogMessage($results[LengowImport::ERRORS][0]);
            return $messages;
        }
        if (isset($results[LengowImport::NUMBER_ORDERS_CREATED]) && $results[LengowImport::NUMBER_ORDERS_CREATED] > 0) {
            $messages[] = $this->dataHelper->decodeLogMessage(
                '%1 order(s) imported',
                true,
                [$results[LengowImport::NUMBER_ORDERS_CREATED]]
            );
        }
        if (isset($results[LengowImport::NUMBER_ORDERS_UPDATED]) && $results[LengowImport::NUMBER_ORDERS_UPDATED] > 0) {
            $messages[] = $this->dataHelper->decodeLogMessage(
                '%1 order(s) updated',
                true,
                [$results[LengowImport::NUMBER_ORDERS_UPDATED]]
            );
        }
        if (isset($results[LengowImport::NUMBER_ORDERS_FAILED]) && $results[LengowImport::NUMBER_ORDERS_FAILED] > 0) {
            $messages[] = $this->dataHelper->decodeLogMessage(
                '%1 order(s) with errors',
                true,
                [$results[LengowImport::NUMBER_ORDERS_FAILED]]
            );
        }
        if (empty($messages)) {
            $messages[] = $this->dataHelper->decodeLogMessage('No new notification on order');
        }
        if (isset($results[LengowImport::ERRORS])) {
            foreach ($results[LengowImport::ERRORS] as $storeId => $values) {
                if ((int) $storeId > 0) {
                    try {
                        $store = $this->storeManager->getStore($storeId);
                        $storeName = $store->getName() . ' (' . $store->getId() . ') : ';
                    } catch (Exception $e) {
                        $storeName = 'Store id ' . $storeId;
                    }
                    $messages[] = $storeName . $this->dataHelper->decodeLogMessage($values);
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
    public function getInformation(): array
    {
        $information = [];
        $order = $this->lengowOrderFactory->create();
        $information['order_with_error'] = $this->dataHelper->decodeLogMessage(
            $this->dataHelper->setLogMessage(
                'You have %1 order(s) with errors',
                [$order->countOrderWithError()]
            )
        );
        $information['order_to_be_sent'] = $this->dataHelper->decodeLogMessage(
            $this->dataHelper->setLogMessage(
                'You have %1 order(s) waiting to be sent',
                [$order->countOrderToBeSent()]
            )
        );
        $information['last_importation'] = $this->importHelper->getLastImportDatePrint();

        return $information;
    }
}
