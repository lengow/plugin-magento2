<?php

namespace Lengow\Connector\Block\Adminhtml\Product;

use Lengow\Connector\Model\Config\Source\Type as SourceType;
use Magento\Catalog\Model\Product\AttributeSet\Options as AttributeSetOptions;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;

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
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $status
     * @param SourceType $sourceType
     * @param ProductVisibility $productVisibility
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\Catalog\Model\Product\AttributeSet\Options $attributeSetOptions,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $status,
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
     */
    protected function _getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        // set default store if storeId is global
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
                        'qty', 'cataloginventory_stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left'
                    )
                    ->addStoreFilter($this->_getStore())
                    ->addAttributeToFilter('type_id', array('nlike' => 'bundle'));
        $collection->joinAttribute('price', 'catalog_product/price', 'entity_id', null, 'left', $this->_getStore()->getId());
        $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner', $this->_getStore()->getId());
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
            array(
                'header' => __('ID'),
                'index'  => 'entity_id',
                'width' => 100,
                'type'   => 'number',
            )
        );
        $this->addColumn(
            'name',
            array(
                'header' => __('Name'),
                'index'  => 'name',
            )
        );
        $this->addColumn(
            'type',
            array(
                'header'  => __('Type'),
                'index'   => 'type_id',
                'width'   => '60px',
                'type'    => 'options',
                'options' => $type,
            )
        );
        $sets = $this->_attributeSetOptions->toOptionArray();
        $set = [];
        foreach ($sets as $value) {
            $set[$value['value']] = $value['label'];
        }
        $this->addColumn(
            'set_name',
            array(
                'header'  => __('Attribut set name'),
                'index'   => 'attribute_set_id',
                'width'   => '100px',
                'type'    => 'options',
                'options' => $set,
            )
        );
        $this->addColumn(
            'sku',
            array(
                'header' => __('SKU'),
                'index'  => 'sku',
                'width' => 100,
            )
        );
        $this->addColumn(
            'price',
            array(
                'header'        => __('Price'),
                'index'         => 'price',
                'type'          => 'price',
                'currency_code' => $store->getCurrentCurrency()->getCode(),
            )
        );
        $this->addColumn(
            'quantity_and_stock_status',
            array(
                'header' => __('Quantity'),
                'index'  => 'qty',
                'width'  => '100px',
                'type'   => 'number',
            )
        );
        $this->addColumn(
            'visibility',
            array(
                'header'  => __('Visibility'),
                'width'   => '70px',
                'index'   => 'visibility',
                'type'    => 'options',
                'options' => $this->_productVisibility->getOptionArray(),
            )
        );
        $this->addColumn(
            'status',
            array(
                'header'  => __('Status'),
                'width'   => '70px',
                'index'   => 'status',
                'type'    => 'options',
                'options' => $this->_status->getOptionArray()
            )
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
            array(
                'header'   => __('Include in export ?'),
                'index'    => 'lengow_product',
                'width'    => '70px',
//                'type'     => 'text',
                'type'     => 'options',
//                'renderer' => 'Lengow_Connector_Block_Adminhtml_Product_Renderer_Lengow',
                'options'  => array(
                    0 => __('No'),
                    1 => __('Yes')
                ),
            )
        );

        return parent::_prepareColumns();
    }

}
