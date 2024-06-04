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

use Lengow\Connector\Model\Export;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use Lengow\Connector\Test\Unit\Fixture;

class ExportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Lengow\Connector\Model\Export
     */
    protected $_export;

    /**
     * @var \Lengow\Connector\Helper\Config
     */
    protected $_configHelper;

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $_store;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp() : void
    {
        $objectManager = new ObjectManager($this);
        $this->_export = $objectManager->getObject(Export::class);
        $this->_configHelper = $objectManager->getObject(ConfigHelper::class);
        $this->_store = $objectManager->getObject(Store::class);
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Export::class,
            $this->_export,
            '[Test Class Instantiation] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export::getFields
     */
    public function testGetFields()
    {
        $fixture = new Fixture();
        $fixture->setPrivatePropertyValue($this->_export, ['storeId'], [1]);
        $selectedAttributes = ['meta_description', 'meta_keyword', 'meta_title', 'minimal_price', 'size'];
        $defaultFields = $fixture->getPrivatePropertyValue($this->_export, 'defaultFields');
        $fieldsMock = [];
        foreach ($defaultFields as $key => $field) {
            $fieldsMock[] = $key;
        }
        $fieldsMock = array_merge($fieldsMock, $selectedAttributes);
        $configHelperMock = $fixture->mockFunctions(
            $this->_configHelper,
            ['getSelectedAttributes', 'moduleIsEnabled'],
            [$selectedAttributes, true]
        );
        $fixture->setPrivatePropertyValue($this->_export, ['configHelper'], [$configHelperMock]);
        $this->assertIsArray(
            $fixture->invokeMethod($this->_export, 'getFields'),
            '[Test Get Fields] Check if return is a array'
        );
        $this->assertEquals(
            $fieldsMock,
            $fixture->invokeMethod($this->_export, 'getFields'),
            '[Test Get Fields] Check if return is valid'
        );

    }

    /**
     * @covers \Lengow\Connector\Model\Export::setFormat
     */
    public function testSetFormat()
    {
        $fixture = new Fixture();
        $this->assertEquals(
            'csv',
            $fixture->invokeMethod($this->_export, 'setFormat', ['csv']),
            '[Test Set Format] set csv format'
        );
        $this->assertEquals(
            'json',
            $fixture->invokeMethod($this->_export, 'setFormat', ['json']),
            '[Test Set Format] set json format'
        );
        $this->assertEquals(
            'xml',
            $fixture->invokeMethod($this->_export, 'setFormat', ['xml']),
            '[Test Set Format] set xml format'
        );
        $this->assertEquals(
            'yaml',
            $fixture->invokeMethod($this->_export, 'setFormat', ['yaml']),
            '[Test Set Format] set yaml format'
        );
        $this->assertEquals(
            'csv',
            $fixture->invokeMethod($this->_export, 'setFormat', ['plop']),
            '[Test Set Format] set invalid format'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export::setProductIds
     */
    public function testSetProductIds()
    {
        $fixture = new Fixture();
        $this->assertEquals(
            [],
            $fixture->invokeMethod($this->_export, 'setProductIds', [false]),
            '[Test Set Product Ids] set no product ids'
        );
        $this->assertEquals(
            [123, 236, 254],
            $fixture->invokeMethod($this->_export, 'setProductIds', ['123,236,254']),
            '[Test Set Product Ids] set product ids'
        );
        $this->assertEquals(
            [123, 254],
            $fixture->invokeMethod($this->_export, 'setProductIds', ['123,plop,254']),
            '[Test Set Product Ids] set product ids with special characters'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export::setProductTypes
     */
    public function testSetProductTypes()
    {
        $fixture = new Fixture();
        $configHelperMock = $fixture->mockFunctions(
            $this->_configHelper,
            ['get'],
            ['configurable,simple,downloadable,grouped,virtual']
        );
        $fixture->setPrivatePropertyValue($this->_export, ['configHelper', 'storeId'], [$configHelperMock, 1]);
        $this->assertEquals(
            ['configurable', 'simple', 'downloadable', 'grouped', 'virtual'],
            $fixture->invokeMethod($this->_export, 'setProductTypes', [false]),
            '[Test Set Product Types] get default product type when product types parameter is not set'
        );
        $this->assertEquals(
            ['configurable', 'simple'],
            $fixture->invokeMethod($this->_export, 'setProductTypes', ['configurable,simple']),
            '[Test Set Product Types] get product types when product types parameter is set'
        );
        $this->assertEquals(
            ['configurable', 'simple'],
            $fixture->invokeMethod($this->_export, 'setProductTypes', ['configurable,plop,simple']),
            '[Test Set Product Types] get product types when product types parameter is set with special character'
        );

    }

    /**
     * @covers \Lengow\Connector\Model\Export::setLogOutput
     */
    public function testSetLogOutput()
    {
        $fixture = new Fixture();
        $fixture->setPrivatePropertyValue($this->_export, ['stream'], [false]);
        $this->assertTrue(
            $fixture->invokeMethod($this->_export, 'setLogOutput', [true]),
            '[Test Set Log Output] enable log output with file export is enabled'
        );
        $this->assertNotTrue(
            $fixture->invokeMethod($this->_export, 'setLogOutput', [false]),
            '[Test Set Log Output] disable log output with file export is enabled'
        );
        $fixture->setPrivatePropertyValue($this->_export, ['stream'], [true]);
        $this->assertNotTrue(
            $fixture->invokeMethod($this->_export, 'setLogOutput', [true]),
            '[Test Set Log Output] enable log output with file export is disabled'
        );
        $this->assertNotTrue(
            $fixture->invokeMethod($this->_export, 'setLogOutput', [false]),
            '[Test Set Log Output] disable log output with file export is disabled'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export::setCurrency
     */
    public function testSetCurrency()
    {
        $fixture = new Fixture();
        $storeMock = $fixture->mockFunctions(
            $this->_store,
            ['getAvailableCurrencyCodes', 'getCurrentCurrencyCode'],
            [['EUR', 'USD', 'GPB'], 'EUR']
        );
        $fixture->setPrivatePropertyValue($this->_export, ['store'], [$storeMock]);
        $this->assertEquals(
            'EUR',
            $fixture->invokeMethod($this->_export, 'setCurrency', [false]),
            '[Test Set Currency] set currency when parameter is not set'
        );
        $this->assertEquals(
            'GPB',
            $fixture->invokeMethod($this->_export, 'setCurrency', ['GPB']),
            '[Test Set Currency] set currency when parameter is set with a authorized value'
        );
        $this->assertEquals(
            'EUR',
            $fixture->invokeMethod($this->_export, 'setCurrency', ['JPY']),
            '[Test Set Currency] set currency when parameter is set with a not authorized value'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export::setType
     */
    public function testSetType()
    {
        $fixture = new Fixture();
        $this->assertEquals(
            'manual',
            $fixture->invokeMethod($this->_export, 'setType', ['manual']),
            '[Test Set Type] is a manual export'
        );
        $this->assertEquals(
            'cron',
            $fixture->invokeMethod($this->_export, 'setType', ['cron']),
            '[Test Set Type] is a cron export'
        );
        $this->assertEquals(
            'magento cron',
            $fixture->invokeMethod($this->_export, 'setType', ['magento cron']),
            '[Test Set Type] is a magento cron export'
        );
        $fixture->setPrivatePropertyValue($this->_export, ['updateExportDate'], [false]);
        $this->assertEquals(
            'manual',
            $fixture->invokeMethod($this->_export, 'setType', [false]),
            '[Test Set Log Output] if type and update export date is not set (manual export)'
        );
        $fixture->setPrivatePropertyValue($this->_export, ['updateExportDate'], [true]);
        $this->assertEquals(
            'cron',
            $fixture->invokeMethod($this->_export, 'setType', [false]),
            '[Test Set Log Output] if type is not set but update export date is set (cron export)'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export::getProductModulo
     */
    public function testGetProductModulo()
    {
        $fixture = new Fixture();
        $this->assertIsInt(
            $fixture->invokeMethod($this->_export, 'getProductModulo', [1000]),
            '[Test Get Product Modulo] Check if return is a integer value'
        );
        $this->assertEquals(
            100,
            $fixture->invokeMethod($this->_export, 'getProductModulo', [1000]),
            '[Test Get Product Modulo] Check if return is valid'
        );
        $this->assertEquals(
            50,
            $fixture->invokeMethod($this->_export, 'getProductModulo', [400]),
            '[Test Get Product Modulo] Check if return is valid when modulo 50'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export::getMaxCharacterSize
     */
    public function testGetMaxCharacterSize()
    {
        $fixture = new Fixture();
        $fields = ['id', 'name', 'child_name', 'active', 'price_before_discount_excl_tax', 'shipping_method', 'type'];
        $this->assertIsInt(
            $fixture->invokeMethod($this->_export, 'getMaxCharacterSize', [$fields]),
            '[Test Get Max Character Size] Check if return is a integer value'
        );
        $this->assertEquals(
            30,
            $fixture->invokeMethod($this->_export, 'getMaxCharacterSize', [$fields]),
            '[Test Get Max Character Size] Check if return is valid'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export::microtimeFloat
     */
    public function testMicrotimeFloat()
    {
        $fixture = new Fixture();
        $this->assertIsFloat(
            $fixture->invokeMethod($this->_export, 'microtimeFloat'),
            '[Test Microtime Float] Check if return is a float value'
        );
    }
}
