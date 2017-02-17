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
 * @subpackage  Block
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Block\Adminhtml\Product;

use Lengow\Connector\Model\Config\Source\Type as SourceType;
use Magento\Catalog\Model\Product\AttributeSet\Options as AttributeSetOptions;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data as HelperData;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\WebsiteFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var SourceType
     */
    protected $_sourceType;

    /**
     * @var AttributeSetOptions
     */
    protected $_attributeSetOptions;

    /**
     * @var ProductVisibility
     */
    protected $_productVisibility;

    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    protected $_websiteFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    protected $_status;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Model\Product\AttributeSet\Options $attributeSetOptions
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $status
     * @param SourceType $sourceType
     * @param ProductVisibility $productVisibility
     * @param array $data
     */
    public function __construct(
        Context $context,
        HelperData $backendHelper,
        ProductCollectionFactory $collectionFactory,
        AttributeSetOptions $attributeSetOptions,
        WebsiteFactory $websiteFactory,
        ProductStatus $status,
        SourceType $sourceType,
        ProductVisibility $productVisibility,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_sourceType = $sourceType;
        $this->_attributeSetOptions = $attributeSetOptions;
        $this->_productVisibility = $productVisibility;
        $this->_websiteFactory = $websiteFactory;
        $this->_status = $status;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->setId('lengow_product_grid');
        parent::_construct();
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
        $this->setVarNameFilter('product_filter');
    }

    /**
     * Get store
     * TODO dans Header également
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    protected function _getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        if ($storeId == 0) {
            $storeId = $this->_storeManager->getDefaultStoreView()->getId();
        }
        return $this->_storeManager->getStore($storeId);
    }

    /**
     * Prepare collection
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    protected function _prepareCollection()
    {
        $collection = $this->_collectionFactory->create();
        $collection->addAttributeToSelect('sku')
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('lengow_product')
                    ->joinField(
                        'qty',
                        'cataloginventory_stock_item',
                        'qty',
                        'product_id=entity_id',
                        '{{table}}.stock_id=1',
                        'left'
                    )
                    ->addStoreFilter($this->_getStore())
                    ->addAttributeToFilter('type_id', array('nlike' => 'bundle'));
        $collection->joinAttribute(
            'price',
            'catalog_product/price',
            'entity_id',
            null,
            'left',
            $this->_getStore()->getId()
        );
        $collection->joinAttribute(
            'status',
            'catalog_product/status',
            'entity_id',
            null,
            'inner',
            $this->_getStore()->getId()
        );
        $collection->joinAttribute(
            'visibility',
            'catalog_product/visibility',
            'entity_id',
            null,
            'inner',
            $this->_getStore()->getId()
        );
        $this->setCollection($collection);

        $this->getCollection()->addWebsiteNamesToResult();

        parent::_prepareCollection();
        return $this;
    }

    /**
     * Prepare columns
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareColumns()
    {
        // create type filter without bundle type product
        $types = $this->_sourceType->toOptionArray();
        $type = [];
        foreach ($types as $value) {
            $type[$value['value']] = $value['label'];
        }
        $store = $this->_getStore();
        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'index'  => 'entity_id',
                'width' => 100,
                'type'   => 'number',
            ]
        );
        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index'  => 'name',
            ]
        );
        $this->addColumn(
            'type',
            [
                'header'  => __('Type'),
                'index'   => 'type_id',
                'width'   => '60px',
                'type'    => 'options',
                'options' => $type,
            ]
        );
        $sets = $this->_attributeSetOptions->toOptionArray();
        $set = [];
        foreach ($sets as $value) {
            $set[$value['value']] = $value['label'];
        }
        $this->addColumn(
            'set_name',
            [
                'header'  => __('Attribut set name'),
                'index'   => 'attribute_set_id',
                'width'   => '100px',
                'type'    => 'options',
                'options' => $set,
            ]
        );
        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'index'  => 'sku',
                'width' => 100,
            ]
        );
        $this->addColumn(
            'price',
            [
                'header'        => __('Price'),
                'index'         => 'price',
                'type'          => 'price',
                'currency_code' => $store->getCurrentCurrency()->getCode(),
            ]
        );
        $this->addColumn(
            'quantity_and_stock_status',
            [
                'header' => __('Quantity'),
                'index'  => 'qty',
                'width'  => '100px',
                'type'   => 'number',
            ]
        );
        $this->addColumn(
            'visibility',
            [
                'header'  => __('Visibility'),
                'width'   => '70px',
                'index'   => 'visibility',
                'type'    => 'options',
                'options' => $this->_productVisibility->getOptionArray(),
            ]
        );
        $this->addColumn(
            'status',
            [
                'header'  => __('Status'),
                'width'   => '70px',
                'index'   => 'status',
                'type'    => 'options',
                'options' => $this->_status->getOptionArray()
            ]
        );
        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn(
                'websites',
                [
                    'header' => __('Websites'),
                    'sortable' => false,
                    'index' => 'websites',
                    'type' => 'options',
                    'options' => $this->_websiteFactory->create()->getCollection()->toOptionHash()
                ]
            );
        }
        $this->addColumn(
            'lengow_product',
            [
                'header'   => __('Include in export ?'),
                'index'    => 'lengow_product',
                'width'    => '70px',
//                'type'     => 'text',
                'type'     => 'options',
//                'renderer' => 'Lengow_Connector_Block_Adminhtml_Product_Renderer_Lengow',
                'options'  => [
                    0 => __('No'),
                    1 => __('Yes')
                ],
            ]
        );

        return parent::_prepareColumns();
    }

}
