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

class ImportTest extends \PHPUnit\Framework\TestCase
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
    public function setUp() : void
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
        $fixture = new Fixture();
        $classMock = $fixture->getFakeClass();

        $configHelperMock = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
        $configHelperMock->expects($this->any())->method('get')->willReturnOnConsecutiveCalls(
            '1507715696',
            '',
            '',
            '1507715696',
            '',
            '',
            '1507715696',
            '1507715697',
            '1507715697',
            '1507715696'
        );
        $fixture->setPrivatePropertyValue($this->_importHelper, ['configHelper'], [$configHelperMock]);

        $this->assertEquals(
            ['type' => 'cron', 'timestamp' => '1507715696'],
            $this->_importHelper->getLastImport(),
            '[Test Get Last Import] Check if return last import date 1'
        );
        $this->assertEquals(
            ['type' => 'manual', 'timestamp' => '1507715696'],
            $this->_importHelper->getLastImport(),
            '[Test Get Last Import] Check if return last import date 2'
        );
        $this->assertEquals(
            ['type' => 'none', 'timestamp' => 'none'],
            $this->_importHelper->getLastImport(),
            '[Test Get Last Import] Check if return last import date 3'
        );
        $this->assertEquals(
            ['type' => 'manual', 'timestamp' => '1507715697'],
            $this->_importHelper->getLastImport(),
            '[Test Get Last Import] Check if return last import date 4'
        );
        $this->assertEquals(
            ['type' => 'cron', 'timestamp' => '1507715697'],
            $this->_importHelper->getLastImport(),
            '[Test Get Last Import] Check if return last import date 5'
        );
    }

    /**
     * @covers \Lengow\Connector\Helper\Import::isInProcess()
     */
    public function testIsInProcess()
    {
        $fixture = new Fixture();
        $classMock = $fixture->getFakeClass();

        $configHelperMock = $this->getMockBuilder(get_class($classMock))
            ->addMethods(['get','set'])
            ->disableOriginalConstructor()
            ->getMock();


        $fixture->mockFunctions($this->_importHelper, ['setImportEnd'], [true]);
        $configHelperMock->expects($this->any())->method('get')->willReturnOnConsecutiveCalls(
            '1507715696',
            '',
            0,
            time() - 10
        );

        $fixture->setPrivatePropertyValue($this->_importHelper, ['configHelper'], [$configHelperMock]);

        $this->assertEquals(
            false,
            $this->_importHelper->isInProcess(),
            '[Test Get Last Import] Check if return import is in process or not 1'
        );

        $this->assertEquals(
            false,
            $this->_importHelper->isInProcess(),
            '[Test Get Last Import] Check if return import is in process or not 2'
        );

        $this->assertEquals(
            false,
            $this->_importHelper->isInProcess(),
            '[Test Get Last Import] Check if return import is in process or not 3'
        );

        $this->assertEquals(
            true,
            $this->_importHelper->isInProcess(),
            '[Test Get Last Import] Check if return import is in process or not 4'
        );
    }
}
