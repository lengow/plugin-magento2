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
use Lengow\Connector\Block\Adminhtml\Main;

class Cms extends Action
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
     * View constructor
     * @param Context $context Magento context
     * @param PageFactory $resultPageFactory Magento page factory
     * @param JsonFactory $resultJsonFactory Magento Json factory
     */
    public function __construct(Context $context, PageFactory $resultPageFactory, JsonFactory $resultJsonFactory)
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    /**
     * Return the connection base page
     *
     * @return MagentoJsonResult
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();
        $block = $resultPage->getLayout()
            ->createBlock(Main::class)
            ->setTemplate('Lengow_Connector::home/cms.phtml')
            ->toHtml();
        $result->setData(['output' => $block]);
        return $result;
    }
}
