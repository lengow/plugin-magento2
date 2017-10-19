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

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Lengow\Connector\Test\Unit\Fixture;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Import\Customer;

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
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_configHelper = $objectManager->getObject(ConfigHelper::class);
        $this->_customer = $objectManager->getObject(Customer::class);
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

}
