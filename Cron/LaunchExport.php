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
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Export as LengowExport;
use Lengow\Connector\Model\ExportFactory as LengowExportFactory;

class LaunchExport
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
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var LengowExportFactory Lengow export instance
     */
    private $exportFactory;

    /**
     * Constructor
     *
     * @param StoreManagerInterface $storeManager Magento store manager instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param LengowExportFactory $exportFactory Lengow export factory instance
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        LengowExportFactory $exportFactory
    ) {
        $this->storeManager = $storeManager;
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->exportFactory = $exportFactory;
    }

    /**
     * Launch export products for each store
     */
    public function execute()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        $storeCollection = $this->storeManager->getStores();
        foreach ($storeCollection as $store) {
            if ($store->isActive()) {
                $storeId = (int) $store->getId();
                if ($this->configHelper->get(ConfigHelper::EXPORT_MAGENTO_CRON_ENABLED, $storeId)) {
                    try {
                        // config store
                        $this->storeManager->setCurrentStore($storeId);
                        // launch export process
                        $export = $this->exportFactory->create();
                        $export->init(
                            [
                                LengowExport::PARAM_STORE_ID => $storeId,
                                LengowExport::PARAM_STREAM => false,
                                LengowExport::PARAM_UPDATE_EXPORT_DATE => false,
                                LengowExport::PARAM_LOG_OUTPUT => false,
                                LengowExport::PARAM_TYPE => LengowExport::TYPE_MAGENTO_CRON,
                            ]
                        );
                        $export->exec();
                        unset($export);
                    } catch (Exception $e) {
                        $errorMessage = '[Magento error]: "' . $e->getMessage()
                            . '" ' . $e->getFile() . ' | ' . $e->getLine();
                        $this->dataHelper->log(DataHelper::CODE_EXPORT, $errorMessage);
                    }
                }
            }
        }
    }
}
