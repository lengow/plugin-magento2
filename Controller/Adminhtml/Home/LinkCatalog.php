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

namespace Lengow\Connector\Controller\Adminhtml\Home;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json as MagentoJsonResult;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Catalog as LengowCatalog;
use Lengow\Connector\Block\Adminhtml\Home\Content;

class LinkCatalog extends Action
{
    /**
     * @var PageFactory Magento page factory
     */
    protected $resultPageFactory;

    /**
     * @var JsonFactory Magento Json factory
     */
    protected $resultJsonFactory;

    /**
     * @var ConfigHelper Lengow config helper
     */
    protected $configHelper;

    /**
     * @var LengowCatalog Lengow catalog helper
     */
    protected $catalog;

    /**
     * View constructor
     * @param Context $context Magento context
     * @param PageFactory $resultPageFactory Magento page factory
     * @param JsonFactory $resultJsonFactory Magento Json factory
     * @param ConfigHelper $configHelper Lengow config helper
     * @param LengowCatalog $catalog Lengow catalog helper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        ConfigHelper $configHelper,
        LengowCatalog $catalog
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->configHelper = $configHelper;
        $this->catalog = $catalog;

        parent::__construct($context);
    }

    /**
     * Return the connection link catalog page
     *
     * @return MagentoJsonResult
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();
        $catalogsLinked = true;
        $catalogSelected = $this->getRequest()->getParam('catalogSelected') ?? [];
        if (!empty($catalogSelected)) {
            $catalogsLinked = $this->catalog->saveCatalogsLinked($catalogSelected);
        }
        $this->configHelper->cleanConfigCache();
        $block = $resultPage->getLayout()
            ->createBlock(Content::class)
            ->setTemplate('Lengow_Connector::home/catalog_failed.phtml')
            ->toHtml();
        $result->setData(['output' => $block, 'success' => $catalogsLinked]);
        return $result;
    }
}
