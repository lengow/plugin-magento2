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

class OrderTest extends \PHPUnit\Framework\TestCase
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
    public function setUp() : void
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
     * @covers \Lengow\Connector\Model\Import\Order::isOrderShipmentComplete()
     */
    public function testIsOrderShipmentComplete()
    {
        $fixture = new Fixture();

        // test with empty order lines
        $this->assertFalse(
            $fixture->invokeMethod($this->_order, 'isOrderShipmentComplete', [[], []]),
            '[Test Is Order Shipment Complete] Check returns false with empty order lines'
        );

        // test with incomplete progress
        $orderLines = [
            ['order_line_id' => 'line-1', 'quantity' => 3],
            ['order_line_id' => 'line-2', 'quantity' => 2],
        ];
        $progress = [
            'line-1' => ['qty_original' => 3, 'qty_shipped' => 2],
            'line-2' => ['qty_original' => 2, 'qty_shipped' => 2],
        ];
        $this->assertFalse(
            $fixture->invokeMethod($this->_order, 'isOrderShipmentComplete', [$progress, $orderLines]),
            '[Test Is Order Shipment Complete] Check returns false when not all lines are complete'
        );

        // test with complete progress
        $progress['line-1']['qty_shipped'] = 3;
        $this->assertTrue(
            $fixture->invokeMethod($this->_order, 'isOrderShipmentComplete', [$progress, $orderLines]),
            '[Test Is Order Shipment Complete] Check returns true when all lines are complete'
        );

        // test with over-shipped (qty_shipped > qty_original)
        $progress['line-1']['qty_shipped'] = 5;
        $this->assertTrue(
            $fixture->invokeMethod($this->_order, 'isOrderShipmentComplete', [$progress, $orderLines]),
            '[Test Is Order Shipment Complete] Check returns true when lines are over-shipped'
        );

        // test with missing progress for a line
        $progressIncomplete = [
            'line-1' => ['qty_original' => 3, 'qty_shipped' => 3],
        ];
        $this->assertFalse(
            $fixture->invokeMethod($this->_order, 'isOrderShipmentComplete', [$progressIncomplete, $orderLines]),
            '[Test Is Order Shipment Complete] Check returns false when a line has no progress'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Order::getOrderProcessState()
     */
    public function testGetOrderProcessStateConstants()
    {
        $this->assertEquals(0, Order::PROCESS_STATE_NEW);
        $this->assertEquals(1, Order::PROCESS_STATE_IMPORT);
        $this->assertEquals(2, Order::PROCESS_STATE_FINISH);
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Order::getOrderLineByApi()
     */
    public function testGetOrderLineByApi()
    {
        $fixture = new Fixture();
        $classMock = $fixture->getFakeClass();
        $connectorMock = $fixture->mockFunctions($classMock, ['queryApi'], [null]);
        $fixture->setPrivatePropertyValue($this->_order, ['lengowConnector'], [$connectorMock]);
        $this->assertEquals(
            [],
            $this->_order->getOrderLineByApi('123-test-456', 'amazon_fr', 54321),
            '[Test Get Order Line By Api] Check if return is valid when API is down'
        );
        $apiOrder = '{"count":0, "next":null, "previous":null, "results":[]}';
        $connectorMock2 = $fixture->mockFunctions($classMock, ['queryApi'], [json_decode($apiOrder)]);
        $fixture->setPrivatePropertyValue($this->_order, ['lengowConnector'], [$connectorMock2]);
        $this->assertEquals(
            [],
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
        $fixture->setPrivatePropertyValue($this->_order, ['lengowConnector'], [$connectorMock3]);
        $this->assertEquals(
            [['order_line_id' => '123-test-456-1'], ['order_line_id' => '123-test-456-2']],
            $this->_order->getOrderLineByApi('123-test-456', 'amazon_fr', 54321),
            '[Test Get Order Line By Api] Check if return is valid for order with one package and good delivery id'
        );
        $this->assertEquals(
            [],
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
        $fixture->setPrivatePropertyValue($this->_order, ['lengowConnector'], [$connectorMock4]);
        $this->assertEquals(
            [['order_line_id' => '123-test-456-1']],
            $this->_order->getOrderLineByApi('123-test-456', 'amazon_fr', 54321),
            '[Test Get Order Line By Api] Check if return is valid for order with two packages'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Order::getLegacyShipLineParams()
     */
    public function testGetLegacyShipLineParams()
    {
        $fixture = new Fixture();
        $marketplaceArgs = ['line', 'quantity', 'tracking_number'];

        $this->assertNull(
            $fixture->invokeMethod(
                $this->_order,
                'getLegacyShipLineParams',
                [
                    ['args' => ['line', 'quantity']],
                    $marketplaceArgs,
                    ['order_line_id' => 'line-1'],
                ]
            ),
            '[Test Get Legacy Ship Line Params] Check returns null when required quantity is unavailable'
        );

        $this->assertEquals(
            ['quantity' => 2],
            $fixture->invokeMethod(
                $this->_order,
                'getLegacyShipLineParams',
                [
                    ['args' => ['line']],
                    $marketplaceArgs,
                    ['order_line_id' => 'line-1', 'quantity' => 2],
                ]
            ),
            '[Test Get Legacy Ship Line Params] Check returns optional quantity when available'
        );

        $this->assertEquals(
            [],
            $fixture->invokeMethod(
                $this->_order,
                'getLegacyShipLineParams',
                [
                    ['args' => ['line']],
                    ['line', 'tracking_number'],
                    ['order_line_id' => 'line-1'],
                ]
            ),
            '[Test Get Legacy Ship Line Params] Check returns empty params when no quantity argument exists'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Import\Order::restoreLineProgress()
     */
    public function testRestoreLineProgress()
    {
        $fixture = new Fixture();
        $progress = [
            'line-1' => ['qty_original' => 2, 'qty_shipped' => 2],
            'line-2' => ['qty_original' => 1, 'qty_shipped' => 1],
        ];

        $fixture->invokeMethod(
            $this->_order,
            'restoreLineProgress',
            [&$progress, 'line-1', ['qty_original' => 2, 'qty_shipped' => 1]]
        );
        $this->assertEquals(
            ['qty_original' => 2, 'qty_shipped' => 1],
            $progress['line-1'],
            '[Test Restore Line Progress] Check successful lines keep only the failed line rollback'
        );
        $this->assertEquals(
            ['qty_original' => 1, 'qty_shipped' => 1],
            $progress['line-2'],
            '[Test Restore Line Progress] Check unrelated line progress is preserved'
        );

        $fixture->invokeMethod(
            $this->_order,
            'restoreLineProgress',
            [&$progress, 'line-3', null]
        );
        $this->assertArrayNotHasKey(
            'line-3',
            $progress,
            '[Test Restore Line Progress] Check line is removed when it had no prior progress'
        );
    }
}
