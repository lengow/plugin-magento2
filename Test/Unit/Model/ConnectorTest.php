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

namespace Lengow\Connector\Test\Unit\Model;

use Lengow\Connector\Model\Connector;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Lengow\Connector\Test\Unit\Fixture;
use Lengow\Connector\Model\Exception as LengowException;

class ConnectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Lengow\Connector\Model\Connector
     */
    protected $_connector;

    /**
     * @var \Lengow\Connector\Helper\Data
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Config
     */
    protected $_configHelper;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp() : void
    {
        $objectManager = new ObjectManager($this);
        $this->_connector = $objectManager->getObject(Connector::class);
        $this->_dataHelper = $objectManager->getObject(DataHelper::class);
        $this->_configHelper = $objectManager->getObject(ConfigHelper::class);
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Connector::class,
            $this->_connector,
            '[Test Class Instantiation] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Connector::connect
     */
    public function testConnect()
    {
        $fixture = new Fixture();
        $mockConnect = ['token' => '123TEST', 'account_id' => '123', 'user_id' => '123'];
        $connectorMock = $fixture->mockFunctions($this->_connector, ['callAction'], [$mockConnect]);
        $updatedAt = date('Y-m-d H:i:s', time() - 1000);
        $token = '123TEST';
        $configHelperMock = $this->getMockBuilder(get_class($this->_configHelper))
            ->setMethods(['get', 'isNewMerchant'])
            ->disableOriginalConstructor()
            ->getMock();
        $configHelperMock->expects($this->any())
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $token,
                strtotime($updatedAt)
            );
        $fixture->setPrivatePropertyValue(
            $connectorMock,
            ['configHelper'],
            [$configHelperMock],
            $this->_connector
        );
        $this->assertNull(
            $connectorMock->connect(),
            '[Test Connect] Check if return is valid when connection is ok'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Connector::format
     */
    public function testFormat()
    {
        $fixture = new Fixture();
        $this->assertEquals(
            ['id' => 1, 'name' => 'A green door', 'price' => '12.5', 'tags' => ['home', 'green']],
            $fixture->invokeMethod(
                $this->_connector,
                'format',
                ['{"id": 1,"name": "A green door","price": 12.50,"tags": ["home", "green"]}', 'json']
            ),
            '[Test Format] Check json format'
        );

        $this->assertEquals(
            'simple,plop,/1233;variable',
            $fixture->invokeMethod($this->_connector, "format", ['simple,plop,/1233;variable', 'stream']),
            '[Test Format] Check no specific format format'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Connector::queryApi
     */
    public function testQueryApi()
    {
        $fixture = new Fixture();

        $configHelperMock = $fixture->mockFunctions(
            $this->_configHelper,
            ['getAccessIds'],
            [[123, 'accessToken', 'secretToken']]
        );
        $connectorMock = $fixture->mockFunctions(
            $this->_connector,
            ['get'],
            ['{"id": 1,"name": "A green door","price": 12.50,"tags": ["home", "green"]}']
        );
        $fixture->setPrivatePropertyValue(
            $connectorMock,
            ['configHelper'],
            [$configHelperMock],
            $this->_connector
        );

        $this->assertNotTrue(
            $this->_connector->queryApi('plop', '/v3.0/cms'),
            '[Test Query API] Check if type is valid'
        );

        $this->assertEquals(
            json_decode('{"id": 1,"name": "A green door","price": 12.50,"tags": ["home", "green"]}'),
            $connectorMock->queryApi('GET', '/v3.0/cms'),
            '[Test Query API] Check if call is valid'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Connector::isValidAuth
     */
    public function testIsValidAuth()
    {

        $fixture = new Fixture();
        $classMock = $fixture->getFakeClass();
        $connectorMock = $fixture->mockFunctions($this->_connector, ['isCurlActivated'], [false]);
        $this->assertNotTrue(
            $connectorMock->isValidAuth(),
            '[Test Is Valid Auth] Check if API Authentication is refused when curl is not installed'
        );

        $connectorMock2 = $fixture->mockFunctions(
            $this->_connector,
            ['isCurlActivated', 'init', 'connect'],
            [true, null, ['token' => '123', 'account_id' => '123', 'user_id' => '123']]
        );
        $configHelperMock = $fixture->mockFunctions($classMock, ['getAccessIds'], [[null, null, null]]);
        $fixture->setPrivatePropertyValue(
            $connectorMock2,
            ['configHelper'],
            [$configHelperMock],
            $this->_connector
        );
        $this->assertFalse(
            $connectorMock2->isValidAuth(),
            '[Test Is Valid Auth] Check if API Authentication is refused when account id is null'
        );

        $configHelperMock2 = $fixture->mockFunctions(
            $this->_configHelper,
            ['getAccessIds'],
            [[0, 'accessToken', 'secretToken']]
        );
        $fixture->setPrivatePropertyValue(
            $connectorMock2,
            ['configHelper'],
            [$configHelperMock2],
            $this->_connector
        );

        $this->assertFalse(
            $connectorMock2->isValidAuth(),
            '[Test Is Valid Auth] Check if API Authentication is false when account id is equal 0'
        );


        $configHelperMock3 = $fixture->mockFunctions(
            $classMock,
            ['getAccessIds'],
            [['accountId', 'accessToken', 'secretToken']]
        );
        $fixture->setPrivatePropertyValue(
            $connectorMock2,
            ['configHelper'],
            [$configHelperMock3],
            $this->_connector
        );
        $this->assertFalse(
            $connectorMock2->isValidAuth(),
            '[Test Is Valid Auth] Check if API Authentication is refused when account id is not a number'
        );

        $configHelperMock4 = $fixture->mockFunctions(
            $classMock,
            ['getAccessIds'],
            [[123, 'accessToken', 'secretToken']]
        );
        $fixture->setPrivatePropertyValue(
            $connectorMock2,
            ['configHelper'],
            [$configHelperMock4],
            $this->_connector
        );

        $this->assertTrue(
            $connectorMock2->isValidAuth(),
            '[Test Is Valid Auth] Check if API Authentication is valid'
        );


        $connectorMock3 = $fixture->mockFunctions(
            $this->_connector,
            ['isCurlActivated', 'init', 'connect'],
            [true, null, null]
        );
        $connectorMock3->method('connect')
            ->willThrowException(new LengowException('token is null'));
        $dataHelperMock = $fixture->mockFunctions($this->_dataHelper, ['log'], [null]);
        $fixture->setPrivatePropertyValue(
            $connectorMock3,
            ['configHelper','dataHelper'],
            [$configHelperMock4, $dataHelperMock],
            $this->_connector
        );

        $this->assertFalse(
            $connectorMock3->isValidAuth(),
            '[Test Is Valid Auth] Check if API Authentication is refused when credentials are not valid'
        );
    }
}
