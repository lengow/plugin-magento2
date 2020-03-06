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

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Catalog\Helper\Image as ImageHelper;

class Image extends AbstractRenderer
{
    /**
     * @var ImageHelper Magento image helper instance
     */
    protected $imageHelper;

    /**
     * Constructor
     *
     * @param Context $context Magento block context instance
     * @param ImageHelper $imageHelper Magento image helper instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        ImageHelper $imageHelper,
        array $data = []
    )
    {
        $this->imageHelper = $imageHelper;
        $this->_authorization = $context->getAuthorization();
        parent::__construct($context, $data);
    }

    /**
     * Renders grid column
     *
     * @param DataObject $row Magento data object instance
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
