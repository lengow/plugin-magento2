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
use Lengow\Connector\Model\Export\Product;
use Lengow\Connector\Test\Unit\Fixture;

class ProductTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Lengow\Connector\Model\Export\Product
     */
    protected $_product;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_product = $objectManager->getObject(Product::class);
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Product::class,
            $this->_product,
            '[Test Class Instantiation] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Product::getAllCounter
     */
    public function testGetAllCounter()
    {
        $fixture = New Fixture();
        $fixture->setPrivatePropertyValue(
            $this->_product,
            [
                '_simpleCounter',
                '_simpleDisabledCounter',
                '_configurableCounter',
                '_groupedCounter',
                '_virtualCounter',
                '_downloadableCounter'
            ],
            [100, 50, 25, 10, 10, 5]
        );
        $this->assertInternalType(
            'array',
            $this->_product->getAllCounter(),
            '[Test Get All Counter] Check if return is a array'
        );
        $this->assertEquals(
            [
                'total'           => 100,
                'simple'          => 100,
                'simple_enable'   => 50,
                'simple_disabled' => 50,
                'configurable'    => 25,
                'grouped'         => 10,
                'virtual'         => 10,
                'downloadable'    => 5
            ],
            $this->_product->getAllCounter(),
            '[Test Get All Counter] Check if return is valid'
        );
    }
}
