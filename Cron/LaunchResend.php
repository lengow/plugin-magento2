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
use Magento\Store\Model\StoreManagerInterface;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Import\OrdererrorFactory as LengowOrderErrorFactory;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;
use Lengow\Connector\Model\Import\Ordererror;
use Lengow\Connector\Model\Import\Order as LengowOrder;

class LaunchResend
{
    /**
     * @var StoreManagerInterface Magento store manager instance
     */
    private $storeManager;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
    * @var LengowOrderErrorFactory Lengow order error factory instance
    */
    private $orderErrorFactory;

    /**
     *
     * @var LengowOrderFactory Lengow order factory instance
     */
    private $lengowOrderFactory;

    /**
     *
     * @var DataHelper $dataHelper lengow data helper
     */
    private $dataHelper;

    /**
     * Constructor
     *
     * @param StoreManagerInterface     $storeManager       Magento store manager instance
     * @param DataHelper                $dataHelper         Lengow data helper instance
     * @param ConfigHelper              $configHelper       Lengow config helper instance
     * @param LengowOrderErrorFactory   $orderErrorFactory  Lengow orderError factory instance
     * @param LengowOrderFactory        $lengowOrderFactory Lengow order factory instance
     */
    public function __construct(
        StoreManagerInterface   $storeManager,
        DataHelper              $dataHelper,
        ConfigHelper            $configHelper,
        LengowOrderErrorFactory $orderErrorFactory,
        LengowOrderFactory      $lengowOrderFactory
    ) {
        $this->storeManager         = $storeManager;
        $this->configHelper         = $configHelper;
        $this->dataHelper           = $dataHelper;
        $this->orderErrorFactory    = $orderErrorFactory;
        $this->lengowOrderFactory   = $lengowOrderFactory;
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
                continue;
            }
            $resent = [];
            $storeId = (int) $store->getId();
            if (!$this->configHelper->get(ConfigHelper::RESEND_MAGENTO_CRON_ENABLED, $storeId)) {
                continue;
            }
            try {
                // launch resend process
                $ordersToResend = $this->orderErrorFactory->create()->getOrdersToResend($storeId);

                $orderLengowModel = $this->lengowOrderFactory->create();
                foreach ($ordersToResend as $orderResendData) {
                    if (in_array($orderResendData[Ordererror::FIELD_ORDER_LENGOW_ID], $resent)) {
                        continue;
                    }

                    $this->dataHelper->log(
                        DataHelper::CODE_ACTION,
                        'trying to resend : '.$orderResendData[LengowOrder::FIELD_MARKETPLACE_SKU]
                    );
                    $orderLengowModel->reSendOrder($orderResendData[Ordererror::FIELD_ORDER_LENGOW_ID]);
                    $resent[] = $orderResendData[Ordererror::FIELD_ORDER_LENGOW_ID];
                    $this->dataHelper->log(
                        DataHelper::CODE_ACTION,
                        'order action resent : '.$orderResendData[LengowOrder::FIELD_MARKETPLACE_SKU]
                    );
                    usleep(50000);
                }

            } catch (Exception $e) {
                $errorMessage = '[Magento error]: "' . $e->getMessage()
                    . '" in ' . $e->getFile() . ' on line ' . $e->getLine();
                $this->dataHelper->log(DataHelper::CODE_ACTION, $errorMessage);
            }
        }
    }
}
