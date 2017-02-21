<?php
namespace Lengow\Connector\Block\Adminhtml\Product\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class Image extends AbstractRenderer
{
    /**
     * Image Helper
     *
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Catalog\Helper\Image $imageHelper,
        array $data = []
    )
    {
        $this->imageHelper = $imageHelper;
        parent::__construct($context, $data);
        $this->_authorization = $context->getAuthorization();
    }
    /**
    * Renders grid column
    *
    * @param DataObject $row
    * @return  string
    */
    public function render(DataObject $row)
    {
        $image = 'product_listing_thumbnail';
        $imageUrl = $this->imageHelper->init($row, $image)->getUrl();

        return '<img src="'.$imageUrl.'" width="50"/>';

//        $product = new \Magento\Framework\DataObject($item);
//        $imageHelper = $this->imageHelper->init($product, 'product_listing_thumbnail');
//        $item[$fieldName . '_src'] = $imageHelper->getUrl();
//        $item[$fieldName . '_alt'] = $this->getAlt($item) ?: $imageHelper->getLabel();
//        $item[$fieldName . '_link'] = $this->urlBuilder->getUrl(
//            'catalog/product/edit',
//            ['id' => $product->getEntityId(), 'store' => $this->context->getRequestParam('store')]
//        );
//        $origImageHelper = $this->imageHelper->init($product, 'product_listing_thumbnail_preview');
//        $item[$fieldName . '_orig_src'] = $origImageHelper->getUrl();
    }
}