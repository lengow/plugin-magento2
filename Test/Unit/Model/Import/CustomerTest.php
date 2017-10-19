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

namespace Lengow\Connector\Test\Unit\Model\Import;

use Magento\Directory\Model\ResourceModel\Region;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Lengow\Connector\Test\Unit\Fixture;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Import\Customer;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Magento\Customer\Model\Address;

class CustomerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Lengow\Connector\Helper\Config
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Model\Import\Customer
     */
    protected $_customer;

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $_addressFactoryMock;

    /**
     * @var ObjectManager
     */
    protected $_objectManager;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $this->_objectManager = new ObjectManager($this);
        $this->_configHelper = $this->_objectManager->getObject(ConfigHelper::class);
        $this->_customer = $this->_objectManager->getObject(Customer::class);
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Customer::class,
            $this->_customer,
            '[Test Class Instantiation] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Customer::_getNames()
     */
    public function testGetNames()
    {
        $fixture = New Fixture();

        $values = ['firstname' => 'first_name', 'lastname' => 'last_name', 'fullname' => 'full_name'];
        $this->assertEquals(
            $values,
            $fixture->invokeMethod($this->_customer, '_getNames', [$values]),
            '[Test _getNames] set complete address'
        );

        $values = ['firstname' => '', 'lastname' => 'last_name', 'fullname' => 'full_name'];
        $this->assertEquals(
            ['firstname' => 'last_name', 'lastname' => '__'],
            $fixture->invokeMethod($this->_customer, '_getNames', [$values]),
            '[Test _getNames] set empty firstname address'
        );

        $values = ['firstname' => 'first_name', 'lastname' => '', 'fullname' => 'full_name'];
        $this->assertEquals(
            ['firstname' => 'first_name', 'lastname' => '__'],
            $fixture->invokeMethod($this->_customer, '_getNames', [$values]),
            '[Test _getNames] set empty lastname address'
        );

        $values = ['firstname' => 'first_name', 'lastname' => 'last_name', 'fullname' => ''];
        $this->assertEquals(
            $values,
            $fixture->invokeMethod($this->_customer, '_getNames', [$values]),
            '[Test _getNames] set empty fullname address'
        );

        $values = ['firstname' => '', 'lastname' => '', 'fullname' => 'full_name'];
        $this->assertEquals(
            ['firstname' => 'full_name', 'lastname' => '__'],
            $fixture->invokeMethod($this->_customer, '_getNames', [$values]),
            '[Test _getNames] set empty firstname and lastname address'
        );

        $values = ['firstname' => '', 'lastname' => '', 'fullname' => ''];
        $this->assertEquals(
            ['firstname' => '__', 'lastname' => '__'],
            $fixture->invokeMethod($this->_customer, '_getNames', [$values]),
            '[Test _getNames] set empty address'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Customer::_splitNames()
     */
    public function testSplitNames()
    {
        $fixture = New Fixture();

        $this->assertEquals(
            ['firstname' => 'hihi', 'lastname' => ''],
            $fixture->invokeMethod($this->_customer, '_splitNames', ['hihi']),
            '[Test _splitNames] set one word'
        );

        $this->assertEquals(
            ['firstname' => 'plop', 'lastname' => 'machin'],
            $fixture->invokeMethod($this->_customer, '_splitNames', ['plop machin']),
            '[Test _splitNames] set two words'
        );

        $this->assertEquals(
            ['firstname' => 'plop', 'lastname' => 'machin bidule'],
            $fixture->invokeMethod($this->_customer, '_splitNames', ['plop machin bidule']),
            '[Test _splitNames] set three words'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Customer::_convertAddress()
     */
    public function testConvertAddress()
    {
        $fixture = New Fixture();

        $address = $this->_objectManager->getObject(Address::class);
        $region = $this->_objectManager->getObject(Region::class);
        $regionMock = $fixture->mockFunctions(
            $region,
            ['getId'],
            [1]
        );

        $addressFactoryMock = $fixture->mockFunctions($this->_addressFactoryMock, ['create'], [$address]);
        $regionCollectionMock = $this->_objectManager->getCollectionMock(RegionCollection::class, []);
        $regionCollectionMock->expects($this->once())
            ->method('addRegionCodeFilter')
            ->will($this->returnValue($regionCollectionMock));
        $regionCollectionMock->expects($this->once())
            ->method('addCountryFilter')
            ->will($this->returnValue($regionCollectionMock));
        $regionCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->will($this->returnValue($regionMock));
        $fixture->setPrivatePropertyValue(
            $this->_customer, ['_addressFactory', '_regionCollection'], [$addressFactoryMock, $regionCollectionMock]
        );

        $address1 = [
            'company' => 'company',
            'civility' => 'Madame',
            'email' => '123456-ABCCC--1508407265-natdec@magento22.docker',
            'last_name' => 'Doe',
            'first_name' => 'Jane',
            'first_line' => '22 rue des olivettes',
            'full_name' => 'machin bidule',
            'second_line' => 'apt 666 porte 5',
            'complement' => 'code 12345678',
            'zipcode' => '44000',
            'city' => 'NANTES',
            'common_country_iso_a2' => 'FR',
            'phone_home' => '0812345678',
            'phone_office' => '0866666666',
            'phone_mobile' => '0611224455'
        ];

        $this->assertEquals(
            $address,
            $fixture->invokeMethod($this->_customer, '_convertAddress', [$address1]),
            '[Test _convertAddress] @return \Magento\Customer\Model\Address'
        );

    }

}
