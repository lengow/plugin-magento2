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
     * @covers \Lengow\Connector\Helper\Sync::getStatistic
     */
    public function testGetStatistic()
    {
        $fixture = New Fixture();
        $classMock = $fixture->getFakeClass();
        $updatedAt = date('Y-m-d H:i:s', time() - 1000);
        $stats = '{"total_order":"445\u00a0761,17\u00a0\u20ac","nb_order":1231,"currency":"GBP","available":true}';
        $configHelperMock = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
        $configHelperMock->expects($this->any())->method('get')->willReturnOnConsecutiveCalls(
            $updatedAt,
            $stats,
            $updatedAt,
            $stats
        );
        $fixture->setPrivatePropertyValue($this->_syncHelper, ['_configHelper'], [$configHelperMock]);
        $this->assertInternalType(
            'array',
            $this->_syncHelper->getStatistic(),
            '[Test Get Statistic] Check if return is a array'
        );
        $this->assertEquals(
            json_decode($stats, true),
            $this->_syncHelper->getStatistic(),
            '[Test Get Statistic] Check if return is valid with cache'
        );

        $configHelperMock2 = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['get', 'getAllAvailableCurrencyCodes'])
            ->disableOriginalConstructor()
            ->getMock();
        $configHelperMock2->expects($this->any())->method('get')->willReturnOnConsecutiveCalls(
            $updatedAt,
            $stats,
            null
        );
        $configHelperMock2->expects($this->any())->method('getAllAvailableCurrencyCodes')->will($this->returnValue([]));
        $connectorMock = $fixture->mockFunctions($classMock, ['queryApi'], [null]);
        $fixture->setPrivatePropertyValue(
            $this->_syncHelper,
            ['_configHelper', '_connector'],
            [$configHelperMock2, $connectorMock]
        );
        $this->assertEquals(
            json_decode($stats, true),
            $this->_syncHelper->getStatistic(true),
            '[Test Get Statistic] Check if return is valid without cache, no API response but stats in database'
        );
        $this->assertEquals(
            ['total_order' => 0, 'nb_order' => 0, 'currency' => '', 'available' => false],
            $this->_syncHelper->getStatistic(true),
            '[Test Get Statistic] Check if return is valid without cache, no API response and no stats in database'
        );

        $apiStats = '{"currency":{"iso_a3":"EUR"},"level0":[{"transactions":1231.0,"revenue":445761.17}]}';
        $apiStats2 = '{"currency":{"iso_a3":"EUR"},"level0":[{"transactions":null,"revenue":null}]}';

        $configHelperMock3 = $fixture->mockFunctions($classMock, ['getAllAvailableCurrencyCodes', 'set'], [[], null]);
        $connectorMock = $fixture->mockFunctions($classMock, ['queryApi'], [json_decode($apiStats)]);
        $fixture->setPrivatePropertyValue(
            $this->_syncHelper,
            ['_configHelper', '_connector'],
            [$configHelperMock3, $connectorMock]
        );
        $this->assertEquals(
            ['total_order' => '445 761,17', 'nb_order' => 1231, 'currency' => 'EUR', 'available' => true],
            $this->_syncHelper->getStatistic(true),
            '[Test Get Statistic] Check if return is valid with API response but no specific currency'
        );
        $connectorMock2 = $fixture->mockFunctions($classMock, ['queryApi'], [json_decode($apiStats2)]);
        $fixture->setPrivatePropertyValue(
            $this->_syncHelper,
            ['_configHelper', '_connector'],
            [$configHelperMock3, $connectorMock2]
        );
        $this->assertEquals(
            ['total_order' => '0,00', 'nb_order' => 0, 'currency' => 'EUR', 'available' => false],
            $this->_syncHelper->getStatistic(true),
            '[Test Get Statistic] Check if return is valid with API response but no specific currency and no stats'
        );

        $configHelperMock4 = $fixture->mockFunctions(
            $classMock,
            ['getAllAvailableCurrencyCodes', 'set'],
            [['EUR'], null]
        );
        $priceCurrencyMock = $fixture->mockFunctions($classMock, ['format'], ['445 761,17€']);
        $fixture->setPrivatePropertyValue(
            $this->_syncHelper,
            ['_configHelper', '_connector', '_priceCurrency'],
            [$configHelperMock4, $connectorMock, $priceCurrencyMock]
        );
        $this->assertEquals(
            ['total_order' => '445 761,17€', 'nb_order' => 1231, 'currency' => 'EUR', 'available' => true],
            $this->_syncHelper->getStatistic(true),
            '[Test Get Statistic] Check if return is valid with API response and specific currency'
        );
        $priceCurrencyMock2= $fixture->mockFunctions($classMock, ['format'], ['0,00€']);
        $fixture->setPrivatePropertyValue(
            $this->_syncHelper,
            ['_configHelper', '_connector', '_priceCurrency'],
            [$configHelperMock4, $connectorMock2, $priceCurrencyMock2]
        );
        $this->assertEquals(
            ['total_order' => '0,00€', 'nb_order' => 0, 'currency' => 'EUR', 'available' => false],
            $this->_syncHelper->getStatistic(true),
            '[Test Get Statistic] Check if return is valid with API response and specific currency and no stats'
        );
    }
}
