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

use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\PhpEnvironment\ServerAddress;
use Lengow\Connector\Test\Unit\Fixture;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class SecurityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Lengow\Connector\Helper\Security
     */
    protected $_securityHelper;

    /**
     * @var \Lengow\Connector\Helper\Config
     */
    protected $_configHelper;

    /**
     * @var \Magento\Framework\App\Helper\Context
     */
    protected $_context;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\ServerAddress
     */
    protected $_serverAddress;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_securityHelper = $objectManager->getObject(SecurityHelper::class);
        $this->_configHelper = $objectManager->getObject(ConfigHelper::class);
        $this->_context = $objectManager->getObject(Context::class);
        $this->_serverAddress = $objectManager->getObject(ServerAddress::class);
    }

    public function testClassInstance()
    {
        $this->assertInstanceOf(
            SecurityHelper::class,
            $this->_securityHelper,
            '[Test Class Instance] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Helper\Security::getAuthorizedIps
     */
    public function testGetAuthorizedIps()
    {
        $fixture = New Fixture();
        $ipsLengow = $fixture->getPrivatePropertyValue($this->_securityHelper, '_ipsLengow');
        $configHelperMock = $fixture->mockFunctions($this->_configHelper, ['get'], ['127.0.0.4']);
        $securityHelperMock = $fixture->mockFunctions(
            $this->_securityHelper,
            ['getServerIp'],
            ['127.0.0.1'],
            [$this->_context, $configHelperMock, $this->_serverAddress]
        );
        $this->assertInternalType(
            'array',
            $securityHelperMock->getAuthorizedIps(),
            '[Test Get Authorized Ips] Check if return is a array'
        );

        $configHelperMock2 = $fixture->mockFunctions($this->_configHelper, ['get'], ['127.0.0.4']);
        $securityHelperMock2 = $fixture->mockFunctions(
            $this->_securityHelper,
            ['getServerIp'],
            ['127.0.0.1'],
            [$this->_context, $configHelperMock2, $this->_serverAddress]
        );
        $this->assertEquals(
            array_merge(['127.0.0.4'], $ipsLengow, ['127.0.0.1']),
            $securityHelperMock2->getAuthorizedIps(),
            '[Test Get Authorized Ips] Check if return is valid'
        );

        $configHelperMock2 = $fixture->mockFunctions($this->_configHelper, ['get'], [null]);
        $securityHelperMock2 = $fixture->mockFunctions(
            $this->_securityHelper,
            ['getServerIp'],
            ['127.0.0.1'],
            [$this->_context, $configHelperMock2, $this->_serverAddress]
        );
        $this->assertEquals(
            array_merge($ipsLengow, ['127.0.0.1']),
            $securityHelperMock2->getAuthorizedIps(),
            '[Test Get Authorized Ips] Check if return is valid when autorized ips is null'
        );

        $configHelperMock3 = $fixture->mockFunctions(
            $this->_configHelper,
            ['get'],
            ['127.0.0.2;127.0.0.3,127.0.0.4 127.0.0.5-127.0.0.6|127.0.0.7']
        );
        $securityHelperMock3 = $fixture->mockFunctions(
            $this->_securityHelper,
            ['getServerIp'],
            ['127.0.0.8'],
            [$this->_context, $configHelperMock3, $this->_serverAddress]
        );
        $ips = ['127.0.0.2', '127.0.0.3', '127.0.0.4', '127.0.0.5', '127.0.0.6', '127.0.0.7'];
        $this->assertEquals(
            array_merge($ips, $ipsLengow, ['127.0.0.8']),
            $securityHelperMock3->getAuthorizedIps(),
            '[Test Get Authorized Ips] Check if return is valid when autorized ips containts specials characters'
        );
    }

    /**
     * @covers \Lengow\Connector\Helper\Security::checkIp
     */
    public function testCheckIP()
    {
        $fixture = New Fixture();
        $securityHelperMock = $fixture->mockFunctions(
            $this->_securityHelper,
            ['getAuthorizedIps', 'getRemoteIp'],
            [['127.0.0.1'], '127.0.0.4']
        );
        $this->assertInternalType(
            'boolean',
            $securityHelperMock->checkIp(),
            '[Test Check IP] Check if return is a boolean'
        );

        $securityHelperMock2 = $fixture->mockFunctions(
            $this->_securityHelper,
            ['getAuthorizedIps', 'getRemoteIp'],
            [['127.0.0.1', '127.0.0.2', '127.0.0.3', '127.0.0.4'], '127.0.0.4']
        );
        $this->assertTrue($securityHelperMock2->checkIp(), '[Test Check IP] Check if return is valid');

        $securityHelperMock3 = $fixture->mockFunctions(
            $this->_securityHelper,
            ['getAuthorizedIps', 'getRemoteIp'],
            [['127.0.0.1', '127.0.0.2', '127.0.0.3'], '127.0.0.5']
        );
        $this->assertNotTrue($securityHelperMock3->checkIp(), '[Test Check IP] Check if return is not valid');
    }
}
