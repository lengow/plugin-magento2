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

use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Magento\Framework\App\Helper\Context;
use Lengow\Connector\Test\Unit\Fixture;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class SyncTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Lengow\Connector\Helper\Sync
     */
    protected $_syncHelper;

    /**
     * @var \Lengow\Connector\Helper\Config
     */
    protected $_configHelper;

    /**
     * @var \Magento\Framework\App\Helper\Context
     */
    protected $_context;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_syncHelper = $objectManager->getObject(SyncHelper::class);
        $this->_configHelper = $objectManager->getObject(ConfigHelper::class);
        $this->_context = $objectManager->getObject(Context::class);
    }

    public function testClassInstance()
    {
        $this->assertInstanceOf(
            SyncHelper::class,
            $this->_syncHelper,
            '[Test Class Instance] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Helper\Sync::isSyncAction
     */
    public function testIsSyncAction()
    {
        $fixture = New Fixture();
        $fixture->setPrivatePropertyValue(
            $this->_syncHelper,
            ['_syncActions'],
            [['order', 'action', 'catalog', 'option']]
        );
        $this->assertInternalType(
            'boolean',
            $this->_syncHelper->isSyncAction('catalog'),
            '[Test Is Sync Action] Check if return is a boolean'
        );
        $this->assertTrue(
            $this->_syncHelper->isSyncAction('order'),
            '[Test Is Sync Action] Check if return is valid with valid action'
        );
        $this->assertFalse(
            $this->_syncHelper->isSyncAction('plop'),
            '[Test Is Sync Action] Check if return is valid with fake action'
        );
    }

    /**
     * @covers \Lengow\Connector\Helper\Sync::pluginIsBlocked
     */
    public function testPluginIsBlocked()
    {
        $fixture = New Fixture();
        $classMock = $fixture->getFakeClass();
        $configHelperMock = $fixture->mockFunctions($classMock, ['isNewMerchant'], [true]);
        $fixture->setPrivatePropertyValue($this->_syncHelper, ['_configHelper'], [$configHelperMock]);
        $this->assertInternalType(
            'boolean',
            $this->_syncHelper->pluginIsBlocked(),
            '[Test Plugin Is Blocked] Check if return is a boolean'
        );
        $this->assertTrue(
            $this->_syncHelper->pluginIsBlocked(),
            '[Test Plugin Is Blocked] Check if return is valid when merchant is new'
        );
        $configHelperMock2 = $fixture->mockFunctions($classMock, ['isNewMerchant'], [false]);
        $syncHelperMock = $this->getMockBuilder(get_class($this->_syncHelper))
            ->setMethods(['getStatusAccount'])
            ->disableOriginalConstructor()
            ->getMock();
        $syncHelperMock->expects($this->any())->method('getStatusAccount')->willReturnOnConsecutiveCalls(
            ['type' => 'free_trial', 'day' => 12, 'expired' => false],
            ['type' => 'free_trial', 'day' => 0, 'expired' => true],
            ['type' => '', 'day' => 0, 'expired' => false]
        );
        $fixture->setPrivatePropertyValue($syncHelperMock, ['_configHelper'], [$configHelperMock2]);
        $this->assertFalse(
            $syncHelperMock->pluginIsBlocked(),
            '[Test Plugin Is Blocked] Check if return is valid when free trial is not expired'
        );
        $this->assertTrue(
            $syncHelperMock->pluginIsBlocked(),
            '[Test Plugin Is Blocked] Check if return is valid when free trial is expired'
        );
        $this->assertFalse(
            $syncHelperMock->pluginIsBlocked(),
            '[Test Plugin Is Blocked] Check if return is valid when merchant is not in free trial'
        );
    }

    /**
     * @covers \Lengow\Connector\Helper\Sync::getStatusAccount
     */
    public function testGetStatusAccount()
    {
        $fixture = New Fixture();
        $classMock = $fixture->getFakeClass();
        $configHelperMock = $fixture->mockFunctions($classMock, ['isNewMerchant'], [true]);
        $fixture->setPrivatePropertyValue(
            $this->_syncHelper,
            ['_configHelper', 'statusAccount'],
            [$configHelperMock, null]
        );
        $this->assertFalse(
            $this->_syncHelper->getStatusAccount(),
            '[Test Get Status Account] Check if return is false for new merchant'
        );

        $updatedAt = date('Y-m-d H:i:s', time() - 1000);
        $statusAccount = '{"type":"free_trial","day":12,"expired":false}';
        $configHelperMock2 = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['get', 'isNewMerchant'])
            ->disableOriginalConstructor()
            ->getMock();
        $configHelperMock2->expects($this->any())->method('get')->willReturnOnConsecutiveCalls(
            $updatedAt,
            $statusAccount,
            $updatedAt,
            $statusAccount
        );
        $configHelperMock2->expects($this->any())->method('isNewMerchant')->will($this->returnValue(false));
        $fixture->setPrivatePropertyValue(
            $this->_syncHelper,
            ['_configHelper', 'statusAccount'],
            [$configHelperMock2, null]
        );
        $this->assertInternalType(
            'array',
            $this->_syncHelper->getStatusAccount(),
            '[Test Get Status Account] Check if return is a array'
        );
        $fixture->setPrivatePropertyValue($this->_syncHelper, ['statusAccount'], [null]);
        $this->assertEquals(
            json_decode($statusAccount, true),
            $this->_syncHelper->getStatusAccount(),
            '[Test Get Status Account] Check if return is valid with cache'
        );

        $configHelperMock3 = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['get', 'isNewMerchant'])
            ->disableOriginalConstructor()
            ->getMock();
        $configHelperMock3->expects($this->any())->method('get')->willReturnOnConsecutiveCalls(
            $updatedAt,
            $statusAccount,
            null
        );
        $configHelperMock3->expects($this->any())->method('isNewMerchant')->will($this->returnValue(false));
        $connectorMock = $fixture->mockFunctions($classMock, ['queryApi'], [null]);
        $fixture->setPrivatePropertyValue(
            $this->_syncHelper,
            ['_configHelper', '_connector', 'statusAccount'],
            [$configHelperMock3, $connectorMock, null]
        );
        $this->assertEquals(
            json_decode($statusAccount, true),
            $this->_syncHelper->getStatusAccount(true),
            '[Test Get Status Account] Check if return is valid without cache, no API response but status in database'
        );
        $fixture->setPrivatePropertyValue($this->_syncHelper, ['statusAccount'], [null]);
        $this->assertFalse(
            $this->_syncHelper->getStatusAccount(true),
            '[Test Get Status Account] Check if return is valid with no cache, no API and no status in database'
        );

        $apiStatusAccount = '{"isFreeTrial":true,"leftDaysBeforeExpired":12,"isExpired":false}';
        $apiStatusAccount2 = '{"isFreeTrial":true,"leftDaysBeforeExpired":null,"isExpired":true}';
        $apiStatusAccount3 = '{"isFreeTrial":false,"leftDaysBeforeExpired":null,"isExpired":false}';

        $configHelperMock4 = $fixture->mockFunctions($classMock, ['isNewMerchant', 'set'], [false, null]);
        $connectorMock2 = $fixture->mockFunctions($classMock, ['queryApi'], [json_decode($apiStatusAccount)]);
        $fixture->setPrivatePropertyValue(
            $this->_syncHelper,
            ['_configHelper', '_connector', 'statusAccount'],
            [$configHelperMock4, $connectorMock2, null]
        );
        $this->assertEquals(
            ['type' => 'free_trial', 'day' => 12, 'expired' => false],
            $this->_syncHelper->getStatusAccount(true),
            '[Test Get Status Account] Check if return is valid with API response and free trial not expired'
        );
        $connectorMock3 = $fixture->mockFunctions($classMock, ['queryApi'], [json_decode($apiStatusAccount2)]);
        $fixture->setPrivatePropertyValue(
            $this->_syncHelper,
            ['_configHelper', '_connector', 'statusAccount'],
            [$configHelperMock4, $connectorMock3, null]
        );
        $this->assertEquals(
            ['type' => 'free_trial', 'day' => 0, 'expired' => true],
            $this->_syncHelper->getStatusAccount(true),
            '[Test Get Status Account] Check if return is valid with API response and free trial expired'
        );
        $connectorMock4 = $fixture->mockFunctions($classMock, ['queryApi'], [json_decode($apiStatusAccount3)]);
        $fixture->setPrivatePropertyValue(
            $this->_syncHelper,
            ['_configHelper', '_connector', 'statusAccount'],
            [$configHelperMock4, $connectorMock4, null]
        );
        $this->assertEquals(
            ['type' => '', 'day' => 0, 'expired' => false],
            $this->_syncHelper->getStatusAccount(true),
            '[Test Get Status Account] Check if return is valid with API response when account is not a free trial'
        );

        $connectorMock5 = $fixture->mockFunctions($classMock, ['queryApi'], [json_decode($apiStatusAccount2)]);
        $fixture->setPrivatePropertyValue(
            $this->_syncHelper,
            ['_configHelper', '_connector', 'statusAccount'],
            [$configHelperMock4, $connectorMock5, null]
        );
        $this->_syncHelper->getStatusAccount(true);
        $this->assertEquals(
            ['type' => 'free_trial', 'day' => 0, 'expired' => true],
            $this->_syncHelper->getStatusAccount(true),
            '[Test Get Status Account] Check if return is valid with static cache'
        );
    }
}
