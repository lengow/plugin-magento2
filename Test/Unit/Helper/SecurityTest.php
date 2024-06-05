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
use Lengow\Connector\Test\Unit\Fixture;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class SecurityTest extends \PHPUnit\Framework\TestCase
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
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp() : void
    {
        $objectManager = new ObjectManager($this);
        $this->_securityHelper = $objectManager->getObject(SecurityHelper::class);
        $this->_configHelper = $objectManager->getObject(ConfigHelper::class);
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
     * @covers \Lengow\Connector\Helper\Security::checkWebserviceAccess
     */
    public function testCheckWebserviceAccess()
    {
        $fixture = new Fixture();
        $securityHelperMock = $fixture->mockFunctions($this->_securityHelper, ['checkToken', 'checkIp'], [true, false]);
        $configHelperMock = $fixture->mockFunctions($this->_configHelper, ['get','set'], [0, null]);
        $fixture->setPrivatePropertyValue(
            $securityHelperMock,
            ['configHelper'],
            [$configHelperMock],
            $this->_securityHelper
        );
        $this->assertIsBool(
            $securityHelperMock->checkWebserviceAccess('bd30439b3d2ce0bc63ac59fe0eac2060'),
            '[Test Check Webservice Access] Check if return is a array'
        );

        $this->assertTrue(
            $this->_securityHelper->checkWebserviceAccess('bd30439b3d2ce0bc63ac59fe0eac2060'),
            '[Test Check Webservice Access] Check return with valid token authorisation'
        );
        $securityHelperMock2 = $fixture->mockFunctions(
            $this->_securityHelper,
            ['checkToken', 'checkIp'],
            [false, false]
        );

        $fixture->setPrivatePropertyValue($securityHelperMock2, ['configHelper'], [$configHelperMock], $this->_securityHelper);
        $this->assertNotTrue(
            $securityHelperMock2->checkWebserviceAccess('bd30439b3d2ce0bc63ac59fe0eac2060'),
            '[Test Check Webservice Access] Check return with invalid token authorisation'
        );

        $securityHelperMock3 = $fixture->mockFunctions(
            $this->_securityHelper,
            ['checkToken', 'checkIp'],
            [false, true]
        );
        $fixture->setPrivatePropertyValue($securityHelperMock3, ['configHelper'], [$configHelperMock], $this->_securityHelper);
        $this->assertTrue(
            $securityHelperMock3->checkWebserviceAccess('bd30439b3d2ce0bc63ac59fe0eac2060'),
            '[Test Check Webservice Access] Check return with invalid token authorisation but valid ip (Lengow access)'
        );
        $securityHelperMock4 = $fixture->mockFunctions(
            $this->_securityHelper,
            ['checkToken', 'checkIp'],
            [true, false]
        );
        $configHelperMock2 = $fixture->mockFunctions($this->_configHelper, ['get'], [1]);
        $fixture->setPrivatePropertyValue($securityHelperMock4, ['configHelper'], [$configHelperMock2], $this->_securityHelper);
        $this->assertNotTrue(
            $securityHelperMock4->checkWebserviceAccess('bd30439b3d2ce0bc63ac59fe0eac2060'),
            '[Test Check Webservice Access] Check return when ip authorisation is enable but not valid'
        );
        $securityHelperMock5 = $fixture->mockFunctions($this->_securityHelper, ['checkToken', 'checkIp'], [true, true]);
        $fixture->setPrivatePropertyValue($securityHelperMock5, ['configHelper'], [$configHelperMock2], $this->_securityHelper);
        $this->assertTrue(
            $securityHelperMock5->checkWebserviceAccess('bd30439b3d2ce0bc63ac59fe0eac2060'),
            '[Test Check Webservice Access] Check return when ip authorisation is enable and valid'
        );
    }

    /**
     * @covers \Lengow\Connector\Helper\Security::checkToken
     */
    public function testCheckToken()
    {
        $fixture = new Fixture();
        $configHelperMock = $fixture->mockFunctions(
            $this->_configHelper,
            ['getToken'],
            ['bd30439b3d2ce0bc63ac59fe0eac2060']
        );
        $fixture->setPrivatePropertyValue($this->_securityHelper, ['configHelper'], [$configHelperMock]);
        $this->assertIsBool(
            $this->_securityHelper->checkToken('bd30439b3d2ce0bc63ac59fe0eac2060'),
            '[Test Check Token] Check if return is a array'
        );
        $this->assertTrue(
            $this->_securityHelper->checkToken('bd30439b3d2ce0bc63ac59fe0eac2060'),
            '[Test Check Token] Check if valid with a correct token'
        );
        $this->assertNotTrue(
            $this->_securityHelper->checkToken('ee8f8dc3171654b1fff77388fa3fc4ce'),
            '[Test Check Token] Check if valid with a fake token'
        );
    }
}
