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
    protected $_productAction;

    /**
     * @var JsonFactory Magento json factory instance
     */
    protected $_resultJsonFactory;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var SyncHelper Lengow sync helper instance
     */
    protected $_syncHelper;

    /**
     * @var LengowExport Lengow export instance
     */
    protected $_export;

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
    )
    {
        $this->_productAction = $productAction;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_configHelper = $configHelper;
        $this->_dataHelper = $dataHelper;
        $this->_syncHelper = $syncHelper;
        $this->_export = $export;
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
                        case 'change_option_selected':
                            $state = $this->getRequest()->getParam('state');
                            $storeId = $this->getRequest()->getParam('store_id');
                            if ($state !== null) {
                                $oldValue = $this->_configHelper->get('selection_enable', $storeId);
                                $this->_configHelper->set('selection_enable', $state, $storeId);
                                // clean config cache to valid configuration
                                $this->_configHelper->cleanConfigCache();
                                $this->_dataHelper->log(
                                    DataHelper::CODE_SETTING,
                                    $this->_dataHelper->setLogMessage(
                                        '%1 - old value %2 replaced with %3 for store %4',
                                        [
                                            'lengow_export_options/simple/export_selection_enable',
                                            $oldValue,
                                            $state,
                                            $storeId,
                                        ]
                                    )
                                );
                                $this->_export->init(['store_id' => $storeId, 'selection' => $state]);
                                return $this->_resultJsonFactory->create()->setData(
                                    [
                                        'state' => $state,
                                        'exported' => $this->_export->getTotalExportedProduct(),
                                        'total' => $this->_export->getTotalProduct(),
                                    ]
                                );
                            }
                            break;
                        case 'lengow_export_product':
                            $storeId = $this->getRequest()->getParam('store_id');
                            $state = $this->getRequest()->getParam('state');
                            $productId = $this->getRequest()->getParam('product_id');
                            if ($state !== null) {
                                $this->_productAction->updateAttributes(
                                    [$productId],
                                    ['lengow_product' => $state],
                                    $storeId
                                );
                                $this->_export->init(['store_id' => $storeId, 'selection' => 1]);
                                return $this->_resultJsonFactory->create()->setData(
                                    [
                                        'exported' => $this->_export->getTotalExportedProduct(),
                                        'total' => $this->_export->getTotalProduct(),
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
}
