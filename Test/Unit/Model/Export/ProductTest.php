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


class ProductTest extends \PHPUnit_Framework_TestCase
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
    public function setUp()
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
        $fixture = New Fixture();
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['configurable']);
        $this->assertTrue(
            $this->_product->isEnableForExport(),
            '[Test Is Enable For Export] Check if return is valid for a configurable product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['grouped']);
        $this->assertTrue(
            $this->_product->isEnableForExport(),
            '[Test Is Enable For Export] Check if return is valid for a grouped product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['virtual']);
        $this->assertTrue(
            $this->_product->isEnableForExport(),
            '[Test Is Enable For Export] Check if return is valid for a virtual product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['downloadable']);
        $this->assertTrue(
            $this->_product->isEnableForExport(),
            '[Test Is Enable For Export] Check if return is valid for a downloadable product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['simple']);
        $this->assertTrue(
            $this->_product->isEnableForExport(),
            '[Test Is Enable For Export] Check if return is valid for a simple product'
        );
        $magentoProductMock = $fixture->mockFunctions($this->_magentoProduct, ['getStatus'], [1]);
        $fixture->setPrivatePropertyValue($this->_product, ['_parentProduct'], [$magentoProductMock]);
        $this->assertTrue(
            $this->_product->isEnableForExport(),
            '[Test Is Enable For Export] Check if return is valid for a child product with enable parent'
        );
        $magentoProductMock = $fixture->mockFunctions($this->_magentoProduct, ['getStatus'], [2]);
        $fixture->setPrivatePropertyValue(
            $this->_product,
            ['_type', '_parentProduct'],
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
                'downloadable'    => 5
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
        $fixture = New Fixture();
        $classMock = $fixture->getFakeClass();
        $priceMock = $fixture->mockFunctions($classMock, ['clean'], [true]);
        $shippingMock = $fixture->mockFunctions($classMock, ['clean'], [true]);
        $categoryMock = $fixture->mockFunctions($classMock, ['clean'], [true]);
        $fixture->setPrivatePropertyValue(
            $this->_product,
            [
                '_product',
                '_parentProduct',
                '_type',
                '_childrenIds',
                '_prices',
                '_discounts',
                '_images',
                '_variationList',
                '_quantity',
                '_getParentData',
                '_price',
                '_shipping',
                '_category'
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
                $categoryMock
            ]
        );
        $this->_product->clean();
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, '_product'),
            '[Test Clean] Check if _product attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, '_parentProduct'),
            '[Test Clean] Check if _parentProduct attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, '_type'),
            '[Test Clean] Check if _type attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, '_childrenIds'),
            '[Test Clean] Check if _childrenIds attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, '_prices'),
            '[Test Clean] Check if _prices attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, '_discounts'),
            '[Test Clean] Check if _discounts attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, '_images'),
            '[Test Clean] Check if _images attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, '_variationList'),
            '[Test Clean] Check if _variationList attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_product, '_quantity'),
            '[Test Clean] Check if _quantity attribute is null'
        );
        $this->assertFalse(
            $fixture->getPrivatePropertyValue($this->_product, '_getParentData'),
            '[Test Clean] Check if _getParentData attribute is null'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::_setCounter
     */
    public function testSetCounter()
    {
        $fixture = New Fixture();
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['simple']);
        $fixture->invokeMethod($this->_product, '_setCounter');
        $this->assertEquals(
            1,
            $fixture->getPrivatePropertyValue($this->_product, '_simpleCounter'),
            '[Test Set Counter] Check simple counter'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['configurable']);
        $fixture->invokeMethod($this->_product, '_setCounter');
        $this->assertEquals(
            1,
            $fixture->getPrivatePropertyValue($this->_product, '_configurableCounter'),
            '[Test Set Counter] Check configurable counter'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['grouped']);
        $fixture->invokeMethod($this->_product, '_setCounter');
        $this->assertEquals(
            1,
            $fixture->getPrivatePropertyValue($this->_product, '_groupedCounter'),
            '[Test Set Counter] Check grouped counter'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['virtual']);
        $fixture->invokeMethod($this->_product, '_setCounter');
        $this->assertEquals(
            1,
            $fixture->getPrivatePropertyValue($this->_product, '_virtualCounter'),
            '[Test Set Counter] Check virtual counter'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['downloadable']);
        $fixture->invokeMethod($this->_product, '_setCounter');
        $this->assertEquals(
            1,
            $fixture->getPrivatePropertyValue($this->_product, '_downloadableCounter'),
            '[Test Set Counter] Check downloadable counter'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::_getParentProduct
     */
    public function testGetParentProduct()
    {
        $fixture = New Fixture();
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['virtual']);
        $this->assertEquals(
            null,
            $fixture->invokeMethod($this->_product, '_getParentProduct'),
            '[Test Get Parent Product] Check return when is not a simple product'
        );
        $this->assertNotTrue(
            $fixture->getPrivatePropertyValue($this->_product, '_getParentData'),
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
            ['_type', '_product', '_configurableProduct'],
            ['simple', $magentoProductMock, $magentoConfigurableProductMock]
        );
        $this->assertEquals(
            null,
            $fixture->invokeMethod($this->_product, '_getParentProduct'),
            '[Test Get Parent Product] Check if return is valid for a simple product'
        );
        $this->assertNotTrue(
            $fixture->getPrivatePropertyValue($this->_product, '_getParentData'),
            '[Test Get Parent Product] Check if get parent data is false for a simple product without parent'
        );
        $magentoConfigurableProductMock2 = $fixture->mockFunctions(
            $this->_magentoConfigurableProduct,
            ['getParentIdsByChild'],
            [[123, 254]]
        );
        $fixture->setPrivatePropertyValue(
            $this->_product,
            ['_configurableProduct', '_cacheConfigurableProducts'],
            [$magentoConfigurableProductMock2, [123 => 'plop']]
        );
        $fixture->mockFunctions($this->_product, ['_getConfigurableProduct'], ['plop']);
        $this->assertEquals(
            'plop',
            $fixture->invokeMethod($this->_product, '_getParentProduct'),
            '[Test Get Parent Product] Check if return is valid for a simple product'
        );
        $this->assertTrue(
            $fixture->getPrivatePropertyValue($this->_product, '_getParentData'),
            '[Test Get Parent Product] Check if get parent data is true when child is not visible'
        );
        $magentoProductMock2 = $fixture->mockFunctions($this->_magentoProduct, ['getVisibility'], [2]);
        $fixture->setPrivatePropertyValue(
            $this->_product,
            ['_product', '_getParentData'],
            [$magentoProductMock2, false]
        );
        $fixture->invokeMethod($this->_product, '_getParentProduct');
        $this->assertNotTrue(
            $fixture->getPrivatePropertyValue($this->_product, '_getParentData'),
            '[Test Get Parent Product] Check if get parent data is false when child visible'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::_getConfigurableProduct
     */
    public function testGetConfigurableProduct()
    {
        $fixture = New Fixture();
        $fixture->setPrivatePropertyValue($this->_product, ['_cacheConfigurableProducts'], [[123 => 'plop']]);
        $this->assertEquals(
            'plop',
            $fixture->invokeMethod($this->_product, '_getConfigurableProduct', [123]),
            '[Test Get Configurable Product] Check return when configurable is already in cache'
        );
        $storeMock = $fixture->mockFunctions($this->_store, ['getId'], [1]);
        $productRepositoryMock = $fixture->mockFunctions($this->_productRepository, ['getById'], ['hello']);
        $fixture->setPrivatePropertyValue(
            $this->_product,
            ['_cacheConfigurableProducts', '_store', '_productRepository'],
            [[], $storeMock, $productRepositoryMock]
        );
        $this->assertEquals(
            'hello',
            $fixture->invokeMethod($this->_product, '_getConfigurableProduct', [123]),
            '[Test Get Configurable Product] Check return when configurable is load by product repository'
        );
        $this->assertEquals(
            1,
            $fixture->getPrivatePropertyValue($this->_product, '_clearCacheConfigurable'),
            '[Test Get Configurable Product] Check if counter is valid'
        );
        $this->assertEquals(
            [123 => 'hello'],
            $fixture->getPrivatePropertyValue($this->_product, '_cacheConfigurableProducts'),
            '[Test Get Configurable Product] Check if configurable cache is valid'
        );
        $fixture->setPrivatePropertyValue(
            $this->_product,
            ['_cacheConfigurableProducts', '_clearCacheConfigurable'],
            [[123 => 'hello', 132 => 'plop', 352 => 'toto'], 301]
        );
        $this->assertEquals(
            'hello',
            $fixture->invokeMethod($this->_product, '_getConfigurableProduct', [555]),
            '[Test Get Configurable Product] Check return when configurable is load by product repository after a reset'
        );
        $this->assertEquals(
            1,
            $fixture->getPrivatePropertyValue($this->_product, '_clearCacheConfigurable'),
            '[Test Get Configurable Product] Check if counter is valid after a reset'
        );
        $this->assertEquals(
            [555 => 'hello'],
            $fixture->getPrivatePropertyValue($this->_product, '_cacheConfigurableProducts'),
            '[Test Get Configurable Product] Check if configurable cache is valid after a reset'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::_getImages
     */
    public function testGetImages()
    {
        $fixture = New Fixture();
        $storeMock = $fixture->mockFunctions($this->_store, ['getId'], [1]);
        $configHelperMock = $fixture->mockFunctions($this->_configHelper, ['get'], [null]);
        $collectionMock = $fixture->mockFunctions(
            $this->_collection,
            ['toArray'],
            [
                ['items' =>
                    [
                        ['url' => 'http://www.site.com/pub/media/catalog/product/m/h/mh01-black_main.jpg'],
                    ]
                ]
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
                    ]
                ]
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
                '_store',
                '_parentProduct',
                '_product',
                '_configHelper',
                '_baseImageUrl'
            ],
            [
                $storeMock,
                $parentProductMock,
                $productMock,
                $configHelperMock,
                'http://www.site.com/pub/media/catalog/product'
            ]
        );
        $this->assertInternalType(
            'array',
            $fixture->invokeMethod($this->_product, '_getImages'),
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
            $fixture->invokeMethod($this->_product, '_getImages'),
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
            ['_product', '_configHelper'],
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
            $fixture->invokeMethod($this->_product, '_getImages'),
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
                    ]
                ]
            ]
        );
        $parentProductMock2 = $fixture->mockFunctions(
            $this->_magentoProduct,
            ['getMediaGalleryImages'],
            [$parentCollectionMock2]
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_parentProduct'], [$parentProductMock2]);
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
            $fixture->invokeMethod($this->_product, '_getImages'),
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
            ['_product', '_parentProduct'],
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
            $fixture->invokeMethod($this->_product, '_getImages'),
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
                    ]
                ]
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
                    ]
                ]
            ]
        );
        $parentProductMock4 = $fixture->mockFunctions(
            $this->_magentoProduct,
            ['getMediaGalleryImages'],
            [$parentCollectionMock4]
        );
        $fixture->setPrivatePropertyValue(
            $this->_product,
            ['_product', '_parentProduct'],
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
            $fixture->invokeMethod($this->_product, '_getImages'),
            '[Test Get Images] Check if a valid return without parent images'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::_getVariationList
     */
    public function testGetVariationList()
    {
        $fixture = New Fixture();
        $collectionMock = $fixture->mockFunctions(
            $this->_collection,
            ['getConfigurableAttributesAsArray'],
            [
                [
                    ['frontend_label' => 'color'],
                    ['frontend_label' => 'size'],
                ]
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
                ]
            ]
        );
        $parentProductMock = $fixture->mockFunctions(
            $this->_magentoProduct,
            ['getTypeInstance'],
            [$parentCollectionMock]
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['simple']);
        $this->assertInternalType(
            'string',
            $fixture->invokeMethod($this->_product, '_getVariationList'),
            '[Test Get Variation List] Check if return is a string'
        );
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_product, '_getVariationList'),
            '[Test Get Variation Lis] Check if a valid return for simple product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['grouped']);
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_product, '_getVariationList'),
            '[Test Get Variation Lis] Check if a valid return for grouped product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['virtual']);
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_product, '_getVariationList'),
            '[Test Get Variation Lis] Check if a valid return for virtual product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['downloadable']);
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_product, '_getVariationList'),
            '[Test Get Variation Lis] Check if a valid return for downloadable product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type', '_product'], ['configurable', $productMock]);
        $this->assertEquals(
            'color, size',
            $fixture->invokeMethod($this->_product, '_getVariationList'),
            '[Test Get Variation Lis] Check if a valid return for configurable product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type', '_parentProduct'], ['simple', $parentProductMock]);
        $this->assertEquals(
            'color, size, depth',
            $fixture->invokeMethod($this->_product, '_getVariationList'),
            '[Test Get Variation Lis] Check if a valid return for child product'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::_getChildrenIds
     */
    public function testGetChildrenIds()
    {
        $fixture = New Fixture();
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
        $fixture->setPrivatePropertyValue($this->_product, ['_type', '_product'], ['simple', $productMock]);
        $this->assertInternalType(
            'array',
            $fixture->invokeMethod($this->_product, '_getChildrenIds'),
            '[Test Get Children Ids] Check if return is a array'
        );
        $this->assertEquals(
            [],
            $fixture->invokeMethod($this->_product, '_getChildrenIds'),
            '[Test Get Children Ids] Check if a valid return for simple product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type', '_product'], ['configurable', $productMock]);
        $this->assertEquals(
            [],
            $fixture->invokeMethod($this->_product, '_getChildrenIds'),
            '[Test Get Children Ids] Check if a valid return for configurable product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type', '_product'], ['virtual', $productMock]);
        $this->assertEquals(
            [],
            $fixture->invokeMethod($this->_product, '_getChildrenIds'),
            '[Test Get Children Ids] Check if a valid return for virtual product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type', '_product'], ['downloadable', $productMock]);
        $this->assertEquals(
            [],
            $fixture->invokeMethod($this->_product, '_getChildrenIds'),
            '[Test Get Children Ids] Check if a valid return for downloadable product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type', '_product'], ['grouped', $productMock]);
        $this->assertEquals(
            [1, 2, 3],
            $fixture->invokeMethod($this->_product, '_getChildrenIds'),
            '[Test Get Children Ids] Check if a valid return for grouped product'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::_getQuantity
     */
    public function testGetQuantity()
    {
        $fixture = New Fixture();
        $classMock = $fixture->getFakeClass();
        $storeMock = $fixture->mockFunctions($this->_store, ['getId'], [1]);
        $productMock = $fixture->mockFunctions($this->_product, ['getId'], [5]);
        $stockItemMock = $fixture->mockFunctions($classMock, ['getQty'], ['10']);
        $stockRegistryMock = $fixture->mockFunctions($classMock, ['getStockItem'], [$stockItemMock]);
        $fixture->setPrivatePropertyValue(
            $this->_product,
            ['_type', '_product', '_store', '_stockRegistry', '_childrenIds'],
            ['simple', $productMock, $storeMock, $stockRegistryMock, [1, 2, 3]]
        );
        $this->assertInternalType(
            'integer',
            $fixture->invokeMethod($this->_product, '_getQuantity'),
            '[Test Get Quantity] Check if return is a integer'
        );
        $this->assertEquals(
            10,
            $fixture->invokeMethod($this->_product, '_getQuantity'),
            '[Test Get Quantity] Check if a valid return for simple product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['configurable']);
        $this->assertEquals(
            10,
            $fixture->invokeMethod($this->_product, '_getQuantity'),
            '[Test Get Quantity] Check if a valid return for configurable product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['virtual']);
        $this->assertEquals(
            10,
            $fixture->invokeMethod($this->_product, '_getQuantity'),
            '[Test Get Quantity] Check if a valid return for virtual product'
        );
        $fixture->setPrivatePropertyValue($this->_product, ['_type'], ['downloadable']);
        $this->assertEquals(
            10,
            $fixture->invokeMethod($this->_product, '_getQuantity'),
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
            ['_type', '_stockRegistry'],
            ['grouped', $stockRegistryMock2]
        );
        $this->assertEquals(
            5,
            $fixture->invokeMethod($this->_product, '_getQuantity'),
            '[Test Get Quantity] Check if a valid return for grouped product'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::_getAttributeValue
     */
    public function testGetAttributeValue()
    {
        $fixture = New Fixture();
        $productMock = $fixture->mockFunctions($this->_product, ['getData'], [null]);
        $fixture->setPrivatePropertyValue($this->_product, ['_product'], [$productMock]);
        $this->assertEquals(
            null,
            $fixture->invokeMethod($this->_product, '_getAttributeValue', ['plop']),
            '[Test Get Attribute Value] Check if a valid return when attribute value is null'
        );
        $productMock2 = $fixture->mockFunctions($this->_product, ['getData'], [['array', 'plop', 'value']]);
        $fixture->setPrivatePropertyValue($this->_product, ['_product'], [$productMock2]);
        $this->assertEquals(
            'array,plop,value',
            $fixture->invokeMethod($this->_product, '_getAttributeValue', ['plop']),
            '[Test Get Attribute Value] Check if a valid return when attribute value is null'
        );
        $classMock = $fixture->getFakeClass();
        $frontendMock = $fixture->mockFunctions($classMock, ['getValue'], ['front plop']);
        $getAttributeMock = $fixture->mockFunctions($classMock, ['getFrontend'], [$frontendMock]);
        $getResourceMock = $fixture->mockFunctions($classMock, ['getAttribute'], [$getAttributeMock]);
        $productMock = $fixture->mockFunctions($this->_product, ['getData', 'getResource'], ['plop', $getResourceMock]);
        $fixture->setPrivatePropertyValue($this->_product, ['_product'], [$productMock]);
        $this->assertEquals(
            'front plop',
            $fixture->invokeMethod($this->_product, '_getAttributeValue', ['plop']),
            '[Test Get Attribute Value] Check if a valid return when attribute value is null'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Product::_getGroupedPricesAndDiscounts
     */
    public function testGetGroupedPricesAndDiscounts()
    {
        $fixture = New Fixture();
        $classMock = $fixture->getFakeClass();
        $fixture->setPrivatePropertyValue($this->_product, ['_childrenIds'], [[]]);
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
                    'discount_end_date' => ''
                ]
            ],
            $fixture->invokeMethod($this->_product, '_getGroupedPricesAndDiscounts'),
            '[Test Get Grouped Prices And Discount] Check if return is valid when children ids is empty'
        );
        $productMockClass = $fixture->mockFunctions($this->_product, ['_getProduct'], ['plop']);
        $priceMock = $fixture->mockFunctions(
            $classMock,
            ['load', 'clean', 'getPrices', 'getDiscounts'],
            [
                true,
                true,
                [
                    'price_excl_tax' => 20,
                    'price_incl_tax' => 24,
                    'price_before_discount_excl_tax' => 20,
                    'price_before_discount_incl_tax' => 24
                ],
                [
                    'discount_amount' => 0,
                    'discount_percent' => 0,
                    'discount_start_date' => '',
                    'discount_end_date' => ''
                ]
            ]
        );
        $fixture->setPrivatePropertyValue($productMockClass, ['_childrenIds', '_price'], [[10, 11], $priceMock]);
        $this->assertEquals(
            [
                'prices' => [
                    'price_excl_tax' => 40,
                    'price_incl_tax' => 48,
                    'price_before_discount_excl_tax' => 40,
                    'price_before_discount_incl_tax' => 48,
                ],
                'discounts' => [
                    'discount_amount' => 0,
                    'discount_percent' => 0,
                    'discount_start_date' => '',
                    'discount_end_date' => ''
                ]
            ],
            $fixture->invokeMethod($productMockClass, '_getGroupedPricesAndDiscounts'),
            '[Test Get Grouped Prices And Discount] Check if return is valid with children ids and without discount'
        );
        $priceMock2 = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['load', 'clean', 'getPrices', 'getDiscounts'])
            ->disableOriginalConstructor()
            ->getMock();
        $priceMock2->expects($this->any())->method('load')->will($this->returnValue(true));
        $priceMock2->expects($this->any())->method('clean')->will($this->returnValue(true));
        $priceMock2->expects($this->any())->method('getPrices')->willReturnOnConsecutiveCalls(
            [
                'price_excl_tax'                 => 15,
                'price_incl_tax'                 => 18,
                'price_before_discount_excl_tax' => 20,
                'price_before_discount_incl_tax' => 24
            ],
            [
                'price_excl_tax'                 => 100,
                'price_incl_tax'                 => 120,
                'price_before_discount_excl_tax' => 100,
                'price_before_discount_incl_tax' => 120
            ]
        );
        $priceMock2->expects($this->any())->method('getDiscounts')->willReturnOnConsecutiveCalls(
            [
                'discount_amount'     => 5,
                'discount_percent'    => 20,
                'discount_start_date' => '2017-03-01 00:00:00',
                'discount_end_date'   => '2017-03-31 23:59:59'
            ],
            [
                'discount_amount'     => 0,
                'discount_percent'    => 0,
                'discount_start_date' => '',
                'discount_end_date'   => ''
            ]
        );
        $dateTimeMock = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['gmtTimestamp', 'date'])
            ->disableOriginalConstructor()
            ->getMock();
        $dateTimeMock->expects($this->any())->method('gmtTimestamp')->willReturnOnConsecutiveCalls(
            '1488322800',
            '1490997599'
        );
        $dateTimeMock->expects($this->any())->method('date')->willReturnOnConsecutiveCalls(
            '2017-03-01 00:00:00',
            '2017-03-31 23:59:59'
        );
        $fixture->setPrivatePropertyValue($productMockClass, ['_price', '_dateTime'], [$priceMock2, $dateTimeMock]);
        $this->assertEquals(
            [
                'prices' => [
                    'price_excl_tax'                 => 115,
                    'price_incl_tax'                 => 138,
                    'price_before_discount_excl_tax' => 120,
                    'price_before_discount_incl_tax' => 144,
                ],
                'discounts' => [
                    'discount_amount'     => 6,
                    'discount_percent'    => '4.17',
                    'discount_start_date' => '2017-03-01 00:00:00',
                    'discount_end_date'   => '2017-03-31 23:59:59'
                ]
            ],
            $fixture->invokeMethod($productMockClass, '_getGroupedPricesAndDiscounts'),
            '[Test Get Grouped Prices And Discount] Check if return is valid with children ids and one discount'
        );
        $priceMock3 = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['load', 'clean', 'getPrices', 'getDiscounts'])
            ->disableOriginalConstructor()
            ->getMock();
        $priceMock3->expects($this->any())->method('load')->will($this->returnValue(true));
        $priceMock3->expects($this->any())->method('clean')->will($this->returnValue(true));
        $priceMock3->expects($this->any())->method('getPrices')->willReturnOnConsecutiveCalls(
            [
                'price_excl_tax'                 => 15,
                'price_incl_tax'                 => 18,
                'price_before_discount_excl_tax' => 20,
                'price_before_discount_incl_tax' => 24
            ],
            [
                'price_excl_tax'                 => 80,
                'price_incl_tax'                 => 96,
                'price_before_discount_excl_tax' => 100,
                'price_before_discount_incl_tax' => 120
            ]
        );
        $priceMock3->expects($this->any())->method('getDiscounts')->willReturnOnConsecutiveCalls(
            [
                'discount_amount'     => 5,
                'discount_percent'    => 20,
                'discount_start_date' => '2017-03-01 00:00:00',
                'discount_end_date'   => '2017-03-31 23:59:59'
            ],
            [
                'discount_amount'     => 24,
                'discount_percent'    => 20,
                'discount_start_date' => '2017-02-20 00:00:00',
                'discount_end_date'   => '2017-03-20 23:59:59'
            ]
        );
        $dateTimeMock2 = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['gmtTimestamp', 'date'])
            ->disableOriginalConstructor()
            ->getMock();
        $dateTimeMock2->expects($this->any())->method('gmtTimestamp')->willReturnOnConsecutiveCalls(
            '1488322800',
            '1490997599',
            '1487545200',
            '1490050799'
        );
        $dateTimeMock2->expects($this->any())->method('date')->willReturnOnConsecutiveCalls(
            '2017-03-01 00:00:00',
            '2017-03-20 23:59:59'
        );
        $fixture->setPrivatePropertyValue($productMockClass, ['_price', '_dateTime'], [$priceMock3, $dateTimeMock2]);
        $this->assertEquals(
            [
                'prices' => [
                    'price_excl_tax'                 => 95,
                    'price_incl_tax'                 => 114,
                    'price_before_discount_excl_tax' => 120,
                    'price_before_discount_incl_tax' => 144,
                ],
                'discounts' => [
                    'discount_amount'     => 30,
                    'discount_percent'    => '20.83',
                    'discount_start_date' => '2017-03-01 00:00:00',
                    'discount_end_date'   => '2017-03-20 23:59:59'
                ]
            ],
            $fixture->invokeMethod($productMockClass, '_getGroupedPricesAndDiscounts'),
            '[Test Get Grouped Prices And Discount] Check if return is valid with children ids and two discount'
        );
        $priceMock4 = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['load', 'clean', 'getPrices', 'getDiscounts'])
            ->disableOriginalConstructor()
            ->getMock();
        $priceMock4->expects($this->any())->method('load')->will($this->returnValue(true));
        $priceMock4->expects($this->any())->method('clean')->will($this->returnValue(true));
        $priceMock4->expects($this->any())->method('getPrices')->willReturnOnConsecutiveCalls(
            [
                'price_excl_tax'                 => 15,
                'price_incl_tax'                 => 18,
                'price_before_discount_excl_tax' => 20,
                'price_before_discount_incl_tax' => 24
            ],
            [
                'price_excl_tax'                 => 80,
                'price_incl_tax'                 => 96,
                'price_before_discount_excl_tax' => 100,
                'price_before_discount_incl_tax' => 120
            ]
        );
        $priceMock4->expects($this->any())->method('getDiscounts')->willReturnOnConsecutiveCalls(
            [
                'discount_amount'     => 5,
                'discount_percent'    => 20,
                'discount_start_date' => '2017-03-21 00:00:00',
                'discount_end_date'   => '2017-03-31 23:59:59'
            ],
            [
                'discount_amount'     => 24,
                'discount_percent'    => 20,
                'discount_start_date' => '2017-02-20 00:00:00',
                'discount_end_date'   => '2017-03-20 23:59:59'
            ]
        );
        $dateTimeMock3 = $this->getMockBuilder(get_class($classMock))
            ->setMethods(['gmtTimestamp', 'date'])
            ->disableOriginalConstructor()
            ->getMock();
        $dateTimeMock3->expects($this->any())->method('gmtTimestamp')->willReturnOnConsecutiveCalls(
            '1490050800',
            '1490997599',
            '1487545200',
            '1490050799'
        );
        $dateTimeMock3->expects($this->any())->method('date')->willReturnOnConsecutiveCalls(
            '2017-03-20 23:59:59'
        );
        $fixture->setPrivatePropertyValue($productMockClass, ['_price', '_dateTime'], [$priceMock4, $dateTimeMock3]);
        $this->assertEquals(
            [
                'prices' => [
                    'price_excl_tax'                 => 95,
                    'price_incl_tax'                 => 114,
                    'price_before_discount_excl_tax' => 120,
                    'price_before_discount_incl_tax' => 144,
                ],
                'discounts' => [
                    'discount_amount'     => 30,
                    'discount_percent'    => '20.83',
                    'discount_start_date' => '',
                    'discount_end_date'   => '2017-03-20 23:59:59'
                ]
            ],
            $fixture->invokeMethod($productMockClass, '_getGroupedPricesAndDiscounts'),
            '[Test Get Grouped Prices And Discount] Check if return is valid with children ids and two discount'
        );
    }
}
