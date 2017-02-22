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
use Magento\Catalog\Model\Product\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;


class MassPublish extends Product
{
    /**
     * @var Context
     */
    protected $_context;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;


    /**
     * @param Context $context
     * @param Product\Builder $productBuilder
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Product\Builder $productBuilder,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context, $productBuilder);
        $this->_context = $context;
        $this->productBuilder = $productBuilder;
        $this->_storeManager = $storeManager;
    }

    /**
     * Update product(s) publish action
     *
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $product_ids = $this->getRequest()->getParam('product');
        $store_id = (integer)$this->getRequest()->getParam('store', $this->_storeManager->getDefaultStoreView()->getId());
        $publish = (integer)$this->getRequest()->getParam('publish');
        try {
        $this->_objectManager->get(Action::class)
            ->updateAttributes($product_ids, ['lengow_product' => $publish], $store_id);
        } catch (\Exception $e) {
            $this->_getSession()->addException($e, __('Something went wrong while updating the lengow product(s) attribute.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('lengow/*/', ['store' => $store_id]);
    }

}