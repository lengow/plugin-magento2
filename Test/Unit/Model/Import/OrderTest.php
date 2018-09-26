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
use Lengow\Connector\Model\Import\Order;

class OrderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Lengow\Connector\Model\Import\Order
     */
    protected $_order;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_order = $objectManager->getObject(Order::class);
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Order::class,
            $this->_order,
            '[Test Class Instantiation] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Order::getOrderProcessState()
     */
    public function testGetOrderProcessState()
    {
        $this->assertEquals(
            1,
            $this->_order->getOrderProcessState('accepted'),
            '[Test Get Order Process State] Check if return is valid for accepted'
        );
        $this->assertEquals(
            1,
            $this->_order->getOrderProcessState('waiting_shipment'),
            '[Test Get Order Process State] Check if return is valid for waiting_shipment'
        );
        $this->assertEquals(
            2,
            $this->_order->getOrderProcessState('shipped'),
            '[Test Get Order Process State] Check if return is valid for shipped'
        );
        $this->assertEquals(
            2,
            $this->_order->getOrderProcessState('closed'),
            '[Test Get Order Process State] Check if return is valid for closed'
        );
        $this->assertEquals(
            2,
            $this->_order->getOrderProcessState('refused'),
            '[Test Get Order Process State] Check if return is valid for refused'
        );
        $this->assertEquals(
            2,
            $this->_order->getOrderProcessState('canceled'),
            '[Test Get Order Process State] Check if return is valid for canceled'
        );
        $this->assertEquals(
            2,
            $this->_order->getOrderProcessState('refunded'),
            '[Test Get Order Process State] Check if return is valid for states not required'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Order::getOrderLineByApi()
     */
    public function testGetOrderLineByApi()
    {
        $fixture = New Fixture();
        $classMock = $fixture->getFakeClass();
        $connectorMock = $fixture->mockFunctions($classMock, ['queryApi'], [null]);
        $fixture->setPrivatePropertyValue($this->_order, ['_connector'], [$connectorMock]);
        $this->assertFalse(
            $this->_order->getOrderLineByApi('123-test-456', 'amazon_fr', 54321),
            '[Test Get Order Line By Api] Check if return is valid when API is down'
        );
        $apiOrder = '{"count":0, "next":null, "previous":null, "results":[]}';
        $connectorMock2 = $fixture->mockFunctions($classMock, ['queryApi'], [json_decode($apiOrder)]);
        $fixture->setPrivatePropertyValue($this->_order, ['_connector'], [$connectorMock2]);
        $this->assertFalse(
            $this->_order->getOrderLineByApi('123-test-456', 'amazon_fr', 54321),
            '[Test Get Order Line By Api] Check if return is valid when order is not found by the API'
        );
        $apiOrder2 = '{
            "count": 1,
            "next": null,
            "previous": null,
            "results": [{
                "packages": [{
                    "cart": [{
                            "marketplace_order_line_id": "123-test-456-1"
                        },
                        {
                            "marketplace_order_line_id": "123-test-456-2"
                        }
                    ],
                    "delivery": {
                        "id": 54321
                    }
                }]
            }]
        }';
        $connectorMock3 = $fixture->mockFunctions($classMock, ['queryApi'], [json_decode($apiOrder2)]);
        $fixture->setPrivatePropertyValue($this->_order, ['_connector'], [$connectorMock3]);
        $this->assertEquals(
            [['order_line_id' => '123-test-456-1'], ['order_line_id' => '123-test-456-2']],
            $this->_order->getOrderLineByApi('123-test-456', 'amazon_fr', 54321),
            '[Test Get Order Line By Api] Check if return is valid for order with one package and good delivery id'
        );
        $this->assertFalse(
            $this->_order->getOrderLineByApi('123-test-456', 'amazon_fr', 12345),
            '[Test Get Order Line By Api] Check if return is valid for order with one package and bad delivery id'
        );
        $apiOrder3 = '{
            "count": 1,
            "next": null,
            "previous": null,
            "results": [{
                "packages": [{
                    "cart": [
                        {
                            "marketplace_order_line_id": "123-test-456-1"
                        }
                    ],
                    "delivery": {
                        "id": 54321
                    }
                }, {
                    "cart": [
                        {
                            "marketplace_order_line_id": "123-test-456-2"
                        }
                    ],
                    "delivery": {
                        "id": 12345
                    }
                }]
            }]
        }';
        $connectorMock4 = $fixture->mockFunctions($classMock, ['queryApi'], [json_decode($apiOrder3)]);
        $fixture->setPrivatePropertyValue($this->_order, ['_connector'], [$connectorMock4]);
        $this->assertEquals(
            [['order_line_id' => '123-test-456-1']],
            $this->_order->getOrderLineByApi('123-test-456', 'amazon_fr', 54321),
            '[Test Get Order Line By Api] Check if return is valid for order with two packages'
        );
    }
}
