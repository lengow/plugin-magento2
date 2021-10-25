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

namespace Lengow\Connector\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Model\Export as LengowExport;

class Index extends Action
{
    /**
     * @var ProductAction Magento product action instance
     */
    private $productAction;

    /**
     * @var JsonFactory Magento json factory instance
     */
    private $resultJsonFactory;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var SyncHelper Lengow sync helper instance
     */
    private $syncHelper;

    /**
     * @var LengowExport Lengow export instance
     */
    private $export;

    /**
     * Constructor
     *
     * @param Context $context Magento action context instance
     * @param ProductAction $productAction Magento product action instance
     * @param JsonFactory $resultJsonFactory Magento json factory instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param SyncHelper $syncHelper Lengow sync helper instance
     * @param LengowExport $export Lengow export instance
     */
    public function __construct(
        Context $context,
        ProductAction $productAction,
        JsonFactory $resultJsonFactory,
        ConfigHelper $configHelper,
        DataHelper $dataHelper,
        SyncHelper $syncHelper,
        LengowExport $export
    ) {
        $this->productAction = $productAction;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->syncHelper = $syncHelper;
        $this->export = $export;
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
                    case 'change_option_selected':
                        $state = $this->getRequest()->getParam('state');
                        $storeId = (int) $this->getRequest()->getParam('store_id');
                        if ($state !== null) {
                            $oldValue = $this->configHelper->get(ConfigHelper::SELECTION_ENABLED, $storeId);
                            $this->configHelper->set(ConfigHelper::SELECTION_ENABLED, $state, $storeId);
                            // clean config cache to valid configuration
                            $this->configHelper->cleanConfigCache();
                            $this->dataHelper->log(
                                DataHelper::CODE_SETTING,
                                $this->dataHelper->setLogMessage(
                                    '%1 - old value %2 replaced with %3 for store %4',
                                    [
                                        'lengow_export_options/simple/export_selection_enable',
                                        $oldValue,
                                        $state,
                                        $storeId,
                                    ]
                                )
                            );
                            $this->export->init([
                                LengowExport::PARAM_STORE_ID => $storeId,
                                LengowExport::PARAM_SELECTION => $state,
                            ]);
                            return $this->resultJsonFactory->create()->setData(
                                [
                                    'state' => $state,
                                    'exported' => $this->export->getTotalExportProduct(),
                                    'total' => $this->export->getTotalProduct(),
                                ]
                            );
                        }
                        break;
                    case 'lengow_export_product':
                        $storeId = (int) $this->getRequest()->getParam('store_id');
                        $state = $this->getRequest()->getParam('state');
                        $productId = $this->getRequest()->getParam('product_id');
                        if ($state !== null) {
                            $this->productAction->updateAttributes(
                                [$productId],
                                ['lengow_product' => $state],
                                $storeId
                            );
                            $this->export->init([
                                LengowExport::PARAM_STORE_ID => $storeId,
                                LengowExport::PARAM_SELECTION => 1,
                            ]);
                            return $this->resultJsonFactory->create()->setData(
                                [
                                    'exported' => $this->export->getTotalExportProduct(),
                                    'total' => $this->export->getTotalProduct(),
                                ]
                            );
                        }
                        break;
                }
            }
        } else {
            $this->_view->loadLayout();
            $this->_view->renderLayout();
        }
    }
}
