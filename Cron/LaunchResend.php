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
 * @subpackage  Cron
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Cron;

use Exception;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Import\Action as LengowAction;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\Ordererror;
use Lengow\Connector\Model\Import\OrdererrorFactory as LengowOrderErrorFactory;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\OrderFactory as MagentoOrderFactory;
use Magento\Store\Model\StoreManagerInterface;

class LaunchResend
{
    /**
     * @cont RESEND_MAX_TRIES
     */
    private const RESEND_MAX_TRIES = 3;
    /**
     * @var StoreManagerInterface Magento store manager instance
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private ConfigHelper $configHelper;

    /**
    * @var LengowOrderErrorFactory Lengow order error factory instance
    */
    private LengowOrderErrorFactory $orderErrorFactory;

    /**
     *
     * @var LengowOrderFactory Lengow order factory instance
     */
    private LengowOrderFactory $lengowOrderFactory;

    /**
     *
     * @var DataHelper $dataHelper lengow data helper
     */
    private DataHelper $dataHelper;

    /**
     *
     * @var MagentoOrderFactory OrderFactory
     */
    private MagentoOrderFactory $orderFactory;

    /**
     *
     * @var LengowAction $lengowAction
     */
    private LengowAction $lengowAction;

    /**
     * Constructor
     *
     * @param StoreManagerInterface         $storeManager       Magento store manager instance
     * @param DataHelper                    $dataHelper         Lengow data helper instance
     * @param ConfigHelper                  $configHelper       Lengow config helper instance
     * @param LengowExportFactory           $exportFactory      Lengow export factory instance
     * @param LengowOrderErrorFactory       $orderErrorFactory  Lengow order error factory
     * @param LengowOrderFactory            $lengowOrderFactory Lengow Order factory
     * @param MagentoOrderFactory           $orderFactory       Magento Order factory
     * @param LengowAction                  $lengowAction       The Lengow Action
     */
    public function __construct(
        StoreManagerInterface           $storeManager,
        DataHelper                      $dataHelper,
        ConfigHelper                    $configHelper,
        LengowOrderErrorFactory         $orderErrorFactory,
        LengowOrderFactory              $lengowOrderFactory,
        MagentoOrderFactory             $orderFactory,
        LengowAction                    $lengowAction,
    ) {
        $this->storeManager         = $storeManager;
        $this->configHelper         = $configHelper;
        $this->dataHelper           = $dataHelper;
        $this->orderErrorFactory    = $orderErrorFactory;
        $this->lengowOrderFactory   = $lengowOrderFactory;
        $this->orderFactory         = $orderFactory;
        $this->lengowAction         = $lengowAction;

    }

    /**
     * Launch export products for each store
     */
    public function execute(): void
    {

        set_time_limit(0);
        ini_set('memory_limit', '1G');
        $storeCollection = $this->storeManager->getStores();
        foreach ($storeCollection as $store) {
            if (!$store->isActive()) {
                echo "store not activce \n";
                continue;
            }
            $resent = [];
            $storeId = (int) $store->getId();
            if (!$this->configHelper->get(ConfigHelper::RESEND_MAGENTO_CRON_ENABLED, $storeId)) {
                echo "cron not enabled \n";
                continue;
            }
            try {
                // launch resend process
                $orderErrorModel = $this->orderErrorFactory->create();
                $ordersToResend =  $orderErrorModel->getOrdersToResend($storeId);
                $orderLengowModel = $this->lengowOrderFactory->create();

                if (empty($ordersToResend)) {
                    continue;
                }
                foreach ($ordersToResend as $orderResendData) {

                    if($this->isAlreadySent($orderResendData, $resent)) {
                        continue;
                    }
                    if (!$this->couldResend($orderErrorModel, $orderResendData)) {
                        $this->dataHelper->log(
                            DataHelper::CODE_ACTION,
                            'Order action could not be resend : ' . $orderResendData[LengowOrder::FIELD_MARKETPLACE_SKU]
                        );
                        continue;
                    }
                    $this->dataHelper->log(
                        DataHelper::CODE_ACTION,
                        'trying to resend : ' . $orderResendData[LengowOrder::FIELD_MARKETPLACE_SKU]
                    );

                    $orderLengowModel->reSendOrder($orderResendData[Ordererror::FIELD_ORDER_LENGOW_ID]);
                    $resent[] = $orderResendData[Ordererror::FIELD_ORDER_LENGOW_ID];
                    $orderErrorModel->finishOrderErrors(
                        $orderResendData[Ordererror::FIELD_ORDER_LENGOW_ID],
                        Ordererror::TYPE_ERROR_SEND
                    );
                    $this->dataHelper->log(
                        DataHelper::CODE_ACTION,
                        'order action resent : ' . $orderResendData[LengowOrder::FIELD_MARKETPLACE_SKU]
                    );
                    usleep(250000);
                }

            } catch (Exception $e) {
                $errorMessage = '[Magento error]: "' . $e->getMessage()
                    . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
                $this->dataHelper->log(DataHelper::CODE_ACTION, $errorMessage);
            }
        }
    }

    /**
     * Check if the action could be resend
     *
     * @param Ordererror    $orderErrorModel
     * @param array         $orderResendData
     *
     * @return bool
     */
    private function couldResend(Ordererror $orderErrorModel, array $orderResendData): bool
    {

        $tries  = $orderErrorModel->getCountOrderSendErrors(
            (int) $orderResendData[Ordererror::FIELD_ORDER_LENGOW_ID]
        );

        if ($tries >= self::RESEND_MAX_TRIES) {
            $orderErrorModel->finishOrderErrors(
                $orderResendData[Ordererror::FIELD_ORDER_LENGOW_ID],
                Ordererror::TYPE_ERROR_SEND
            );
            return false;
        }

        $order  = $this->orderFactory->create()
                       ->load($orderResendData[LengowOrder::FIELD_ORDER_ID]);
        $action = $this->lengowAction
            ->getLastOrderActionType($orderResendData[LengowOrder::FIELD_ORDER_ID]);
        if (!$action) {
            $action = $order->getData('status') === LengowOrder::STATE_CANCELED
                ? LengowAction::TYPE_CANCEL
                : LengowAction::TYPE_SHIP;
        }

        if ($order->getData('status') === LengowOrder::STATE_CANCELED
                && $action === LengowAction::TYPE_CANCEL) {
            return true;
        }

        if ($action === LengowAction::TYPE_SHIP) {
            /** @var Shipment|void $shipment */
            $shipment = $order->getShipmentsCollection()->getFirstItem();
            if (is_null($shipment)) {
                return false;
            }
            $tracks = $shipment ? $shipment->getAllTracks() : [];
            if (empty($tracks)) {
                return false;
            }
            /** @var \Magento\Shipping\Model\Order\Track $lastTrack */
            $lastTrack =  end($tracks);
            $trackingNumber = $lastTrack->getNumber() ?? '';
            if (empty($trackingNumber)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the action is already sent during execute
     *
     * @param type $orderResendData
     * @param type $resent
     *
     * @return bool
     */
    private function isAlreadySent(array $orderResendData, array $resent): bool
    {
        return in_array(
            $orderResendData[Ordererror::FIELD_ORDER_LENGOW_ID],
            $resent
        );
    }
}
