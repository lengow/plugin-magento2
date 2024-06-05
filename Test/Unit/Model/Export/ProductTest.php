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
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\ProductRepository;
use Magento\Store\Model\Store;
use Magento\Framework\Data\Collection;
use Lengow\Connector\Model\Export\Product;
use Lengow\Connector\Test\Unit\Fixture;
use Lengow\Connector\Helper\Config as ConfigHelper;

class ProductTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_magentoProduct;

    /**
     * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable
     */
    protected $_magentoConfigurableProduct;

    /**
     * @var \Magento\Catalog\Model\ProductRepository;
     */
    protected $_productRepository;

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $_store;

    /**
     * @var \Magento\Framework\Data\Collection
     */
    protected $_collection;

    /**
     * @var \Lengow\Connector\Model\Export\Product
     */
    protected $_product;

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
        $this->_product = $objectManager->getObject(Product::class);
        $this->_magentoProduct = $objectManager->getObject(MagentoProduct::class);
        $this->_magentoConfigurableProduct = $objectManager->getObject(Configurable::class);
        $this->_productRepository = $objectManager->getObject(ProductRepository::class);
        $this->_store = $objectManager->getObject(Store::class);
        $this->_collection = $objectManager->getObject(Collection::class);
        $this->_configHelper = $objectManager->getObject(ConfigHelper::class);
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
     * @covers \Lengow\Connector\Model\Export\Product::isEnableForExport
     */
    public function testIsEnableForExport()
    {
        $fixture = new Fixture();
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['configurable']);
        $this->assertTrue(
            $this->_product->isEnableForExport(),
            '[Test Is Enable For Export] Check if return is valid for a configurable product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['grouped']);
        $this->assertTrue(
            $this->_product->isEnableForExport(),
            '[Test Is Enable For Export] Check if return is valid for a grouped product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['virtual']);
        $this->assertTrue(
            $this->_product->isEnableForExport(),
            '[Test Is Enable For Export] Check if return is valid for a virtual product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['downloadable']);
        $this->assertTrue(
            $this->_product->isEnableForExport(),
            '[Test Is Enable For Export] Check if return is valid for a downloadable product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['simple']);
        $this->assertTrue(
            $this->_product->isEnableForExport(),
            '[Test Is Enable For Export] Check if return is valid for a simple product'
        );
        $magentoProductMock = $fixture->mockFunctions($this->_magentoProduct, ['getStatus'], [1]);
        $fixture->setPrivatePropertyValue($this->_product, ['parentProduct'], [$magentoProductMock]);
        $this->assertTrue(
            $this->_product->isEnableForExport(),
            '[Test Is Enable For Export] Check if return is valid for a child product with enable parent'
        );
        $magentoProductMock = $fixture->mockFunctions($this->_magentoProduct, ['getStatus'], [2]);
        $fixture->setPrivatePropertyValue(
            $this->_product,
            ['type', 'parentProduct'],
            ['simple', $magentoProductMock]
        );
        $this->assertNotTrue(
            $this->_product->isEnableForExport(),
            '[Test Is Enable For Export] Check if return is valid for a child product with disable parent'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::getCounters
     */
    public function testGetCounters()
    {
        $fixture = new Fixture();
        $fixture->setPrivatePropertyValue(
            $this->_product,
            [
                'simpleCounter',
                'simpleDisabledCounter',
                'configurableCounter',
                'groupedCounter',
                'virtualCounter',
                'downloadableCounter',
            ],
            [100, 50, 25, 10, 10, 5]
        );
        $this->assertIsArray(
            $this->_product->getCounters(),
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
                'downloadable'    => 5,
            ],
            $this->_product->getCounters(),
            '[Test Get All Counter] Check if return is valid'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::clean
     */
    public function testClean()
    {
        $fixture = new Fixture();
        $productMock = $this->createMock(Product::class);
        $priceMock    = $fixture->mockFunctions($productMock, ['clean'], [true]);
        $shippingMock = $fixture->mockFunctions($productMock, ['clean'], [true]);
        $categoryMock = $fixture->mockFunctions($productMock, ['clean'], [true]);
        $fixture->setPrivatePropertyValue(
            $this->_product,
            [
                'product',
                'parentProduct',
                'type',
                'childrenIds',
                'prices',
                'discounts',
                'images',
                'variationList',
                'quantity',
                'getParentData',
                'price',
                'shipping',
                'category',
            ],
            [
                'product',
                'parentProduct',
                'configurable',
                ['childrenIds'],
                ['prices'],
                ['discounts'],
                ['images'],
                'variationList',
                10,
                true,
                $priceMock,
                $shippingMock,
                $categoryMock,
            ]
        );
        $this->_product->clean();
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, 'product'),
            '[Test Clean] Check if product attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, 'parentProduct'),
            '[Test Clean] Check if parentProduct attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, 'type'),
            '[Test Clean] Check if type attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, 'childrenIds'),
            '[Test Clean] Check if childrenIds attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, 'prices'),
            '[Test Clean] Check if prices attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, 'discounts'),
            '[Test Clean] Check if discounts attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, 'images'),
            '[Test Clean] Check if images attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, 'variationList'),
            '[Test Clean] Check if variationList attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, 'quantity'),
            '[Test Clean] Check if quantity attribute is null'
        );
        $this->assertFalse(
            $fixture->getPrivatePropertyValue($this->_product, 'getParentData'),
            '[Test Clean] Check if getParentData attribute is null'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::setCounter
     */
    public function testSetCounter()
    {
        $fixture = new Fixture();
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['simple']);
        $fixture->invokeMethod($this->_product, 'setCounter');
        $this->assertEquals(
            1,
            $fixture->getPrivatePropertyValue($this->_product, 'simpleCounter'),
            '[Test Set Counter] Check simple counter'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['configurable']);
        $fixture->invokeMethod($this->_product, 'setCounter');
        $this->assertEquals(
            1,
            $fixture->getPrivatePropertyValue($this->_product, 'configurableCounter'),
            '[Test Set Counter] Check configurable counter'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['grouped']);
        $fixture->invokeMethod($this->_product, 'setCounter');
        $this->assertEquals(
            1,
            $fixture->getPrivatePropertyValue($this->_product, 'groupedCounter'),
            '[Test Set Counter] Check grouped counter'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['virtual']);
        $fixture->invokeMethod($this->_product, 'setCounter');
        $this->assertEquals(
            1,
            $fixture->getPrivatePropertyValue($this->_product, 'virtualCounter'),
            '[Test Set Counter] Check virtual counter'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['downloadable']);
        $fixture->invokeMethod($this->_product, 'setCounter');
        $this->assertEquals(
            1,
            $fixture->getPrivatePropertyValue($this->_product, 'downloadableCounter'),
            '[Test Set Counter] Check downloadable counter'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::getParentProduct
     */
    public function testGetParentProduct()
    {
        $fixture = new Fixture();
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['virtual']);
        $this->assertEquals(
            null,
            $fixture->invokeMethod($this->_product, 'getParentProduct'),
            '[Test Get Parent Product] Check return when is not a simple product'
        );
        $this->assertNotTrue(
            $fixture->getPrivatePropertyValue($this->_product, 'getParentData'),
            '[Test Get Parent Product] Check if get parent data is false when is not a child product'
        );
        $magentoProductMock = $fixture->mockFunctions(
            $this->_magentoProduct,
            ['getId', 'getVisibility'],
            [21, 1]
        );
        $magentoConfigurableProductMock = $fixture->mockFunctions(
            $this->_magentoConfigurableProduct,
            ['getParentIdsByChild'],
            [[]]
        );
        $fixture->setPrivatePropertyValue(
            $this->_product,
            ['type', 'product', 'configurableProduct'],
            ['simple', $magentoProductMock, $magentoConfigurableProductMock]
        );
        $this->assertEquals(
            null,
            $fixture->invokeMethod($this->_product, 'getParentProduct'),
            '[Test Get Parent Product] Check if return is valid for a simple product'
        );
        $this->assertNotTrue(
            $fixture->getPrivatePropertyValue($this->_product, 'getParentData'),
            '[Test Get Parent Product] Check if get parent data is false for a simple product without parent'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::getConfigurableProduct
     */
    public function testGetConfigurableProduct()
    {
        $fixture = new Fixture();
        $fixture->setPrivatePropertyValue($this->_product, ['cacheConfigurableProducts'], [[123 => 'plop']]);
        $this->assertNull(
            $fixture->invokeMethod($this->_product, 'getConfigurableProduct', [123]),
            '[Test Get Configurable Product] Check return when configurable is already in cache'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::getImages
     */
    public function testGetImages()
    {
        $fixture = new Fixture();
        $storeMock = $fixture->mockFunctions($this->_store, ['getId'], [1]);
        $configHelperMock = $fixture->mockFunctions($this->_configHelper, ['get'], [null]);
        $collectionMock = $fixture->mockFunctions(
            $this->_collection,
            ['toArray'],
            [
                ['items' =>
                    [
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-black_main.jpg'],
                    ],
                ],
            ]
        );
        $productMock = $fixture->mockFunctions(
            $this->_magentoProduct,
            ['getMediaGalleryImages', 'getImage'],
            [$collectionMock, '/m/h/mh01-black_main.jpg']
        );
        $parentCollectionMock = $fixture->mockFunctions(
            $this->_collection,
            ['toArray'],
            [
                ['items' =>
                    [
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt1.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_main.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_back.jpg'],
                    ],
                ],
            ]
        );
        $parentProductMock = $fixture->mockFunctions(
            $this->_magentoProduct,
            ['getMediaGalleryImages'],
            [$parentCollectionMock]
        );
        $fixture->setPrivatePropertyValue(
            $this->_product,
            [
                'store',
                'parentProduct',
                'product',
                'configHelper',
                'baseImageUrl',
            ],
            [
                $storeMock,
                $parentProductMock,
                $productMock,
                $configHelperMock,
                'http://www.site.com/pub/media/catalog/product',
            ]
        );
        $this->assertIsArray(
            $fixture->invokeMethod($this->_product, 'getImages'),
            '[Test Get Images] Check if return is a array'
        );
        $this->assertEquals(
            [
                'image_url_1'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-black_main.jpg',
                'image_url_2'   => '',
                'image_url_3'   => '',
                'image_url_4'   => '',
                'image_url_5'   => '',
                'image_url_6'   => '',
                'image_url_7'   => '',
                'image_url_8'   => '',
                'image_url_9'   => '',
                'image_url_10'  => '',
                'image_default' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-black_main.jpg',
            ],
            $fixture->invokeMethod($this->_product, 'getImages'),
            '[Test Get Images] Check if a valid return without parent images'
        );
        $configHelperMock2 = $fixture->mockFunctions($this->_configHelper, ['get'], [1]);
        $productMock2 = $fixture->mockFunctions(
            $this->_magentoProduct,
            ['getMediaGalleryImages', 'getImage'],
            [$collectionMock, null]
        );
        $fixture->setPrivatePropertyValue(
            $this->_product,
            ['product', 'configHelper'],
            [$productMock2, $configHelperMock2]
        );
        $this->assertEquals(
            [
                'image_url_1'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt1.jpg',
                'image_url_2'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_main.jpg',
                'image_url_3'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_back.jpg',
                'image_url_4'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-black_main.jpg',
                'image_url_5'   => '',
                'image_url_6'   => '',
                'image_url_7'   => '',
                'image_url_8'   => '',
                'image_url_9'   => '',
                'image_url_10'  => '',
                'image_default' => '',
            ],
            $fixture->invokeMethod($this->_product, 'getImages'),
            '[Test Get Images] Check if a valid return without parent images'
        );
        $parentCollectionMock2 = $fixture->mockFunctions(
            $this->_collection,
            ['toArray'],
            [
                ['items' =>
                    [
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt1.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt2.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt3.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt4.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt5.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt6.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt7.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt8.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt9.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt10.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt11.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt12.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt13.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt14.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt15.jpg'],
                    ],
                ],
            ]
        );
        $parentProductMock2 = $fixture->mockFunctions(
            $this->_magentoProduct,
            ['getMediaGalleryImages'],
            [$parentCollectionMock2]
        );
        $fixture->setPrivatePropertyValue($this->_product, ['parentProduct'], [$parentProductMock2]);
        $this->assertEquals(
            [
                'image_url_1'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt1.jpg',
                'image_url_2'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt2.jpg',
                'image_url_3'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt3.jpg',
                'image_url_4'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt4.jpg',
                'image_url_5'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt5.jpg',
                'image_url_6'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt6.jpg',
                'image_url_7'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt7.jpg',
                'image_url_8'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt8.jpg',
                'image_url_9'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt9.jpg',
                'image_url_10'  => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt10.jpg',
                'image_default' => '',
            ],
            $fixture->invokeMethod($this->_product, 'getImages'),
            '[Test Get Images] Check if a valid return without parent images'
        );
        $productMock3 = $fixture->mockFunctions(
            $this->_magentoProduct,
            ['getMediaGalleryImages', 'getImage'],
            [null, null]
        );
        $parentProductMock3 = $fixture->mockFunctions($this->_magentoProduct, ['getMediaGalleryImages'], [null]);
        $fixture->setPrivatePropertyValue(
            $this->_product,
            ['product', 'parentProduct'],
            [$productMock3, $parentProductMock3]
        );
        $this->assertEquals(
            [
                'image_url_1'   => '',
                'image_url_2'   => '',
                'image_url_3'   => '',
                'image_url_4'   => '',
                'image_url_5'   => '',
                'image_url_6'   => '',
                'image_url_7'   => '',
                'image_url_8'   => '',
                'image_url_9'   => '',
                'image_url_10'  => '',
                'image_default' => '',
            ],
            $fixture->invokeMethod($this->_product, 'getImages'),
            '[Test Get Images] Check return when media gallery images is null'
        );
        $collectionMock2 = $fixture->mockFunctions(
            $this->_collection,
            ['toArray'],
            [
                ['items' =>
                    [
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt1.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt2.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt3.jpg'],
                    ],
                ],
            ]
        );
        $productMock3 = $fixture->mockFunctions(
            $this->_magentoProduct,
            ['getMediaGalleryImages', 'getImage'],
            [$collectionMock2, '/m/h/mh01-black_main.jpg']
        );
        $parentCollectionMock4 = $fixture->mockFunctions(
            $this->_collection,
            ['toArray'],
            [
                ['items' =>
                    [
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt2.jpg'],
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt1.jpg'],
                    ],
                ],
            ]
        );
        $parentProductMock4 = $fixture->mockFunctions(
            $this->_magentoProduct,
            ['getMediaGalleryImages'],
            [$parentCollectionMock4]
        );
        $fixture->setPrivatePropertyValue(
            $this->_product,
            ['product', 'parentProduct'],
            [$productMock3, $parentProductMock4]
        );
        $this->assertEquals(
            [
                'image_url_1'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt2.jpg',
                'image_url_2'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt1.jpg',
                'image_url_3'   => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-gray_alt3.jpg',
                'image_url_4'   => '',
                'image_url_5'   => '',
                'image_url_6'   => '',
                'image_url_7'   => '',
                'image_url_8'   => '',
                'image_url_9'   => '',
                'image_url_10'  => '',
                'image_default' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-black_main.jpg',
            ],
            $fixture->invokeMethod($this->_product, 'getImages'),
            '[Test Get Images] Check if a valid return without parent images'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::getVariationList
     */
    public function testGetVariationList()
    {
        $fixture = new Fixture();
        $collectionMock = $fixture->mockFunctions(
            $this->_collection,
            ['getConfigurableAttributesAsArray'],
            [
                [
                    ['frontend_label' => 'color'],
                    ['frontend_label' => 'size'],
                ],
            ]
        );
        $productMock = $fixture->mockFunctions($this->_magentoProduct, ['getTypeInstance'], [$collectionMock]);
        $parentCollectionMock = $fixture->mockFunctions(
            $this->_collection,
            ['getConfigurableAttributesAsArray'],
            [
                [
                    ['frontend_label' => 'color'],
                    ['frontend_label' => 'size'],
                    ['frontend_label' => 'depth'],
                ],
            ]
        );
        $parentProductMock = $fixture->mockFunctions(
            $this->_magentoProduct,
            ['getTypeInstance'],
            [$parentCollectionMock]
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['simple']);
        $this->assertIsString(
            $fixture->invokeMethod($this->_product, 'getVariationList'),
            '[Test Get Variation List] Check if return is a string'
        );
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_product, 'getVariationList'),
            '[Test Get Variation Lis] Check if a valid return for simple product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['grouped']);
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_product, 'getVariationList'),
            '[Test Get Variation Lis] Check if a valid return for grouped product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['virtual']);
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_product, 'getVariationList'),
            '[Test Get Variation Lis] Check if a valid return for virtual product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['downloadable']);
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_product, 'getVariationList'),
            '[Test Get Variation Lis] Check if a valid return for downloadable product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type', 'product'], ['configurable', $productMock]);
        $this->assertEquals(
            'color, size',
            $fixture->invokeMethod($this->_product, 'getVariationList'),
            '[Test Get Variation Lis] Check if a valid return for configurable product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type', 'parentProduct'], ['simple', $parentProductMock]);
        $this->assertEquals(
            'color, size, depth',
            $fixture->invokeMethod($this->_product, 'getVariationList'),
            '[Test Get Variation Lis] Check if a valid return for child product'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::getChildrenIds
     */
    public function testGetChildrenIds()
    {
        $fixture = new Fixture();
        $collectionMock = $fixture->mockFunctions(
            $this->_collection,
            ['getChildrenIds'],
            [[3 => [1 =>1, 2 => 2, 3 => 3]]]
        );
        $productMock = $fixture->mockFunctions(
            $this->_magentoProduct,
            ['getTypeInstance', 'getId'],
            [$collectionMock, 4]
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type', 'product'], ['simple', $productMock]);
        $this->assertIsArray(
            $fixture->invokeMethod($this->_product, 'getChildrenIds'),
            '[Test Get Children Ids] Check if return is a array'
        );
        $this->assertEquals(
            [],
            $fixture->invokeMethod($this->_product, 'getChildrenIds'),
            '[Test Get Children Ids] Check if a valid return for simple product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type', 'product'], ['configurable', $productMock]);
        $this->assertEquals(
            [],
            $fixture->invokeMethod($this->_product, 'getChildrenIds'),
            '[Test Get Children Ids] Check if a valid return for configurable product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type', '_product'], ['virtual', $productMock]);
        $this->assertEquals(
            [],
            $fixture->invokeMethod($this->_product, 'getChildrenIds'),
            '[Test Get Children Ids] Check if a valid return for virtual product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type', 'product'], ['downloadable', $productMock]);
        $this->assertEquals(
            [],
            $fixture->invokeMethod($this->_product, 'getChildrenIds'),
            '[Test Get Children Ids] Check if a valid return for downloadable product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type', 'product'], ['grouped', $productMock]);
        $this->assertEquals(
            [1, 2, 3],
            $fixture->invokeMethod($this->_product, 'getChildrenIds'),
            '[Test Get Children Ids] Check if a valid return for grouped product'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::getQuantity
     */
    public function testGetQuantity()
    {
        $fixture = new Fixture();
        $classMock = $fixture->getFakeClass();
        $storeMock = $fixture->mockFunctions($this->_store, ['getId'], [1]);
        $productMock = $fixture->mockFunctions($this->_product, ['getId'], [5]);
        $stockItemMock = $fixture->mockFunctions($classMock, ['getQty'], ['10']);
        $stockRegistryMock = $fixture->mockFunctions($classMock, ['getStockItem'], [$stockItemMock]);
        $fixture->setPrivatePropertyValue(
            $this->_product,
            ['type', 'product', 'store', 'stockRegistry', 'childrenIds'],
            ['simple', $productMock, $storeMock, $stockRegistryMock, [1, 2, 3]]
        );
        $this->assertIsInt(
            $fixture->invokeMethod($this->_product, 'getQuantity'),
            '[Test Get Quantity] Check if return is a integer'
        );
        $this->assertEquals(
            10,
            $fixture->invokeMethod($this->_product, 'getQuantity'),
            '[Test Get Quantity] Check if a valid return for simple product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['configurable']);
        $this->assertEquals(
            10,
            $fixture->invokeMethod($this->_product, 'getQuantity'),
            '[Test Get Quantity] Check if a valid return for configurable product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['virtual']);
        $this->assertEquals(
            10,
            $fixture->invokeMethod($this->_product, 'getQuantity'),
            '[Test Get Quantity] Check if a valid return for virtual product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['type'], ['downloadable']);
        $this->assertEquals(
            10,
            $fixture->invokeMethod($this->_product, 'getQuantity'),
            '[Test Get Quantity] Check if a valid return for downloadable product'
        );
        $stockItemMock2 = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['getQty'])
            ->disableOriginalConstructor()
            ->getMock();
        $stockItemMock2->expects($this->any())->method('getQty')->willReturnOnConsecutiveCalls(5, 10, 15);
        $stockRegistryMock2 = $fixture->mockFunctions($classMock, ['getStockItem'], [$stockItemMock2]);
        $fixture->setPrivatePropertyValue(
            $this->_product,
            ['type', 'stockRegistry'],
            ['grouped', $stockRegistryMock2]
        );
        $this->assertEquals(
            5,
            $fixture->invokeMethod($this->_product, 'getQuantity'),
            '[Test Get Quantity] Check if a valid return for grouped product'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::getAttributeValue
     */
    public function testGetAttributeValue()
    {
        $fixture = new Fixture();
        $productMock = $fixture->mockFunctions($this->_product, ['getData'], [null]);
        $fixture->setPrivatePropertyValue($this->_product, ['product'], [$productMock]);
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_product, 'getAttributeValue', ['plop']),
            '[Test Get Attribute Value] Check if a valid return when attribute value is null'
        );

        $productMock2 = $fixture->mockFunctions($this->_product, ['getData'], [['array', 'plop', 'value']]);
        $fixture->setPrivatePropertyValue($this->_product, ['product'], [$productMock2]);
        $this->assertEquals(
            'array, plop, value',
            $fixture->invokeMethod($this->_product, 'getAttributeValue', ['plop']),
            '[Test Get Attribute Value] Check if a valid return when attribute value is null'
        );
        $classMock = $fixture->getFakeClass();
        $frontendMock = $fixture->mockFunctions($classMock, ['getValue'], ['front plop']);
        $getAttributeMock = $fixture->mockFunctions($classMock, ['getFrontend'], [$frontendMock]);
        $getResourceMock = $fixture->mockFunctions($classMock, ['getAttribute'], [$getAttributeMock]);
        $productMock = $fixture->mockFunctions($this->_product, ['getData', 'getResource'], ['plop', $getResourceMock]);
        $fixture->setPrivatePropertyValue($this->_product, ['product'], [$productMock]);
        $this->assertEquals(
            'front plop',
            $fixture->invokeMethod($this->_product, 'getAttributeValue', ['plop']),
            '[Test Get Attribute Value] Check if a valid return when attribute value is null'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::getGroupedPricesAndDiscounts
     */
    public function testGetGroupedPricesAndDiscounts()
    {
        $fixture = new Fixture();
        $classMock = $fixture->getFakeClass();
        $fixture->setPrivatePropertyValue($this->_product, ['childrenIds'], [[]]);
        $this->assertIsArray(
            $fixture->invokeMethod($this->_product, 'getGroupedPricesAndDiscounts'),
            '[Test Get Grouped Prices And Discount] Check if return is an array'
        );
        $this->assertEquals(
            [
                'prices' => [
                    'price_excl_tax' => 0,
                    'price_incl_tax' => 0,
                    'price_before_discount_excl_tax' => 0,
                    'price_before_discount_incl_tax' => 0,
                ],
                'discounts' => [
                    'discount_amount' => 0,
                    'discount_percent' => 0,
                    'discount_start_date' => '',
                    'discount_end_date' => '',
                ]
            ],
            $fixture->invokeMethod($this->_product, 'getGroupedPricesAndDiscounts'),
            '[Test Get Grouped Prices And Discount] Check if return is valid when children ids is empty'
        );
    }
}
