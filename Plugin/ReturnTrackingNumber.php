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
 * @subpackage  Plugin
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Api\OrderRepositoryInterface;
use Lengow\Connector\Helper\Config as LengowConfig;

class ReturnTrackingNumber
{
    /**
     *
     * @var RequestInterface $request
     */
    protected RequestInterface $request;

    /**
     *
     * @var LengowConfig $lengowConfig
     */
    protected LengowConfig $lengowConfig;

    /**
     *
     * @var OrderRepositoryInterface $orderRepo
     */
    protected OrderRepositoryInterface $orderRepo;

    /**
     * ReturnTrackingNumber constructor
     *
     * @param RequestInterface          $request        Magento Request Interface
     * @param LengowConfig              $lengowConfig   Lengow Config Helper
     * @param OrderRepositoryInterface  $orderRepo      Magento Api order repository
     */
    public function __construct(
        RequestInterface $request,
        LengowConfig $lengowConfig,
        OrderRepositoryInterface  $orderRepo
    ) {

        $this->request = $request;
        $this->lengowConfig = $lengowConfig;
        $this->orderRepo = $orderRepo;
    }

    /**
     * will add the return tracking number
     *
     * @param Shipment  $subject    the Order shipment before save
     * @param Track     $track      The Order shipment track before add
     *
     * @return array
     */
    public function beforeAddTrack(Shipment $subject, Track $track): array
    {
        $orderId = (int) $this->request->getParam('order_id');
        $isEnabled = false;

        try {
            $order = $this->orderRepo->get($orderId);
            $isEnabled = (bool) $this->lengowConfig->get(
                LengowConfig::RETURN_TRACKING_NUMBER_ENABLED,
                $order->getStoreId()
            );
        } catch (\Exception $e) {
            return [$track];
        }
        
        if (!$isEnabled) {
            return [$track];
        }

        $trackingsPosted = $this->request->getPost('tracking') ?? [];
        $lastTraskPosted = end($trackingsPosted);
        $returnNumber = $lastTraskPosted['return_number'] ?? '';

        if ($returnNumber) {
            $track->setReturnTrackNumber($returnNumber);
        }

        return [$track];
    }

}

