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
 * @subpackage  Model
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Setup;

use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Lengow\Connector\Helper\Config;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface {
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $_eavSetupFactory;

    /**
     * Customer setup factory
     *
     * @var CustomerSetupFactory
     */
    private $_customerSetupFactory;

    /**
     * Sales setup factory
     *
     * @var SalesSetupFactory
     */
    protected $_salesSetupFactory;

    /**
     * Lengow config helper
     *
     * @var Config
     */
    protected $_configHelper;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param CustomerSetupFactory $customerSetupFactory
     * @param SalesSetupFactory $salesSetupFactory
     * @param Config $configHelper
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        CustomerSetupFactory $customerSetupFactory,
        SalesSetupFactory $salesSetupFactory,
        Config $configHelper
    ) {
        $this->_eavSetupFactory = $eavSetupFactory;
        $this->_customerSetupFactory = $customerSetupFactory;
        $this->_salesSetupFactory = $salesSetupFactory;
        $this->_configHelper =  $configHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function install( ModuleDataSetupInterface $setup, ModuleContextInterface $context ) {

        $setup->startSetup();

        $eavSetup = $this->_eavSetupFactory->create(['setup' => $setup]);
        $customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
        $salesSetup = $this->_salesSetupFactory->create(['setup' => $setup]);

        // create attribute lengow_product for product
        $entityTypeId = $customerSetup->getEntityTypeId(Product::ENTITY);
        $lengowProductAttribut = $eavSetup->getAttribute($entityTypeId, 'lengow_product');

        if (!$lengowProductAttribut) {
            $eavSetup->addAttribute(
                $entityTypeId,
                'lengow_product',
                [
                    'type'                    => 'int',
                    'backend'                 => '',
                    'frontend'                => '',
                    'label'                   => 'Publish on Lengow',
                    'input'                   => 'boolean',
                    'global'                  => ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => 1,
                    'required'                => 0,
                    'user_defined'            => 1,
                    'default'                 => 1,
                    'searchable'              => 0,
                    'filterable'              => 0,
                    'comparable'              => 0,
                    'unique'                  => 0,
                    'visible_on_front'        => 0,
                    'used_in_product_listing' => 1,
                    'system'                  => 0,
                    'group'                   => 'Lengow'
                ]
            );
        }

        // create attribute from_lengow for customer
        $entityTypeId = $customerSetup->getEntityTypeId(Customer::ENTITY);
        $fromLengowCustomer = $eavSetup->getAttribute($entityTypeId, 'from_lengow');
        if (!$fromLengowCustomer) {
            $eavSetup->addAttribute(
                $entityTypeId,
                'from_lengow',
                [
                    'type'       => 'int',
                    'label'      => 'From Lengow',
                    'visible'    => true,
                    'required'   => false,
                    'unique'     => false,
                    'sort_order' => 700,
                    'default'    => 0,
                    'input'      => 'select',
                    'system'     => 0,
                    'source'     => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean'
                ]
            );

            // TODO Save on abstractModel deprecated
            $fromLengowCustomer = $customerSetup->getEavConfig()->getAttribute('customer', 'from_lengow')
                                                ->addData(['used_in_forms' => 'adminhtml_customer']);
            $fromLengowCustomer->save();
        }

        $entityTypeId = $salesSetup->getEntityTypeId(Order::ENTITY);
        // create attribute from_lengow for order
        $salesSetup->addAttribute(
            $entityTypeId,
            'from_lengow',
            [
                'label'      => 'From Lengow',
                'type'       => 'int',
                'visible'    => true,
                'required'   => false,
                'unique'     => false,
                'filterable' => 1,
                'sort_order' => 700,
                'default'    => 0,
                'input'      => 'select',
                'system'     => 0,
                'source'     => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'grid'       => true
            ]
        );

        // Set default attributes
        $this->_configHelper->setDefaultAttributes();

        $setup->endSetup();
    }

}