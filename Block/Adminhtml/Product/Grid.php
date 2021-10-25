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

use Exception;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid as MagentoGrid;
use Magento\Backend\Block\Widget\Grid\Extended as MagentoGridExtended;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\Product\AttributeSet\Options as AttributeSetOptions;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\FileSystemException;
use Magento\Store\Model\WebsiteFactory;
use Lengow\Connector\Block\Adminhtml\Product\Grid\Renderer\Image;
use Lengow\Connector\Block\Adminhtml\Product\Grid\Renderer\Lengow;
use Lengow\Connector\Block\Widget\Grid\Extended;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Config\Source\Type as SourceType;

class Grid extends Extended
{
    /**
     * @var ProductCollectionFactory Magento product collection factory instance
     */
    private $collectionFactory;

    /**
     * @var AttributeSetOptions Magento attribute set options instance
     */
    private $attributeSetOptions;

    /**
     * @var ProductVisibility Magento product visibility instance
     */
    private $productVisibility;

    /**
     * @var WebsiteFactory Magento website factory instance
     */
    private $websiteFactory;

    /**
     * @var ProductStatus Magento product attribute status instance
     */
    private $status;

    /**
     * @var SourceType Lengow config source type instance
     */
    private $sourceType;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * Constructor
     *
     * @param Context $context Magento block context instance
     * @param BackendHelper $backendHelper Magento backend helper instance
     * @param ProductCollectionFactory $collectionFactory
     * @param AttributeSetOptions $attributeSetOptions Magento attribute option instance
     * @param WebsiteFactory $websiteFactory Magento website factory instance
     * @param ProductStatus $status Magento attribute status instance
     * @param ProductVisibility $productVisibility Magento product visibility instance
     * @param SourceType $sourceType Magento source type instance
     * @param DataHelper $dataHelper Lengow data helper instance
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
        $this->collectionFactory = $collectionFactory;
        $this->sourceType = $sourceType;
        $this->attributeSetOptions = $attributeSetOptions;
        $this->productVisibility = $productVisibility;
        $this->websiteFactory = $websiteFactory;
        $this->status = $status;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Inherited construct method
     *
     * @return void
     *
     * @throws FileSystemException
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
     * @return MagentoGrid
     *
     * @throws Exception
     */
    protected function _prepareCollection(): MagentoGrid
    {
        $collection = $this->collectionFactory->create();
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
            ->addStoreFilter($this->dataHelper->getStore())
            ->addAttributeToFilter('type_id', ['nlike' => 'bundle']);
        $collection->joinAttribute(
            'lengow_product',
            'catalog_product/lengow_product',
            'entity_id',
            null,
            'left',
            $this->dataHelper->getStore()->getId()
        );
        $collection->joinAttribute(
            'price',
            'catalog_product/price',
            'entity_id',
            null,
            'left',
            $this->dataHelper->getStore()->getId()
        );
        $collection->joinAttribute(
            'status',
            'catalog_product/status',
            'entity_id',
            null,
            'inner',
            $this->dataHelper->getStore()->getId()
        );
        $collection->joinAttribute(
            'visibility',
            'catalog_product/visibility',
            'entity_id',
            null,
            'inner',
            $this->dataHelper->getStore()->getId()
        );
        $this->setCollection($collection);

        $this->getCollection()->addWebsiteNamesToResult();
        parent::_prepareCollection();
        return $this;
    }

    /**
     * Prepare columns
     *
     * @return MagentoGridExtended
     *
     * @throws Exception
     */
    protected function _prepareColumns(): MagentoGridExtended
    {
        // create type filter without bundle type product
        $types = $this->sourceType->toOptionArray();
        $type = [];
        foreach ($types as $value) {
            $type[$value['value']] = $value['label'];
        }
        $store = $this->dataHelper->getStore();
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
                'renderer' => Image::class,
                'column_css_class' => 'data-grid-thumbnail-cell',
                'filter' => false,
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
        $sets = $this->attributeSetOptions->toOptionArray();
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
                'options' => $this->productVisibility::getOptionArray(),
            ]
        );
        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'column_css_class' => 'a-center',
                'index' => 'status',
                'type' => 'options',
                'options' => $this->status::getOptionArray(),
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
                    'options' => $this->websiteFactory->create()->getCollection()->toOptionHash(),
                    'filter' => false,
                ]
            );
        }
        $this->addColumn(
            'lengow_product',
            [
                'header' => __('Include in export?'),
                'index' => 'lengow_product',
                'type' => 'options',
                'renderer' => Lengow::class,
                'column_css_class' => 'a-center',
                'options' => [
                    1 => __('Yes'),
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
    protected function _prepareMassaction(): self
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('product');

        $this->getMassactionBlock()->addItem(
            'publish',
            [
                'label' => __('Publish in Lengow'),
                'url' => $this->getUrl('*/*/massPublish', ['_current' => true, 'publish' => true]),
                'complete' => 'reloadGrid',
            ]
        );
        $this->getMassactionBlock()->addItem(
            'unpublish',
            [
                'label' => __('Unpublish in Lengow'),
                'url' => $this->getUrl('*/*/massPublish', ['_current' => true, 'publish' => false]),
                'complete' => 'reloadGrid',
            ]
        );
        return $this;
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl(): string
    {
        return $this->getUrl('lengow/product/index', ['_current' => true]);
    }

    /**
     * Inline editing action
     *
     * @param DataObject $item Magento data object instance
     *
     * @return string
     */
    public function getRowUrl($item): string
    {
        return $this->getUrl(
            'catalog/*/edit',
            ['store' => $this->getRequest()->getParam('store'), 'id' => $item->getId()]
        );
    }
}
