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

class PriceTest extends \PHPUnit_Framework_TestCase
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
    public function setUp()
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
        $fixture = New Fixture();
        $fixture->setPrivatePropertyValue(
            $this->_price,
            ['_priceExclTax', '_priceInclTax', '_priceBeforeDiscountExclTax', '_priceBeforeDiscountInclTax'],
            [80, 96, 100, 120]
        );
        $this->assertInternalType(
            'array',
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
        $fixture = New Fixture();
        $fixture->setPrivatePropertyValue(
            $this->_price,
            ['_discountAmount', '_discountPercent', '_discountStartDate', '_discountEndDate'],
            [80, 96, '2017-02-20 00:00:00', '2017-03-20 23:59:59']
        );
        $this->assertInternalType(
            'array',
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
        $fixture = New Fixture();
        $fixture->setPrivatePropertyValue(
            $this->_price,
            [
                '_product',
                '_priceExclTax',
                '_priceInclTax',
                '_priceBeforeDiscountExclTax',
                '_priceBeforeDiscountInclTax',
                '_discountAmount',
                '_discountPercent',
                '_discountStartDate',
                '_discountEndDate',
            ],
            ['plop', 50, 60, 100, 120, 80, 96, '2017-02-20 00:00:00', '2017-03-20 23:59:59']
        );
        $this->_price->clean();
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, '_product'),
            '[Test Clean] Check if _product attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, '_priceExclTax'),
            '[Test Clean] Check if _priceExclTax attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, '_priceInclTax'),
            '[Test Clean] Check if _priceInclTax attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, '_priceBeforeDiscountExclTax'),
            '[Test Clean] Check if _priceBeforeDiscountExclTax attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, '_priceBeforeDiscountInclTax'),
            '[Test Clean] Check if _priceBeforeDiscountInclTax attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, '_discountAmount'),
            '[Test Clean] Check if _discountAmount attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, '_discountPercent'),
            '[Test Clean] Check if _discountPercent attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, '_discountStartDate'),
            '[Test Clean] Check if _discountStartDate attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_price, '_discountEndDate'),
            '[Test Clean] Check if product _discountEndDate is null'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Price::_getAllDiscounts
     */
    public function testGetAllDiscounts()
    {
        $fixture = New Fixture();
        $classMock = $fixture->getFakeClass();
        $fixture->setPrivatePropertyValue($this->_price, ['_priceBeforeDiscountInclTax', '_priceInclTax'], [120, 120]);
        $this->assertInternalType(
            'array',
            $fixture->invokeMethod($this->_price, '_getAllDiscounts'),
            '[Test Get All Discounts] Check if return is a array'
        );
        $this->assertEquals(
            ['discount_amount'  => 0, 'discount_percent' => 0],
            $fixture->invokeMethod($this->_price, '_getAllDiscounts'),
            '[Test Get All Discounts] Check if return is valid without product discount'
        );

        $fixture->setPrivatePropertyValue($this->_price, ['_priceBeforeDiscountInclTax', '_priceInclTax'], [60, 120]);
        $this->assertEquals(
            ['discount_amount'  => 0, 'discount_percent' => 0],
            $fixture->invokeMethod($this->_price, '_getAllDiscounts'),
            '[Test Get All Discounts] Check if return is valid when price before discount is less than final price'
        );

        $priceCurrencyMock = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['round'])
            ->disableOriginalConstructor()
            ->getMock();
        $priceCurrencyMock->expects($this->any())->method('round')->willReturnOnConsecutiveCalls(60, 50);
        $fixture->setPrivatePropertyValue(
            $this->_price,
            ['_priceBeforeDiscountInclTax', '_priceInclTax', '_priceCurrency'],
            [120, 60, $priceCurrencyMock]
        );
        $this->assertEquals(
            ['discount_amount'  => 60, 'discount_percent' => 50],
            $fixture->invokeMethod($this->_price, '_getAllDiscounts'),
            '[Test Get All Discounts] Check if return is valid with product discount'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Price::_getAllDiscountDates
     */
    public function testGetAllDiscountDates()
    {
        $fixture = New Fixture();
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
            ['_product', '_catalogueRule', '_dateTime', '_store'],
            [$productMock, $catalogueRuleMock, $dateTimeMock, $storeMock]
        );
        $this->assertInternalType(
            'array',
            $fixture->invokeMethod($this->_price, '_getAllDiscountDates'),
            '[Test Get All Discounts] Check if return is a array'
        );
        $this->assertEquals(
            ['discount_start_date'  => '', 'discount_end_date' => ''],
            $fixture->invokeMethod($this->_price, '_getAllDiscountDates'),
            '[Test Get All Discounts] Check if return is valid without product discount'
        );

        $productMock2 = $fixture->mockFunctions(
            $classMock,
            ['getSpecialFromDate', 'getSpecialToDate', 'getId'],
            ['2017-02-20 00:00:00', '2017-03-20 23:59:59', 10]
        );
        $fixture->setPrivatePropertyValue($this->_price, ['_product'], [$productMock2]);
        $this->assertEquals(
            ['discount_start_date'  => '2017-02-20 00:00:00', 'discount_end_date' => '2017-03-20 23:59:59'],
            $fixture->invokeMethod($this->_price, '_getAllDiscountDates'),
            '[Test Get All Discounts] Check return with special dates but no catalogue rules'
        );

        $resourceMock2 = $fixture->mockFunctions(
            $classMock,
            ['getRulesFromProduct'],
            [
                [
                    ['from_time' => '1487545200', 'to_time' => '1490050799'],
                    ['from_time' => '1488322800', 'to_time' => '1490997599'],
                ],
            ]
        );
        $catalogueRuleMock2 = $fixture->mockFunctions($classMock, ['getResource'], [$resourceMock2]);
        $dateTimeMock2 = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['gmtTimestamp', 'date'])
            ->disableOriginalConstructor()
            ->getMock();
        $dateTimeMock2->expects($this->any())->method('gmtTimestamp')->will($this->returnValue('1488322800'));
        $dateTimeMock2->expects($this->any())->method('date')->willReturnOnConsecutiveCalls(
            '2017-03-01 00:00:00',
            '2017-03-31 23:59:59'
        );
        $fixture->setPrivatePropertyValue(
            $this->_price,
            ['_catalogueRule', '_dateTime'],
            [$catalogueRuleMock2, $dateTimeMock2]
        );
        $this->assertEquals(
            ['discount_start_date'  => '2017-03-01 00:00:00', 'discount_end_date' => '2017-03-31 23:59:59'],
            $fixture->invokeMethod($this->_price, '_getAllDiscountDates'),
            '[Test Get All Discounts] Check return with special dates but no catalogue rules'
        );
    }
}
