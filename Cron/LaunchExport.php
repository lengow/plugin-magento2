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

use Magento\Store\Model\StoreManagerInterface;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\ExportFactory;

class LaunchExport
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface Magento store manager instance
     */
    protected $_storeManager;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Model\Export Lengow export instance
     */
    protected $_exportFactory;

    /**
     * Constructor
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Magento store manager instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Model\ExportFactory $exportFactory Lengow export factory instance
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        ExportFactory $exportFactory
    ) {
        $this->_storeManager = $storeManager;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_exportFactory = $exportFactory;
    }

    /**
     * Launch export products for each store
     */
    public function execute()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        $storeCollection = $this->_storeManager->getStores();
        foreach ($storeCollection as $store) {
            if ($store->isActive()) {
                $storeId = (int)$store->getId();
                if ($this->_configHelper->get('export_cron_enable', $storeId)) {
                    try {
                        // config store
                        $this->_storeManager->setCurrentStore($storeId);
                        // launch export process
                        $export = $this->_exportFactory->create();
                        $export->init(
                            [
                                'store_id' => $storeId,
                                'stream' => false,
                                'update_export_date' => false,
                                'log_output' => false,
                                'type' => 'magento cron'
                            ]
                        );
                        $export->exec();
                        unset($export);
                    } catch (\Exception $e) {
                        $errorMessage = 'Magento error: "' . $e->getMessage()
                            . '" ' . $e->getFile() . ' line ' . $e->getLine();
                        $this->_dataHelper->log('Export', $errorMessage);
                    }
                }
            }
        }
    }
}
