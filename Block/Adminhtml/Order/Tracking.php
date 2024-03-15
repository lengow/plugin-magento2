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

namespace Lengow\Connector\Block\Adminhtml\Order;

use Magento\Shipping\Block\Adminhtml\Order\Tracking as OrderTracking;
use Magento\Backend\Block\Template\Context as TemplateContext;
use Magento\Shipping\Model\Config as ShippingConfig;
use Magento\Framework\Registry;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;
use Lengow\Connector\Model\Import\Order as LengowOrder;

class Tracking extends OrderTracking
{

    /**
     *
     * @var LengowOrderFactory $lengowOrderFactory
     */
    protected LengowOrderFactory $lengowOrderFactory;

    /**
     *
     * @var LengowOrder $lengowOrder
     */
    protected $lengowOrder;

    /**
     * Tracking constructor
     *
     * @param TemplateContext       $context
     * @param ShippingConfig        $shippingConfig
     * @param Registry              $registry
     * @param LengowOrderFactory    $lengowOrderFactory
     * @param array                 $data
     */
    public function __construct(
        TemplateContext $context,
        ShippingConfig $shippingConfig,
        Registry $registry,
        LengowOrderFactory$lengowOrderFactory,
        array $data = []
    )
    {
        $this->lengowOrderFactory = $lengowOrderFactory;
        parent::__construct($context, $shippingConfig, $registry, $data);
    }

    /**
     *
     * @return bool
     */
    public function canDisplayReturnNumber(): bool
    {
        try {

            return $this->getLengowOrder()
                ->getMarketPlace()
                ->hasReturnTrackingNumber();

        } catch (\Exception $e) {

            return false;
        }
    }

    /**
     *
     * @return bool
     */
    public function canDisplayReturnCarrier(): bool
    {

        try {

            return $this->getLengowOrder()
                ->getMarketPlace()
                ->hasReturnTrackingCarrier();

        } catch (\Exception $e) {
           
            return false;
        }
    }


    /**
     *
     * @return LengowOrder
     */
    protected function getLengowOrder(): LengowOrder
    {
        if ($this->lengowOrder instanceof LengowOrder) {
            return $this->lengowOrder;
        }
        $this->lengowOrder = $this->lengowOrderFactory->create();
        $lengowOrderId = (int) $this->lengowOrder->getLengowOrderIdByOrderId(
            (int) $this->_request->getParam('order_id')
        );

        return $this->lengowOrder->load($lengowOrderId);
    }

}


