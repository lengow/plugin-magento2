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
use Lengow\Connector\Test\Unit\Fixture;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\Collection as GroupCollection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigDataCollectionFactory;
use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigDataCollection;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Magento\Store\Model\ResourceModel\Store\Collection as StoreCollection;

class ConfigTest extends \PHPUnit\Framework\TestCase
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
     * @var \Magento\Config\Model\ResourceModel\Config\Data\Collection
     */
    protected $_configDataCollectionMock;

    /**
     * @var \Magento\Store\Model\ResourceModel\Store\Collection
     */
    protected $_storeCollectionMock;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp() : void
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

        $configDataCollectionFactoryMock = $this->getMockBuilder(ConfigDataCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->_configDataCollectionMock = $objectManager->getCollectionMock(ConfigDataCollection::class, []);
        $configDataCollectionFactoryMock->method('create')->willReturn($this->_configDataCollectionMock);

        $storeCollectionFactoryMock = $this->getMockBuilder(StoreCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->_storeCollectionMock = $objectManager->getCollectionMock(StoreCollection::class, []);
        $storeCollectionFactoryMock->method('create')->willReturn($this->_storeCollectionMock);
        $this->_configHelper = $objectManager->getObject(
            Config::class,
            [
                'customerGroupCollectionFactory' => $customerGroupCollectionFactoryMock,
                'attributeCollectionFactory' => $attributeCollectionFactoryMock,
                'configDataCollectionFactory' => $configDataCollectionFactoryMock,
                'storeCollectionFactory' => $storeCollectionFactoryMock,
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
     * @covers \Lengow\Connector\Helper\Config::get
     */
    public function testGet()
    {
        $this->assertEquals(
            $this->_configHelper->get('ip_enable'),
            null,
            '[Test Get] Check if return is valid for Lengow setting with cache'
        );


        $this->_configDataCollectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->will($this->returnValue($this->_configDataCollectionMock));

        $this->_configDataCollectionMock->expects($this->once())
            ->method('load')
            ->will($this->returnValue($this->_configDataCollectionMock));

        $this->_configDataCollectionMock->expects($this->once())
            ->method('getData')
            ->will($this->returnValue([['value' => 'mytoken']]));


        $this->assertEquals(
            $this->_configHelper->get('token'),
            'mytoken',
            '[Test Get] Check if return is valid for Lengow setting with cache'
        );


        $this->assertEquals(
            $this->_configHelper->get('toto'),
            null,
            '[Test Get] Check if return is valid for fake Lengow setting with cache'
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
        $this->assertIsArray(
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
     * @covers \Lengow\Connector\Helper\Config::isNewMerchant
     */
    public function testIsNewMerchant()
    {
        $fixture = new Fixture();
        $configHelperMock = $fixture->mockFunctions($this->_configHelper, ['getAccessIds'], [[null, null, null]]);
        $this->assertIsBool(
            $configHelperMock->isNewMerchant(),
            '[Test Is New Merchant] Check if return is a boolean'
        );
        $this->assertTrue(
            $configHelperMock->isNewMerchant(),
            '[Test Is New Merchant] Check if return is valid when access ids are empty'
        );
        $configHelperMock2 = $fixture->mockFunctions(
            $this->_configHelper,
            ['getAccessIds'],
            [['123', 'blablabla', 'blablabla']]
        );
        $this->assertFalse(
            $configHelperMock2->isNewMerchant(),
            '[Test Is New Merchant] Check if return is valid when access ids are presents'
        );
    }

    /**
     * @covers \Lengow\Connector\Helper\Config::getSelectedAttributes
     */
    public function testGetSelectedAttributes()
    {
        $fixture = new Fixture();
        $selectedAttributesMock = 'meta_description,meta_keyword,meta_title,minimal_price,size';
        $configHelperMock = $fixture->mockFunctions($this->_configHelper, ['get'], [$selectedAttributesMock]);
        $this->assertIsArray(
            $configHelperMock->getSelectedAttributes(1),
            '[Test Get Selected Attributes] Check if return is a array'
        );
        $this->assertEquals(
            ['meta_description', 'meta_keyword', 'meta_title', 'minimal_price', 'size'],
            $configHelperMock->getSelectedAttributes(1),
            '[Test Get Selected Attributes] Check if return is valid'
        );
        $configHelperMock2 = $fixture->mockFunctions($this->_configHelper, ['get'], [null]);
        $this->assertEquals(
            [],
            $configHelperMock2->getSelectedAttributes(1),
            '[Test Get Selected Attributes] Check if return is valid when is null'
        );
    }
}
