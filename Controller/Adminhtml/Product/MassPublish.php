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
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Catalog\Model\Product\Action;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;


class MassPublish extends \Magento\Catalog\Controller\Adminhtml\Product
{
    /**
     * MassActions filter
     *
     * @var Filter
     */
    protected $_filter;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

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
     * @param Processor $productPriceIndexerProcessor
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Product\Builder $productBuilder,
        Processor $productPriceIndexerProcessor,
        Filter $filter,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->_filter = $filter;
        $this->_collectionFactory = $collectionFactory;
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
        $collection = $this->_filter->getCollection($this->_collectionFactory->create());
        $productIds = $collection->getAllIds();
        $status = (int) $this->getRequest()->getParam('status');

        // TODO - search store_id parameter
        $url= $this->_redirect->getRefererUrl();
        $path = parse_url($url, PHP_URL_PATH);
        $store = strpos($path, 'store');

        if ($store !== false) {
            preg_match('/(?<=store\/)\d/',$path, $storeId);
            $store = $storeId[0];
        } else {
            $store = $this->_storeManager->getDefaultStoreView()->getId();
        }

        try {
        $this->_objectManager->get(Action::class)
            ->updateAttributes($productIds, ['lengow_product' => $status], $store);
        } catch (\Exception $e) {
            $this->_getSession()->addException($e, __('Something went wrong while updating the lengow product(s) attribute.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('lengow/*/');
    }

}