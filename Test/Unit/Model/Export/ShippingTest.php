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
use Lengow\Connector\Model\Export\Shipping;
use Lengow\Connector\Test\Unit\Fixture;

class ShippingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Lengow\Connector\Model\Export\Shipping
     */
    protected $_shipping;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_shipping = $objectManager->getObject(Shipping::class);
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Shipping::class,
            $this->_shipping,
            '[Test Class Instantiation] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Shipping::getShippingMethod
     */
    public function testGetShippingMethod()
    {
        $fixture = new Fixture();
        $fixture->setPrivatePropertyValue($this->_shipping, ['_shippingMethod'], ['ups']);
        $this->assertInternalType(
            'string',
            $this->_shipping->getShippingMethod(),
            '[Test Get Variation List] Check if return is a string'
        );
        $this->assertEquals(
            'ups',
            $this->_shipping->getShippingMethod(),
            '[Test Get Shipping Cost] Check if return is valid'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Shipping::getShippingCost
     */
    public function testGetShippingCost()
    {
        $fixture = new Fixture();
        $fixture->setPrivatePropertyValue($this->_shipping, ['_shippingCost'], [4.99]);
        $this->assertInternalType(
            'float',
            $this->_shipping->getShippingCost(),
            '[Test Get Shipping Cost] Check if return is a float'
        );
        $this->assertEquals(
            4.99,
            $this->_shipping->getShippingCost(),
            '[Test Get Shipping Cost] Check if return is valid'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Shipping::clean
     */
    public function testClean()
    {
        $fixture = new Fixture();
        $fixture->setPrivatePropertyValue($this->_shipping, ['_product', '_shippingCost'], ['product', 5]);
        $this->_shipping->clean();
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_shipping, '_product'),
            '[Test Clean] Check if _product attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_shipping, '_shippingCost'),
            '[Test Clean] Check if _product attribute is null'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Shipping::_getShippingData
     */
    public function testGetShippingData()
    {
        $fixture = new Fixture();
        $classMock = $fixture->getFakeClass();
        $storeMock = $fixture->mockFunctions($classMock, ['getId'], [1]);
        $configHelperMock = $fixture->mockFunctions($classMock, ['get'], [null]);
        $fixture->setPrivatePropertyValue(
            $this->_shipping,
            ['_store', '_configHelper'],
            [$storeMock, $configHelperMock]
        );
        $this->assertInternalType(
            'array',
            $fixture->invokeMethod($this->_shipping, '_getShippingData'),
            '[Test Get Shipping Data] Check if return is a array'
        );
        $this->assertEquals(
            [],
            $fixture->invokeMethod($this->_shipping, '_getShippingData'),
            '[Test Get Shipping Data] Check return when shipping method is null'
        );
        $configHelperMock2 = $fixture->mockFunctions($classMock, ['get'], ['ups_flatrate']);
        $carrierMock = $fixture->mockFunctions($classMock, ['getCarrierCode', 'isFixed'], ['ups', true]);
        $carrierFactoryMock = $fixture->mockFunctions($classMock, ['get'], [$carrierMock]);
        $fixture->setPrivatePropertyValue(
            $this->_shipping,
            ['_configHelper', '_carrierFactory'],
            [$configHelperMock2, $carrierFactoryMock]
        );
        $this->assertEquals(
            [
                'shipping_carrier'  => 'ups',
                'shipping_is_fixed' => true,
                'shipping_method'   => 'Flatrate',
            ],
            $fixture->invokeMethod($this->_shipping, '_getShippingData'),
            '[Test Get Shipping Data] Check return when carrier is fixed'
        );
        $carrierMock2 = $fixture->mockFunctions($classMock, ['getCarrierCode', 'isFixed'], ['ups', false]);
        $carrierFactoryMock2 = $fixture->mockFunctions($classMock, ['get'], [$carrierMock2]);
        $fixture->setPrivatePropertyValue($this->_shipping, ['_carrierFactory'], [ $carrierFactoryMock2]);
        $this->assertEquals(
            [
                'shipping_carrier'  => 'ups',
                'shipping_is_fixed' => false,
                'shipping_method'   => 'Flatrate',
            ],
            $fixture->invokeMethod($this->_shipping, '_getShippingData'),
            '[Test Get Shipping Data] Check return when carrier is not fixed'
        );
        $carrierFactoryMock2 = $fixture->mockFunctions($classMock, ['get'], [null]);
        $fixture->setPrivatePropertyValue($this->_shipping, ['_carrierFactory'], [$carrierFactoryMock2]);
        $this->assertEquals(
            [
                'shipping_carrier'  => '',
                'shipping_is_fixed' => '',
                'shipping_method'   => 'Flatrate',
            ],
            $fixture->invokeMethod($this->_shipping, '_getShippingData'),
            '[Test Get Shipping Data] Check return when carrier is null'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Shipping::_getProductShippingCost
     */
    public function testGetProductShippingCost()
    {
        $fixture = new Fixture();
        $classMock = $fixture->getFakeClass();
        $carrierRateMock = $fixture->mockFunctions($classMock, ['getResult'], [null]);
        $magentoShippingMock = $fixture->mockFunctions($classMock, ['collectCarrierRates'], [$carrierRateMock]);
        $shippingMock = $fixture->mockFunctions($this->_shipping, ['_getShippingRateRequest'], [true]);
        $fixture->setPrivatePropertyValue($shippingMock, ['_magentoShipping'], [$magentoShippingMock]);
        $this->assertFalse(
            $fixture->invokeMethod($shippingMock, '_getProductShippingCost'),
            '[Test Get Product Shipping Cost] Check return when carrier rates is null'
        );
        $resultMock = $fixture->mockFunctions($classMock, ['getError'], [true]);
        $carrierRateMock2 = $fixture->mockFunctions($classMock, ['getResult'], [$resultMock]);
        $magentoShippingMock2 = $fixture->mockFunctions($classMock, ['collectCarrierRates'], [$carrierRateMock2]);
        $fixture->setPrivatePropertyValue($shippingMock, ['_magentoShipping'], [$magentoShippingMock2]);
        $this->assertFalse(
            $fixture->invokeMethod($shippingMock, '_getProductShippingCost'),
            '[Test Get Product Shipping Cost] Check return when carrier rates result return a error'
        );
        $resultMock2 = $fixture->mockFunctions($classMock, ['getError', 'getAllRates'], [false, []]);
        $carrierRateMock3 = $fixture->mockFunctions($classMock, ['getResult'], [$resultMock2]);
        $magentoShippingMock3 = $fixture->mockFunctions($classMock, ['collectCarrierRates'], [$carrierRateMock3]);
        $fixture->setPrivatePropertyValue($shippingMock, ['_magentoShipping'], [$magentoShippingMock3]);
        $this->assertEquals(
            0,
            $fixture->invokeMethod($shippingMock, '_getProductShippingCost'),
            '[Test Get Product Shipping Cost] Check return when carrier rates result return a empty array'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($shippingMock, '_shippingCostFixed'),
            '[Test Get Product Shipping Cost] Check shipping cost fixed attribute is null'
        );
        $rateMock = $fixture->mockFunctions($classMock, ['getPrice'], ['9.99']);
        $resultMock3 = $fixture->mockFunctions($classMock, ['getError', 'getAllRates'], [false, [$rateMock]]);
        $carrierRateMock4 = $fixture->mockFunctions($classMock, ['getResult'], [$resultMock3]);
        $magentoShippingMock4 = $fixture->mockFunctions($classMock, ['collectCarrierRates'], [$carrierRateMock4]);
        $priceCurrencyMock = $fixture->mockFunctions($classMock, ['round', 'convertAndRound'], [9.99, 10.53]);
        $fixture->setPrivatePropertyValue(
            $shippingMock,
            ['_magentoShipping', '_priceCurrency', '_shippingIsFixed'],
            [$magentoShippingMock4, $priceCurrencyMock, true]
        );
        $this->assertInternalType(
            'float',
            $fixture->invokeMethod($shippingMock, '_getProductShippingCost'),
            '[Test Get Shipping Data] Check if return is a float'
        );
        $this->assertEquals(
            9.99,
            $fixture->invokeMethod($shippingMock, '_getProductShippingCost'),
            '[Test Get Product Shipping Cost] Check return when carrier rates result return a rate'
        );
        $this->assertEquals(
            9.99,
            $fixture->getPrivatePropertyValue($shippingMock, '_shippingCostFixed'),
            '[Test Get Product Shipping Cost] Check shipping cost fixed attribute is set'
        );
        $fixture->setPrivatePropertyValue($shippingMock, ['_currency', '_storeCurrency'], ['USD', 'EUR']);
        $this->assertEquals(
            10.53,
            $fixture->invokeMethod($shippingMock, '_getProductShippingCost'),
            '[Test Get Product Shipping Cost] Check return when carrier rates result return a rate with conversion'
        );
    }
}
