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

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Controller\Adminhtml\Product;
use Magento\Catalog\Controller\Adminhtml\Product\Builder as ProductBuilder;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Store\Model\StoreManagerInterface;

class MassPublish extends Product
{
    /**
     * @var Context Magento action context instance
     */
    protected $_context;

    /**
     * @var StoreManagerInterface Magento store manager instance
     */
    protected $_storeManager;

    /**
     * @var ProductAction Magento product action instance
     */
    protected $_productAction;

    /**
     * @param Context $context Magento action context instance
     * @param ProductBuilder $productBuilder Magento product builder instance
     * @param StoreManagerInterface $storeManager Magento store manager instance
     * @param ProductAction $productAction Magento product action instance
     */
    public function __construct(
        Context $context,
        ProductBuilder $productBuilder,
        StoreManagerInterface $storeManager,
        ProductAction $productAction
    ) {
        parent::__construct($context, $productBuilder);
        $this->_context = $context;
        $this->productBuilder = $productBuilder;
        $this->_storeManager = $storeManager;
        $this->_productAction = $productAction;
    }

    /**
     * Update product(s) publish action
     */
    public function execute()
    {
        $productIds = $this->getRequest()->getParam('product');
        $storeId = (int) $this->getRequest()->getParam('store', $this->_storeManager->getDefaultStoreView()->getId());
        $publish = (int) $this->getRequest()->getParam('publish');
        try {
            $this->_productAction->updateAttributes($productIds, ['lengow_product' => $publish], $storeId);
        } catch (\Exception $e) {
            $this->_getSession()->addException(
                $e,
                __('Something went wrong while updating the lengow product(s) attribute.')
            );
        }
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('lengow/*/', ['store' => $storeId]);
    }
}
