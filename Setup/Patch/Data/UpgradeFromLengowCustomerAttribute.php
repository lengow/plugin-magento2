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

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Setup\EavSetupFactory;

class UpgradeFromLengowCustomerAttribute implements DataPatchInterface
{

    /**
     *
     * @var ModuleDataSetupInterface $setup
     */
    private $setup;

    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface   $setup                 Magento module setup
     * @param CustomerSetupFactory       $customerSetupFactory  Customer setup factory
     * @param EavSetupFactory            $eavSetupFactory       EAV setup factory
     *
     */
    public function __construct(
        ModuleDataSetupInterface $setup,
        CustomerSetupFactory $customerSetupFactory,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->setup = $setup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->setup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->setup]);
        $customerSetup = $this->customerSetupFactory->create(
            [
                'resourceName' => 'customer_setup',
                'setup' => $this->setup
            ]
        );
        $entityTypeId = $customerSetup->getEntityTypeId(Customer::ENTITY);
        $fromLengowCustomer = $eavSetup->getAttribute($entityTypeId, 'from_lengow');
        if ($fromLengowCustomer) {
            $input = $fromLengowCustomer['frontend_input'] ?? 'select';
            if ($input === 'select') {
                $eavSetup->updateAttribute(
                    Customer::ENTITY,
                    'from_lengow',
                    [
                        'input' => 'boolean',
                        'frontend_input' => 'boolean'
                    ]
                );
            }

        }
        $this->setup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [
            InstallLengowData::class,
            UpgradeLengowDataConfig::class,
            UpgradeLengowDataTracking::class,
            DisableLengowDataTracking::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}
