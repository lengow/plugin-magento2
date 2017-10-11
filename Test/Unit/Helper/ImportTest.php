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

use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Magento\Framework\App\Helper\Context;
use Lengow\Connector\Test\Unit\Fixture;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ImportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Lengow\Connector\Helper\Import
     */
    protected $_importHelper;

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
        $this->_importHelper = $objectManager->getObject(ImportHelper::class);
        $this->_syncHelper = $objectManager->getObject(SyncHelper::class);
        $this->_configHelper = $objectManager->getObject(ConfigHelper::class);
        $this->_context = $objectManager->getObject(Context::class);
    }

    public function testClassInstance()
    {
        $this->assertInstanceOf(
            ImportHelper::class,
            $this->_importHelper,
            '[Test Class Instance] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Helper\Import::getLastImport()
     */
    public function testGetLastImport()
    {
        $fixture = New Fixture();
//        $classMock = $fixture->getFakeClass();
        $configHelperMock = $fixture->mockFunctions(
            $this->_configHelper,
            ['last_import_cron', 'last_import_manual'],
            ['1507715696', '1507711756']
        );
//        $configHelperMock = $fixture->mockFunctions($classMock, ['isNewMerchant'], [true]);
//        $fixture->setPrivatePropertyValue($this->_syncHelper, ['_configHelper'], [$configHelperMock]);
        $this->assertEquals(
            ['type' => 'none', 'timestamp' => 'none'],
            $this->_importHelper->getLastImport(),
            '[Test Get Last Import] Check if return last import date'
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
        $fixture->setPrivatePropertyValue($this->_syncHelper, ['_configHelper'], [$configHelperMock]);
        $this->assertFalse(
            $this->_syncHelper->getStatusAccount(),
            '[Test Get Status Account] Check if return is false for new merchant'
        );
        $updatedAt = date('Y-m-d H:i:s', time() - 1000);
        $statusAccount = '{"type":"free_trial","day":12,"expired":false}';
        $configHelperMock2 = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
        $configHelperMock2->expects($this->any())->method('get')->willReturnOnConsecutiveCalls(
            $updatedAt,
            $statusAccount,
            $updatedAt,
            $statusAccount
        );
        $configHelperMock2->expects($this->any())->method('isNewMerchant')->will($this->returnValue(false));
        $fixture->setPrivatePropertyValue($this->_syncHelper, ['_configHelper'], [$configHelperMock2]);
        $this->assertInternalType(
            'array',
            $this->_syncHelper->getStatistic(),
            '[Test Get Status Account] Check if return is a array'
        );
        $this->assertEquals(
            json_decode($statusAccount, true),
            $this->_syncHelper->getStatistic(),
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
            ['_configHelper', '_connector'],
            [$configHelperMock3, $connectorMock]
        );
        $this->assertEquals(
            json_decode($statusAccount, true),
            $this->_syncHelper->getStatusAccount(true),
            '[Test Get Status Account] Check if return is valid without cache, no API response but status in database'
        );
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
            ['_configHelper', '_connector'],
            [$configHelperMock4, $connectorMock2]
        );
        $this->assertEquals(
            ['type' => 'free_trial', 'day' => 12, 'expired' => false],
            $this->_syncHelper->getStatusAccount(true),
            '[Test Get Status Account] Check if return is valid with API response and free trial not expired'
        );
        $connectorMock3 = $fixture->mockFunctions($classMock, ['queryApi'], [json_decode($apiStatusAccount2)]);
        $fixture->setPrivatePropertyValue(
            $this->_syncHelper,
            ['_configHelper', '_connector'],
            [$configHelperMock4, $connectorMock3]
        );
        $this->assertEquals(
            ['type' => 'free_trial', 'day' => 0, 'expired' => true],
            $this->_syncHelper->getStatusAccount(true),
            '[Test Get Status Account] Check if return is valid with API response and free trial expired'
        );
        $connectorMock4 = $fixture->mockFunctions($classMock, ['queryApi'], [json_decode($apiStatusAccount3)]);
        $fixture->setPrivatePropertyValue(
            $this->_syncHelper,
            ['_configHelper', '_connector'],
            [$configHelperMock4, $connectorMock4]
        );
        $this->assertEquals(
            ['type' => '', 'day' => 0, 'expired' => false],
            $this->_syncHelper->getStatusAccount(true),
            '[Test Get Status Account] Check if return is valid with API response when account is not a free trial'
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
