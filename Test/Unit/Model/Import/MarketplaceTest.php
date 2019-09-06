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
use Lengow\Connector\Model\Import\Marketplace;

class MarketplaceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Lengow\Connector\Model\Import\Marketplace
     */
    protected $_marketplace;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_marketplace = $objectManager->getObject(Marketplace::class);
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Marketplace::class,
            $this->_marketplace,
            '[Test Class Instantiation] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Marketplace::getStateLengow()
     */
    public function testGetStateLengow()
    {
        $fixture = New Fixture();
        $fixture->setPrivatePropertyValue($this->_marketplace, ['statesLengow'], [['Shipped' => 'shipped']]);
        $this->assertInternalType(
            'string',
            $this->_marketplace->getStateLengow('Shipped'),
            '[Test Get State Lengow] Check if return is a string'
        );
        $this->assertEquals(
            'shipped',
            $this->_marketplace->getStateLengow('Shipped'),
            '[Test Get State Lengow] Check if return is valid when lengow state is present'
        );
        $fixture->setPrivatePropertyValue($this->_marketplace, ['statesLengow'], [[]]);
        $this->assertEquals(
            '',
            $this->_marketplace->getStateLengow('test'),
            '[Test Get State Lengow] Check if return is valid when lengow state is empty'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Marketplace::getDefaultValue()
     */
    public function testGetDefaultValue()
    {
        $fixture = New Fixture();
        $this->assertFalse(
            $this->_marketplace->getDefaultValue('carrier'),
            '[Test Get Default Value] Check if return is a false when argument is empty'
        );
        $argValues = [
            'carrier' => [
                'default_value' => 'LAPOSTE',
                'accept_free_values' => false,
                'valid_values' => [],
            ],
            'tracking_number' => [
                'default_value' => '',
                'accept_free_values' => true,
                'valid_values' => [],
            ]
        ];
        $fixture->setPrivatePropertyValue($this->_marketplace, ['argValues'], [$argValues]);
        $this->assertEquals(
            'LAPOSTE',
            $this->_marketplace->getDefaultValue('carrier'),
            '[Test Get Default Value] Check if return is valid when default value is present'
        );
        $this->assertFalse(
            $this->_marketplace->getDefaultValue('tracking_number'),
            '[Test Get Default Value] Check if return is a false when default value is empty'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Marketplace::containOrderLine()
     */
    public function testContainOrderLine()
    {
        $fixture = New Fixture();
        $actions = [
            'ship' => [
                'args' => ['carrier' => 'carrier', 'line' => 'line'],
                'optional_args' => [],
            ],
        ];
        $fixture->setPrivatePropertyValue($this->_marketplace, ['actions'], [$actions]);
        $this->assertInternalType(
            'boolean',
            $this->_marketplace->containOrderLine('ship'),
            '[Test Contain Order Line] Check if return is a boolean'
        );
        $this->assertTrue(
            $this->_marketplace->containOrderLine('ship'),
            '[Test Contain Order Line] Check if return is valid when line argument is required'
        );
        $actions = [
            'ship' => [
                'args' => ['carrier' => 'carrier'],
                'optional_args' => ['line' => 'line'],
            ],
        ];
        $fixture->setPrivatePropertyValue($this->_marketplace, ['actions'], [$actions]);
        $this->assertTrue(
            $this->_marketplace->containOrderLine('ship'),
            '[Test Contain Order Line] Check if return is valid when line argument is optional'
        );
        $actions = [
            'ship' => [
                'args' => ['carrier' => 'carrier', 'tracking_number' => 'tracking_number'],
                'optional_args' => [],
            ],
        ];
        $fixture->setPrivatePropertyValue($this->_marketplace, ['actions'], [$actions]);
        $this->assertFalse(
            $this->_marketplace->containOrderLine('ship'),
            '[Test Contain Order Line] Check if return is valid when line argument is not present'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Marketplace::_matchCarrier()
     */
    public function testMatchCarrier()
    {
        $fixture = New Fixture();
        $this->assertInternalType(
            'string',
            $fixture->invokeMethod($this->_marketplace, '_matchCarrier', ['custom', 'my carrier']),
            '[Test Match Carrier] Check if return is a string'
        );
        $carriers = [
            'FEDEX' => 'FedEx',
            'CHRONOPOST' => 'Chronopost',
            'COLISSIMO' => 'La Poste - Colissimo',
            'PITALIA' => 'Post Italia',
        ];
        $fixture->setPrivatePropertyValue($this->_marketplace, ['carriers'], [$carriers]);
        $this->assertEquals(
            'my carrier',
            $fixture->invokeMethod($this->_marketplace, '_matchCarrier', ['custom', 'my carrier']),
            '[Test Match Carrier] Check if return is valid when match is not possible for custom carrier'
        );
        $this->assertEquals(
            'COLISSIMO',
            $fixture->invokeMethod($this->_marketplace, '_matchCarrier', ['custom', 'La Poste - Colissimo']),
            '[Test Match Carrier] Check if return is valid when match is possible for custom carrier'
        );
        $this->assertEquals(
            'FEDEX',
            $fixture->invokeMethod($this->_marketplace, '_matchCarrier', ['FEDEX', 'my carrier']),
            '[Test Match Carrier] Check if return is valid when match is possible for specific carrier'
        );
        $this->assertEquals(
            'DHL',
            $fixture->invokeMethod($this->_marketplace, '_matchCarrier', ['DHL', 'DHL France - 24 hours']),
            '[Test Match Carrier] Check if return is valid when match is not possible for specific carrier'
        );
        $this->assertEquals(
            'PITALIA',
            $fixture->invokeMethod($this->_marketplace, '_matchCarrier', ['custom', 'Post Italia']),
            '[Test Match Carrier] Check if return is valid when strict match is possible with carrier label'
        );
        $this->assertEquals(
            'PITALIA',
            $fixture->invokeMethod($this->_marketplace, '_matchCarrier', ['custom', 'Post Italia international']),
            '[Test Match Carrier] Check if return is valid when approximate match is possible with carrier label'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Marketplace::_cleanString()
     */
    public function testCleanString()
    {
        $fixture = New Fixture();
        $this->assertInternalType(
            'string',
            $fixture->invokeMethod($this->_marketplace, '_cleanString', ['custom']),
            '[Test Clean String] Check if return is a string'
        );
        $this->assertEquals(
            'mygreatcarrier',
            $fixture->invokeMethod($this->_marketplace, '_cleanString', [' My-GREAT_carrier.']),
            '[Test Clean String] Check if return is valid when match is not possible for custom carrier'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Marketplace::_searchValue()
     */
    public function testSearchValue()
    {
        $fixture = New Fixture();
        $this->assertInternalType(
            'boolean',
            $fixture->invokeMethod($this->_marketplace, '_searchValue', ['custom', 'custom']),
            '[Test Search Value] Check if return is a boolean'
        );
        $this->assertFalse(
            $fixture->invokeMethod($this->_marketplace, '_searchValue', ['toto', 'tata']),
            '[Test Search Value] Check if return is valid when any code found'
        );
        $this->assertTrue(
            $fixture->invokeMethod($this->_marketplace, '_searchValue', ['toto', 'toto']),
            '[Test Search Value] Check if return is valid with strict match'
        );
        $this->assertTrue(
            $fixture->invokeMethod($this->_marketplace, '_searchValue', ['toto', 'mygreattoto']),
            '[Test Search Value] Check if return is valid with approximate match'
        );
    }
}
