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

namespace Lengow\Connector\Test\Unit\Model\Export;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Lengow\Connector\Model\Export\Price;
use Lengow\Connector\Test\Unit\Fixture;

class PriceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Lengow\Connector\Model\Export\Price
     */
    protected $_price;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp() : void
    {
        $objectManager = new ObjectManager($this);
        $this->_price = $objectManager->getObject(Price::class);
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Price::class,
            $this->_price,
            '[Test Class Instantiation] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Price::getPrices
     */
    public function testGetPrices()
    {
        $fixture = new Fixture();
        $fixture->setPrivatePropertyValue(
            $this->_price,
            ['priceExclTax', 'priceInclTax', 'priceBeforeDiscountExclTax', 'priceBeforeDiscountInclTax'],
            [80, 96, 100, 120]
        );
        $this->assertIsArray(
            $this->_price->getPrices(),
            '[Test Get Prices] Check if return is a array'
        );

        $this->assertEquals(
            [
                'price_excl_tax'                 => 80,
                'price_incl_tax'                 => 96,
                'price_before_discount_excl_tax' => 100,
                'price_before_discount_incl_tax' => 120,
            ],
            $this->_price->getPrices(),
            '[Test Get Prices] Check if return is valid'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Price::getDiscounts
     */
    public function testGetDiscounts()
    {
        $fixture = new Fixture();
        $fixture->setPrivatePropertyValue(
            $this->_price,
            ['discountAmount', 'discountPercent', 'discountStartDate', 'discountEndDate'],
            [80, 96, '2017-02-20 00:00:00', '2017-03-20 23:59:59']
        );
        $this->assertIsArray(
            $this->_price->getDiscounts(),
            '[Test Get Discounts] Check if return is a array'
        );
        $this->assertEquals(
            [
                'discount_amount'     => 80,
                'discount_percent'    => 96,
                'discount_start_date' => '2017-02-20 00:00:00',
                'discount_end_date'   => '2017-03-20 23:59:59',
            ],
            $this->_price->getDiscounts(),
            '[Test Get Discounts] Check if return is valid'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Price::clean
     */
    public function testClean()
    {
        $fixture = new Fixture();
        $fixture->setPrivatePropertyValue(
            $this->_price,
            [
                'product',
                'priceExclTax',
                'priceInclTax',
                'priceBeforeDiscountExclTax',
                'priceBeforeDiscountInclTax',
                'discountAmount',
                'discountPercent',
                'discountStartDate',
                'discountEndDate',
            ],
            ['plop', 50, 60, 100, 120, 80, 96, '2017-02-20 00:00:00', '2017-03-20 23:59:59']
        );
        $this->_price->clean();
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, 'product'),
            '[Test Clean] Check if product attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, 'priceExclTax'),
            '[Test Clean] Check if priceExclTax attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, 'priceInclTax'),
            '[Test Clean] Check if priceInclTax attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, 'priceBeforeDiscountExclTax'),
            '[Test Clean] Check if priceBeforeDiscountExclTax attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, 'priceBeforeDiscountInclTax'),
            '[Test Clean] Check if priceBeforeDiscountInclTax attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, 'discountAmount'),
            '[Test Clean] Check if discountAmount attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, 'discountPercent'),
            '[Test Clean] Check if discountPercent attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, 'discountStartDate'),
            '[Test Clean] Check if discountStartDate attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, 'discountEndDate'),
            '[Test Clean] Check if product discountEndDate is null'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Price::getAllDiscounts
     */
    public function testGetAllDiscounts()
    {
        $fixture = new Fixture();
        $classMock = $fixture->getFakeClass();
        $fixture->setPrivatePropertyValue($this->_price, ['priceBeforeDiscountInclTax', 'priceInclTax'], [120, 120]);
        $this->assertIsArray(
            $fixture->invokeMethod($this->_price, 'getAllDiscounts'),
            '[Test Get All Discounts] Check if return is a array'
        );
        $this->assertEquals(
            ['discount_amount'  => 0, 'discount_percent' => 0],
            $fixture->invokeMethod($this->_price, 'getAllDiscounts'),
            '[Test Get All Discounts] Check if return is valid without product discount'
        );

        $fixture->setPrivatePropertyValue($this->_price, ['priceBeforeDiscountInclTax', 'priceInclTax'], [60, 120]);
        $this->assertEquals(
            ['discount_amount'  => 0, 'discount_percent' => 0],
            $fixture->invokeMethod($this->_price, 'getAllDiscounts'),
            '[Test Get All Discounts] Check if return is valid when price before discount is less than final price'
        );

        $priceCurrencyMock = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['round'])
            ->disableOriginalConstructor()
            ->getMock();
        $priceCurrencyMock->expects($this->any())->method('round')->willReturnOnConsecutiveCalls(60, 50);
        $fixture->setPrivatePropertyValue(
            $this->_price,
            ['priceBeforeDiscountInclTax', 'priceInclTax', 'priceCurrency'],
            [120, 60, $priceCurrencyMock]
        );
        $this->assertEquals(
            ['discount_amount'  => 60, 'discount_percent' => 50],
            $fixture->invokeMethod($this->_price, 'getAllDiscounts'),
            '[Test Get All Discounts] Check if return is valid with product discount'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Price::getAllDiscountDates
     */
    public function testGetAllDiscountDates()
    {
        $fixture = new Fixture();
        $classMock = $fixture->getFakeClass();
        $productMock = $fixture->mockFunctions(
            $classMock,
            ['getSpecialFromDate', 'getSpecialToDate', 'getId'],
            [null, null, 10]
        );
        $resourceMock = $fixture->mockFunctions($classMock, ['getRulesFromProduct'], [[]]);
        $catalogueRuleMock = $fixture->mockFunctions($classMock, ['getResource'], [$resourceMock]);
        $storeMock = $fixture->mockFunctions($classMock, ['getWebsiteId'], [1]);
        $dateTimeMock = $fixture->mockFunctions($classMock, ['gmtTimestamp'], ['1488322800']);
        $fixture->setPrivatePropertyValue(
            $this->_price,
            ['product', 'catalogueRule', 'dateTime', 'store'],
            [$productMock, $catalogueRuleMock, $dateTimeMock, $storeMock]
        );
        $this->assertIsArray(
            $fixture->invokeMethod($this->_price, 'getAllDiscountDates'),
            '[Test Get All Discounts] Check if return is a array'
        );
        $this->assertEquals(
            ['discount_start_date'  => '', 'discount_end_date' => ''],
            $fixture->invokeMethod($this->_price, 'getAllDiscountDates'),
            '[Test Get All Discounts] Check if return is valid without product discount'
        );

        $productMock2 = $fixture->mockFunctions(
            $classMock,
            ['getSpecialFromDate', 'getSpecialToDate', 'getId'],
            ['2017-02-20 00:00:00', '2017-03-20 23:59:59', 10]
        );
        $fixture->setPrivatePropertyValue($this->_price, ['product'], [$productMock2]);

        $this->assertEquals(
            ['discount_start_date'  => '2017-02-20 00:00:00', 'discount_end_date' => '2017-03-20 23:59:59'],
            $fixture->invokeMethod($this->_price, 'getAllDiscountDates'),
            '[Test Get All Discounts] Check return with special dates but no catalogue rules'
        );
    }
}
