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

namespace Lengow\Connector\Block\Adminhtml\Product\Grid\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Backend\Block\Context;
use Magento\Catalog\Helper\Image as HelperImage;

class Image extends AbstractRenderer
{
    /**
     * Image Helper
     *
     * @var \Magento\Catalog\Helper\Image Magento image helper instance
     */
    protected $imageHelper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Context $context Magento block context instance
     * @param \Magento\Catalog\Helper\Image $imageHelper Magento image helper instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        HelperImage $imageHelper,
        array $data = []
    ) {
        $this->imageHelper = $imageHelper;
        parent::__construct($context, $data);
        $this->_authorization = $context->getAuthorization();
    }

    /**
     * Renders grid column
     *
     * @param \Magento\Framework\DataObject $row Magento data object instance
     *
     * @return  string
     */
    public function render(DataObject $row)
    {
        $image = 'product_listing_thumbnail';
        $imageUrl = $this->imageHelper->init($row, $image)->getUrl();

        return '<img src="' . $imageUrl . '" width="50"/>';
    }
}
