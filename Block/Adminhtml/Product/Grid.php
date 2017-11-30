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
use Lengow\Connector\Block\Widget\Grid\Extended;
use Magento\Catalog\Model\Product\AttributeSet\Options as AttributeSetOptions;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data as BackendHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\WebsiteFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;

class Grid extends Extended
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory Magento product collection factory instance
     */
    protected $_collectionFactory;

    /**
     * @var \Lengow\Connector\Model\Config\Source\Type Lengow config source type instance
     */
    protected $_sourceType;

    /**
     * @var \Magento\Catalog\Model\Product\AttributeSet\Options Magento attribute set options instance
     */
    protected $_attributeSetOptions;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility Magento product visibility instance
     */
    protected $_productVisibility;

    /**
     * @var \Magento\Store\Model\WebsiteFactory Magento website factory instance
     */
    protected $_websiteFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status Magento product attribute status instance
     */
    protected $_status;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context Magento block context instance
     * @param \Magento\Backend\Helper\Data $backendHelper Magento backend helper instance
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Model\Product\AttributeSet\Options $attributeSetOptions Magento attribute option instance
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory Magento website factory instance
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $status Magento attribute status instance
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility Magento product visibility instance
     * @param \Lengow\Connector\Model\Config\Source\Type $sourceType Magento source type instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        ProductCollectionFactory $collectionFactory,
        AttributeSetOptions $attributeSetOptions,
        WebsiteFactory $websiteFactory,
        ProductStatus $status,
        ProductVisibility $productVisibility,
        SourceType $sourceType,
        DataHelper $dataHelper,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_sourceType = $sourceType;
        $this->_attributeSetOptions = $attributeSetOptions;
        $this->_productVisibility = $productVisibility;
        $this->_websiteFactory = $websiteFactory;
        $this->_status = $status;
        $this->_dataHelper = $dataHelper;
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
     * Prepare collection
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    protected function _prepareCollection()
    {
        $collection = $this->_collectionFactory->create();
        $collection->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('thumbnail')
            ->joinField(
                'qty',
                'cataloginventory_stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            )
            ->addStoreFilter($this->_dataHelper->getStore())
            ->addAttributeToFilter('type_id', ['nlike' => 'bundle']);
        $collection->joinAttribute(
            'lengow_product',
            'catalog_product/lengow_product',
            'entity_id',
            null,
            'left',
            $this->_dataHelper->getStore()->getId()
        );
        $collection->joinAttribute(
            'price',
            'catalog_product/price',
            'entity_id',
            null,
            'left',
            $this->_dataHelper->getStore()->getId()
        );
        $collection->joinAttribute(
            'status',
            'catalog_product/status',
            'entity_id',
            null,
            'inner',
            $this->_dataHelper->getStore()->getId()
        );
        $collection->joinAttribute(
            'visibility',
            'catalog_product/visibility',
            'entity_id',
            null,
            'inner',
            $this->_dataHelper->getStore()->getId()
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
        $store = $this->_dataHelper->getStore();
        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'index' => 'entity_id',
                'type' => 'number',
            ]
        );
        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index' => 'name',
            ]
        );
        $this->addColumn(
            'image',
            [
                'header' => __('Image'),
                'index' => 'image',
                'renderer' => '\Lengow\Connector\Block\Adminhtml\Product\Grid\Renderer\Image',
                'column_css_class' => 'data-grid-thumbnail-cell',
                'filter' => false
            ]
        );
        $this->addColumn(
            'type',
            [
                'header' => __('Type'),
                'index' => 'type_id',
                'column_css_class' => 'a-center',
                'type' => 'options',
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
                'header' => __('Attribut set name'),
                'index' => 'attribute_set_id',
                'column_css_class' => 'a-center',
                'type' => 'options',
                'options' => $set,
            ]
        );
        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'index' => 'sku',
                'column_css_class' => 'a-center',
            ]
        );
        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'index' => 'price',
                'type' => 'price',
                'currency_code' => $store->getCurrentCurrency()->getCode(),
            ]
        );
        $this->addColumn(
            'quantity_and_stock_status',
            [
                'header' => __('Quantity'),
                'index' => 'qty',
                'type' => 'number',
                'column_css_class' => 'a-center',
            ]
        );
        $this->addColumn(
            'visibility',
            [
                'header' => __('Visibility'),
                'index' => 'visibility',
                'type' => 'options',
                'options' => $this->_productVisibility->getOptionArray(),
            ]
        );
        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'column_css_class' => 'a-center',
                'index' => 'status',
                'type' => 'options',
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
                    'options' => $this->_websiteFactory->create()->getCollection()->toOptionHash(),
                    'filter' => false
                ]
            );
        }
        $this->addColumn(
            'lengow_product',
            [
                'header' => __('Include in export ?'),
                'index' => 'lengow_product',
                'type' => 'options',
                'renderer' => 'Lengow\Connector\Block\Adminhtml\Product\Grid\Renderer\Lengow',
                'column_css_class' => 'a-center',
                'options' => [
                    1 => __('Yes')
                ],
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Prepare mass action buttons
     *
     * @return $this
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setTemplate('Magento_Catalog::product/grid/massaction_extended.phtml');
        $this->getMassactionBlock()->setFormFieldName('product');

        $this->getMassactionBlock()->addItem(
            'publish',
            [
                'label' => __('Publish in Lengow'),
                'url' => $this->getUrl('*/*/massPublish', ['_current' => true, 'publish' => true]),
                'complete' => 'reloadGrid'
            ]
        );
        $this->getMassactionBlock()->addItem(
            'unpublish',
            [
                'label' => __('Unpublish in Lengow'),
                'url' => $this->getUrl('*/*/massPublish', ['_current' => true, 'publish' => false]),
                'complete' => 'reloadGrid'
            ]
        );
        return $this;
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('lengow/product/index', ['_current' => true]);
    }

    /**
     * Inline editing action
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
            'catalog/*/edit',
            ['store' => $this->getRequest()->getParam('store'), 'id' => $row->getId()]
        );
    }
}
