<?php

/**
 * Copyright 2019 Lengow SAS
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
 * @subpackage  Setup
 * @author      Team module <team-module@lengow.com>
 * @copyright   2019 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Setup\Patch\Data;

use Lengow\Connector\Helper\Config as ConfigHelper;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class UpgradeLengowDataTracking
 */
class UpgradeLengowDataTracking implements DataPatchInterface
{
    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     *
     * @var ModuleDataSetupInterface $setup
     */
    private $setup;


    /**
     * Constructor
     *
     * @param ConfigHelper              $configHelper Lengow config helper instance
     * @param ModuleDataSetupInterface  $setup        Magento module setup
     *
     */
    public function __construct(
        ConfigHelper $configHelper,
        ModuleDataSetupInterface $setup
    ) {
        $this->configHelper = $configHelper;
        $this->setup = $setup;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->setup->getConnection()->startSetup();
        $trackingEnable = (bool) $this->configHelper->get(ConfigHelper::TRACKING_ENABLED);
        if (!$trackingEnable && !$this->configHelper->isNewMerchant()) {
            $this->configHelper->set(ConfigHelper::TRACKING_ENABLED, 1);
            // clean config cache to valid configuration
            $this->configHelper->cleanConfigCache();
        }

        $this->setup->getConnection()->endSetup();
    }



    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [InstallLengowData::class,UpgradeLengowDataConfig::class];
    }



    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }

}
