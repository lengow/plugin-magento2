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
 * @subpackage  Test
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Test\Unit\Helper;

use Lengow\Connector\Helper\Config;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\Collection as GroupCollection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Lengow\Connector\Helper\Config
     */
    protected $_configHelper;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\Collection
     */
    protected $_customerGroupCollectionMock;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection
     */
    protected $_attributeCollectionMock;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $customerGroupCollectionFactoryMock = $this->getMockBuilder(GroupCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->_customerGroupCollectionMock = $objectManager->getCollectionMock(GroupCollection::class, []);
        $customerGroupCollectionFactoryMock->method('create')->willReturn($this->_customerGroupCollectionMock);
        $attributeCollectionFactoryMock = $this->getMockBuilder(AttributeCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->_attributeCollectionMock = $objectManager->getCollectionMock(AttributeCollection::class, []);
        $attributeCollectionFactoryMock->method('create')->willReturn($this->_attributeCollectionMock);
        $this->_configHelper = $objectManager->getObject(
            Config::class,
            [
                '_customerGroupCollectionFactory' => $customerGroupCollectionFactoryMock,
                '_attributeCollectionFactory'     => $attributeCollectionFactoryMock,
            ]
        );
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Config::class,
            $this->_configHelper,
            '[Test Class Instantiation] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Helper\Config::getAllCustomerGroup
     */
    public function testGetAllCustomerGroup()
    {
        $mockCustomerGroups = [
            ['value' => '0', 'label' => 'NOT LOGGED IN'],
            ['value' => '1', 'label' => 'General'],
            ['value' => '2', 'label' => 'Whosale'],
            ['value' => '3', 'label' => 'Retailer'],
        ];
        $this->_customerGroupCollectionMock->expects($this->once())
            ->method('toOptionArray')
            ->willReturn($mockCustomerGroups);
        $customerGroups = $this->_configHelper->getAllCustomerGroup();
        $this->assertInternalType(
            'array',
            $customerGroups,
            '[Test Get All Customer Group] Check if return is a array'
        );
        $this->assertEquals(
            $customerGroups,
            $mockCustomerGroups,
            '[Test Get All Customer Group] Check if return is valid'
        );
    }

    /**
     * @covers \Lengow\Connector\Helper\Config::getAllAttributes
     */
    public function testGetAllAttributes()
    {
        $mockAttributes = [
            ['attribute_code' => 'activity'],
            ['attribute_code' => 'category_gear'],
            ['attribute_code' => 'category_ids'],
            ['attribute_code' => 'climate']
        ];
        $results = [
            ['value' => 'none', 'label' => ''],
            ['value' => 'activity', 'label' => 'activity'],
            ['value' => 'category_gear', 'label' => 'category_gear'],
            ['value' => 'category_ids', 'label' => 'category_ids'],
            ['value' => 'climate', 'label' => 'climate'],
        ];
        $this->_attributeCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->will($this->returnValue($this->_attributeCollectionMock));
        $this->_attributeCollectionMock->expects($this->once())
            ->method('load')
            ->will($this->returnValue($this->_attributeCollectionMock));
        $this->_attributeCollectionMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($mockAttributes));
        $attributes = $this->_configHelper->getAllAttributes();
        $this->assertInternalType(
            'array',
            $attributes,
            '[Test Get All Attributes] Check if return is a array'
        );
        $this->assertEquals(
            $attributes,
            $results,
            '[Test Get All Attributes] Check if return is valid'
        );
    }
}

