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

class ConnectorTest extends \PHPUnit_Framework_TestCase
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
    public function setUp()
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
        $fixture = New Fixture();
        $mockConnect = ['token' => '123TEST', 'account_id' => '123', 'user_id' => '123'];
        $connectorMock = $fixture->mockFunctions($this->_connector, ['callAction'], [$mockConnect]);
        $this->assertEquals(
            $connectorMock->connect(),
            $mockConnect,
            '[Test Connect] Check if return is valid when connection is ok'
        );

        $connectorMock2 = $fixture->mockFunctions($this->_connector, ['callAction'], [null]);
        $this->assertNotTrue(
            $connectorMock2->connect(),
            '[Test Connect] Check if return is valid when connection is failed'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Connector::_format
     */
    public function testFormat()
    {
        $fixture = New Fixture();
        $this->assertEquals(
            ['id' => 1, 'name' => 'A green door', 'price' => '12.5', 'tags' => ['home', 'green']],
            $fixture->invokeMethod(
                $this->_connector,
                '_format',
                ['{"id": 1,"name": "A green door","price": 12.50,"tags": ["home", "green"]}', 'json']
            ),
            '[Test Format] Check json format'
        );

        $this->assertEquals(
            'simple,plop,/1233;variable',
            $fixture->invokeMethod($this->_connector, '_format', ['simple,plop,/1233;variable', 'csv']),
            '[Test Format] Check csv format'
        );

        $string = "<?xml version='1.0'?>
            <document>
                <title>Forty What?</title>
                <from>Joe</from>
                <to>Jane</to>
                <body>I know that's the answer -- but what's the question?</body>
            </document>";
        $this->assertEquals(
            new \SimpleXMLElement($string),
            $fixture->invokeMethod($this->_connector, '_format', [$string, 'xml']),
            '[Test Format] Check xml format'
        );

        $this->assertEquals(
            'simple,plop,/1233;variable',
            $fixture->invokeMethod($this->_connector, '_format', ['simple,plop,/1233;variable', 'stream']),
            '[Test Format] Check stream format'
        );

        $this->assertEquals(
            'simple,plop,/1233;variable',
            $fixture->invokeMethod($this->_connector, "_format", ['simple,plop,/1233;variable', 'plop']),
            '[Test Format] Check no specific format format'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Connector::queryApi
     */
    public function testQueryApi()
    {
        $fixture = New Fixture();
        $this->assertNotTrue(
            $this->_connector->queryApi('plop', '/v3.0/cms'),
            '[Test Query API] Check if type is valid'
        );

        $connectorMock = $fixture->mockFunctions(
            $this->_connector,
            ['validAuthenticationByStore', 'get'],
            [false, 'simple,plop,/1233;variable']
        );
        $this->assertNotTrue(
            $connectorMock->queryApi('get', '/v3.0/cms'),
            '[Test Query API] Check if API Authentication is not valid'
        );

        $connectorMock = $fixture->mockFunctions(
            $this->_connector,
            ['validAuthenticationByStore', 'get'],
            [123, '{"id": 1,"name": "A green door","price": 12.50,"tags": ["home", "green"]}']
        );
        $this->assertEquals(
            json_decode('{"id": 1,"name": "A green door","price": 12.50,"tags": ["home", "green"]}'),
            $connectorMock->queryApi('get', '/v3.0/cms'),
            '[Test Query API] Check if call is valid'
        );

    }

    /**
     * @covers \Lengow\Connector\Model\Connector::validAuthenticationByStore
     */
    public function testValidAuthenticationByStore()
    {
        $fixture = New Fixture();
        $configHelperMock = $fixture->mockFunctions($this->_configHelper, ['getAccessId'], [[123, '123', '123']]);
        $connectorMock = $fixture->mockFunctions(
            $this->_connector,
            ['connect', 'isCurlActivated'],
            [['token' => '123', 'account_id' => '123', 'user_id' => '123'], true],
            [$this->_dataHelper, $configHelperMock]
        );
        $this->assertEquals(
            123,
            $connectorMock->validAuthenticationByStore(),
            '[Test Is Valid Auth] Check if API Authentication is valid'
        );

        $configHelperMock2 = $fixture->mockFunctions($this->_configHelper, ['getAccessId'], [[123, '123', '123']]);
        $connectorMock2 = $fixture->mockFunctions(
            $this->_connector,
            ['connect', 'isCurlActivated'],
            [['token' => '123', 'account_id' => '123', 'user_id' => '123'], false],
            [$this->_dataHelper, $configHelperMock2]
        );
        $this->assertNotTrue(
            $connectorMock2->validAuthenticationByStore(),
            '[Test Is Valid Auth] Check if API Authentication is refused when curl is not installed'
        );

        $configHelperMock3 = $fixture->mockFunctions($this->_configHelper, ['getAccessId'], [[123, '123', '123']]);
        $connectorMock3 = $fixture->mockFunctions(
            $this->_connector,
            ['connect', 'isCurlActivated'],
            [false, true],
            [$this->_dataHelper, $configHelperMock3]
        );
        $this->assertNotTrue(
            $connectorMock3->validAuthenticationByStore(),
            '[Test Is Valid Auth] Check if API Authentication is refused when credentials are not valid'
        );

        $configHelperMock4 = $fixture->mockFunctions($this->_configHelper, ['getAccessId'], [[null, null, null]]);
        $connectorMock4 = $fixture->mockFunctions(
            $this->_connector,
            ['connect', 'isCurlActivated'],
            [['token' => '123', 'account_id' => '123', 'user_id' => '123'], true],
            [$this->_dataHelper, $configHelperMock4]
        );
        $this->assertNotTrue(
            $connectorMock4->validAuthenticationByStore(),
            '[Test Is Valid Auth] Check if API Authentication is refused when account id is null'
        );

        $configHelperMock5 = $fixture->mockFunctions($this->_configHelper, ['getAccessId'], [['plop', 'plop', 'plop']]);
        $connectorMock5 = $fixture->mockFunctions(
            $this->_connector,
            ['connect', 'isCurlActivated'],
            [['token' => '123', 'account_id' => '123', 'user_id' => '123'], true],
            [$this->_dataHelper, $configHelperMock5]
        );
        $this->assertNotTrue(
            $connectorMock5->validAuthenticationByStore(),
            '[Test Is Valid Auth] Check if API Authentication is refused when account id is not a number'
        );
    }
}
