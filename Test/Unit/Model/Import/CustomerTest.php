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

class CustomerTest extends \PHPUnit\Framework\TestCase
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
    public function setUp() : void
    {
        $this->_objectManager = new ObjectManager($this);
        $this->_configHelper = $this->_objectManager->getObject(ConfigHelper::class);
        $this->_customer = $this->_objectManager->getObject(Customer::class);
        $this->_addressFactoryMock = $this->createMock(AddressFactory::class);
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
     * @covers \Lengow\Connector\Model\Import\Customer::getNames()
     */
    public function testGetNames()
    {
        $fixture = new Fixture();

        $values = ['firstName' => 'John', 'lastName' => 'Doe'];
        $adressData = '{
            "company": null,
            "civility": null,
            "first_name": "John",
            "last_name": "Doe",
            "second_line": null,
            "complement": null,
            "phone_home": null,
            "phone_office": null,
            "phone_mobile": null,
            "full_address": null,
            "full_name": "John Doe",
            "email": "john.doe@test-mail.com"
        }';

        $this->assertIsArray(
            $fixture->invokeMethod($this->_customer, 'getNames', [json_decode($adressData)]),
            '[Test getNames] is array'
        );

        $this->assertEquals(
            $values,
            $fixture->invokeMethod($this->_customer, 'getNames', [json_decode($adressData)]),
            '[Test getNames] set complete address'
        );
        $adressData2 = '{
            "company": null,
            "civility": null,
            "first_name": null,
            "last_name": null,
            "second_line": null,
            "complement": null,
            "phone_home": null,
            "phone_office": null,
            "phone_mobile": null,
            "full_address": null,
            "full_name": "John Doe",
            "email": "john.doe@test-mail.com"
        }';
        $this->assertIsArray(
            $fixture->invokeMethod($this->_customer, 'getNames', [json_decode($adressData)]),
            '[Test getNames] is array'
        );
        $this->assertEquals(
            $values,
            $fixture->invokeMethod($this->_customer, 'getNames', [json_decode($adressData)]),
            '[Test _getNames] set empty address'
        );

         $adressData3 = '{
            "company": null,
            "civility": null,
            "first_name": null,
            "last_name": null,
            "second_line": null,
            "complement": null,
            "phone_home": null,
            "phone_office": null,
            "phone_mobile": null,
            "full_address": null,
            "full_name": "",
            "email": "john.doe@test-mail.com"
        }';
        $this->assertIsArray(
            $fixture->invokeMethod($this->_customer, 'getNames', [json_decode($adressData3)]),
            '[Test getNames] is array'
        );
        $valuesEmpty = ['firstName' => '__', 'lastName' => '__'];
        $this->assertEquals(
            $valuesEmpty,
            $fixture->invokeMethod($this->_customer, 'getNames', [json_decode($adressData3)]),
            '[Test _getNames] set empty address'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Customer::splitNames()
     */
    public function testSplitNames()
    {
        $fixture = new Fixture();

        $this->assertEquals(
            ['firstName' => 'hihi', 'lastName' => ''],
            $fixture->invokeMethod($this->_customer, 'splitNames', ['hihi']),
            '[Test splitNames] set one word'
        );

        $this->assertEquals(
            ['firstName' => 'plop', 'lastName' => 'machin'],
            $fixture->invokeMethod($this->_customer, 'splitNames', ['plop machin']),
            '[Test splitNames] set two words'
        );

        $this->assertEquals(
            ['firstName' => 'plop', 'lastName' => 'machin bidule'],
            $fixture->invokeMethod($this->_customer, 'splitNames', ['plop machin bidule']),
            '[Test splitNames] set three words'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Customer::hydrateAddress()
     */
    public function testHydrateAddress()
    {
        $fixture = new Fixture();

        $adressData = '{
                "id": 572984019,
                "type": "billing",
                "first_line": null,
                "zipcode": "75228-6481",
                "city": "DALLAS",
                "vat_number": null,
                "common_country_iso_a2": "US",
                "company": null,
                "civility": null,
                "first_name": null,
                "last_name": null,
                "second_line": null,
                "complement": null,
                "phone_home": null,
                "phone_office": null,
                "phone_mobile": null,
                "full_address": null,
                "full_name": null,
                "email": "m1z9b3mk8gb1c26@marketplace.amazon.com",
                "metas": null,
                "state_region": "TX"
        }';

        $orderData = '{
            "lengow_status": "shipped",
            "order_types": [],
            "original_total_buyer_amount": null,
            "original_total_buyer_tax": null,
            "original_buyer_currency": null
        }';

        $this->assertIsObject(
            $fixture->invokeMethod($this->_customer, 'hydrateAddress', [json_decode($orderData), json_decode($adressData)]),
            '[Test hydrateAddress]'
        );

        $this->assertEquals(
            json_decode($adressData),
            $fixture->invokeMethod($this->_customer, 'hydrateAddress', [json_decode($orderData), json_decode($adressData)]),
            '[Test hydrateAddress]'
        );

        $dataHydrated = '{
                "id": 572984019,
                "type": "billing",
                "first_line": null,
                "zipcode": "75228-6481",
                "city": "DALLAS",
                "vat_number": null,
                "common_country_iso_a2": "US",
                "company": null,
                "civility": null,
                "first_name": null,
                "last_name": null,
                "second_line": null,
                "complement": null,
                "phone_home": "0000000000",
                "phone_office": null,
                "phone_mobile": "0000000000",
                "full_address": null,
                "full_name": "",
                "email": "m1z9b3mk8gb1c26@marketplace.amazon.com",
                "metas": null,
                "state_region": "TX"
        }';
        $orderData2 = '{
            "lengow_status": "shipped",
            "order_types": [
                {
                    "type": "is_delivered_by_marketplace",
                    "label": "Fulfillment"
                },
                {
                    "type": "is_express",
                    "label": "Express"
                }
            ],
            "original_total_buyer_amount": null,
            "original_total_buyer_tax": null,
            "original_buyer_currency": null
        }';
        $addressWaited = json_decode($dataHydrated);
        $notProvided = __('Not provided by the marketplace');
        $addressWaited->first_name = $notProvided;
        $addressWaited->last_name = $notProvided;
        $addressWaited->full_name = $notProvided;
        $addressWaited->first_line = $notProvided;
        $addressWaited->full_address = $notProvided;


        $this->assertEquals(
            $addressWaited,
            $fixture->invokeMethod($this->_customer, 'hydrateAddress', [json_decode($orderData2), json_decode($adressData)]),
            '[Test hydrateAddress]'
        );
    }
}
