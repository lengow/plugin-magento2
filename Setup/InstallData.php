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
 * @subpackage  Setup
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Setup;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean as EavBool;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Setup\SalesSetupFactory;
use Lengow\Connector\Helper\Config as ConfigHelper;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var EavSetupFactory Magento EAV setup factory instance
     */
    private $eavSetupFactory;

    /**
     * @var CustomerSetupFactory Magento customer setup factory instance
     */
    private $customerSetupFactory;

    /**
     * @var SalesSetupFactory Magento sales setup factory instance
     */
    private $salesSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @var ObjectManagerInterface Magento object manager instance
     */
    private $objectManager;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory Magento EAV setup factory instance
     * @param CustomerSetupFactory $customerSetupFactory Magento customer setup factory instance
     * @param SalesSetupFactory $salesSetupFactory Magento sales setup factory instance
     * @param AttributeSetFactory $attributeSetFactory Magento attribute set factory instance
     * @param ObjectManagerInterface $objectManager Magento object manager instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        CustomerSetupFactory $customerSetupFactory,
        SalesSetupFactory $salesSetupFactory,
        AttributeSetFactory $attributeSetFactory,
        ObjectManagerInterface $objectManager,
        ConfigHelper $configHelper
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->salesSetupFactory = $salesSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->objectManager = $objectManager;
        $this->configHelper = $configHelper;
    }

    /**
     * Installs data for a module
     *
     * @param ModuleDataSetupInterface $setup Magento module data setup instance
     * @param ModuleContextInterface $context Magento module context instance
     *
     * @throws Exception
     *
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $customerSetup = $this->customerSetupFactory->create(['resourceName' => 'customer_setup', 'setup' => $setup]);
        $salesSetup = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);

        // create attribute lengow_product for product
        $entityTypeId = $customerSetup->getEntityTypeId(Product::ENTITY);
        $lengowProductAttribut = $eavSetup->getAttribute($entityTypeId, 'lengow_product');
        if (!$lengowProductAttribut) {
            $eavSetup->addAttribute(
                $entityTypeId,
                'lengow_product',
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Publish on Lengow',
                    'input' => 'boolean',
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => 1,
                    'required' => 0,
                    'user_defined' => 1,
                    'default' => 1,
                    'searchable' => 0,
                    'filterable' => 0,
                    'comparable' => 0,
                    'unique' => 0,
                    'visible_on_front' => 0,
                    'used_in_product_listing' => 1,
                    'system' => 0,
                    'group' => 'Lengow',
                ]
            );
        }

        // create attribute from_lengow for customer
        $entityTypeId = $customerSetup->getEntityTypeId(Customer::ENTITY);
        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();
        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
        $fromLengowCustomer = $eavSetup->getAttribute($entityTypeId, 'from_lengow');
        if (!$fromLengowCustomer) {
            $eavSetup->addAttribute(
                $entityTypeId,
                'from_lengow',
                [
                    'type' => 'int',
                    'label' => 'From Lengow',
                    'visible' => true,
                    'required' => false,
                    'unique' => false,
                    'sort_order' => 700,
                    'default' => 0,
                    'input' => 'select',
                    'system' => 0,
                    'user_defined' => true,
                    'source' => EavBool::class,
                ]
            );
            $fromLengowCustomer = $customerSetup->getEavConfig()
                ->getAttribute('customer', 'from_lengow')
                ->addData(
                    [
                        'attribute_set_id' => $attributeSetId,
                        'attribute_group_id' => $attributeGroupId,
                        'used_in_forms' => ['adminhtml_customer'],
                    ]
                );
            $fromLengowCustomer->getResource()->save($fromLengowCustomer);
        }

        // create attribute from_lengow for order
        $entityTypeId = $salesSetup->getEntityTypeId(Order::ENTITY);
        $salesSetup->addAttribute(
            $entityTypeId,
            'from_lengow',
            [
                'label' => 'From Lengow',
                'type' => 'int',
                'visible' => true,
                'required' => false,
                'unique' => false,
                'filterable' => 1,
                'sort_order' => 700,
                'default' => 0,
                'input' => 'select',
                'system' => 0,
                'source' => EavBool::class,
                'grid' => true,
            ]
        );

        // set default attributes
        $this->configHelper->setDefaultAttributes();

        // check if order state and status 'Lengow technical error' exists
        $collections = $this->objectManager->create(Status::class)
            ->getCollection()
            ->toOptionArray();
        $lengowTechnicalExists = false;
        foreach ($collections as $value) {
            if ($value['value'] === 'lengow_technical_error') {
                $lengowTechnicalExists = true;
            }
        }
        // if not exists create new order state and status 'Lengow technical error'
        $statusTable = $setup->getTable('sales_order_status');
        $statusStateTable = $setup->getTable('sales_order_status_state');
        if (!$lengowTechnicalExists) {
            // insert statuses
            $setup->getConnection()->insertArray(
                $statusTable,
                ['status', 'label'],
                [
                    [
                        'status' => 'lengow_technical_error',
                        'label' => 'Lengow Technical Error',
                    ],
                ]
            );
            // insert states and mapping of statuses to states
            $setup->getConnection()->insertArray(
                $statusStateTable,
                ['status', 'state', 'is_default'],
                [
                    [
                        'status' => 'lengow_technical_error',
                        'state' => 'lengow_technical_error',
                        'is_default' => 1,
                    ],
                ]
            );
        }

        $setup->endSetup();
    }
}
