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

namespace Lengow\Connector\Block\Adminhtml\Order\Tracking;

use Magento\Shipping\Block\Adminhtml\Order\Tracking\View as OrderTrackingView;
use Lengow\Connector\Helper\Config as LengowConfig;
use Magento\Backend\Block\Template\Context as TemplateContext;
use Magento\Shipping\Model\Config as ShippingConfig;
use Magento\Framework\Registry;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Shipping\Helper\Data as ShippingHelper;

class View extends OrderTrackingView
{
    /**
     *
     * @var LengowConfig $lengowConfig
     */
    protected LengowConfig $lengowConfig;

    /**
     * View constructor
     *
     * @param TemplateContext $context
     * @param ShippingConfig $shippingConfig
     * @param Registry $registry
     * @param CarrierFactory $carrierFactory
     * @param LengowConfig $lengowConfig
     * @param array $data
     * @param ShippingHelper|null $shippingHelper
     */
    public function __construct(
        TemplateContext $context,
        ShippingConfig $shippingConfig,
        Registry $registry,
        CarrierFactory $carrierFactory,
        LengowConfig $lengowConfig,
        array $data = [],
        ?ShippingHelper $shippingHelper = null
    ) {
        $this->lengowConfig = $lengowConfig;

        parent::__construct(
            $context,
            $shippingConfig,
            $registry,
            $carrierFactory,
            $data,
            $shippingHelper
        );
    }

    /**
     *
     * @return bool
     */
    public function canDisplayReturnNumber(): bool
    {
       return (bool) $this->lengowConfig->get(
            LengowConfig::RETURN_TRACKING_NUMBER_ENABLED,
            $this->getShipment()->getStoreId()
        ) ;

    }
}
