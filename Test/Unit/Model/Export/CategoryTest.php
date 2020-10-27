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
use Lengow\Connector\Model\Export\Category;
use Lengow\Connector\Test\Unit\Fixture;

class CategoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Lengow\Connector\Model\Export\Category
     */
    protected $_category;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_category = $objectManager->getObject(Category::class);
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Category::class,
            $this->_category,
            '[Test Class Instantiation] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Category::getCategoryBreadcrumb
     */
    public function testGetCategoryBreadcrumb()
    {
        $fixture = new Fixture();
        $fixture->setPrivatePropertyValue(
            $this->_category,
            ['_categoryBreadcrumb'],
            ['Default Category > Men > Tops > Hoodies & Sweatshirts']
        );
        $this->assertInternalType(
            'string',
            $this->_category->getCategoryBreadcrumb(),
            '[Test Get Variation List] Check if return is a string'
        );
        $this->assertEquals(
            'Default Category > Men > Tops > Hoodies & Sweatshirts',
            $this->_category->getCategoryBreadcrumb(),
            '[Test Get Shipping Cost] Check if return is valid'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Category::clean
     */
    public function testClean()
    {
        $fixture = new Fixture();
        $fixture->setPrivatePropertyValue(
            $this->_category,
            ['_product', '_categoryBreadcrumb'],
            ['product', 'Default Category > Men > Tops > Hoodies & Sweatshirts']
        );
        $this->_category->clean();
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_category, '_product'),
            '[Test Clean] Check if _product attribute is null'
        );
        $this->assertNull(
            $fixture->getPrivatePropertyValue($this->_category, '_categoryBreadcrumb'),
            '[Test Clean] Check if _product attribute is null'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Category::_getDefaultCategory
     */
    public function testGetDefaultCategory()
    {
        $fixture = new Fixture();
        $classMock = $fixture->getFakeClass();
        $pathFilterMock = $fixture->mockFunctions($classMock, ['exportToArray'], [[]]);
        $categoryCollectionMock = $fixture->mockFunctions($classMock, ['addPathsFilter'], [$pathFilterMock]);
        $productMock = $fixture->mockFunctions($classMock, ['getCategoryCollection'], [$categoryCollectionMock]);
        $storeMock = $fixture->mockFunctions($classMock, ['getRootCategoryId'], [0]);
        $fixture->setPrivatePropertyValue($this->_category, ['_store', '_product'], [$storeMock, $productMock]);
        $this->assertInternalType(
            'array',
            $fixture->invokeMethod($this->_category, '_getDefaultCategory'),
            '[Test Get Default Category] Check if return is a array'
        );
        $this->assertEquals(
            ['id' => 0, 'path' => ''],
            $fixture->invokeMethod($this->_category, '_getDefaultCategory'),
            '[Test Get Default Category] Check return when category collection is null'
        );
        $pathFilterMock2 = $fixture->mockFunctions(
            $classMock,
            ['exportToArray'],
            [
                [
                    ['entity_id' => '5', 'level' => '5', 'path' => '1/2/3/4/5'],
                    ['entity_id' => '3', 'level' => '3', 'path' => '1/2/3'],
                    ['entity_id' => '2', 'level' => '2', 'path' => '1/2'],
                    ['entity_id' => '4', 'level' => '4', 'path' => '1/2/3/4'],
                ],
            ]
        );
        $categoryCollectionMock2 = $fixture->mockFunctions($classMock, ['addPathsFilter'], [$pathFilterMock2]);
        $productMock2 = $fixture->mockFunctions($classMock, ['getCategoryCollection'], [$categoryCollectionMock2]);
        $fixture->setPrivatePropertyValue($this->_category, ['_product'], [$productMock2]);
        $this->assertEquals(
            ['id' => 5, 'path' => '1/2/3/4/5'],
            $fixture->invokeMethod($this->_category, '_getDefaultCategory'),
            '[Test Get Default Category] Check return is a valid array'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Category::_getBreadcrumb
     */
    public function testGetBreadcrumb()
    {
        $fixture = new Fixture();
        $categoryMock = $this->getMockBuilder(get_class($this->_category))
            ->setMethods(['_getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $categoryMock->expects($this->any())->method('_getName')->willReturnOnConsecutiveCalls(
            'Default Category',
            'Men',
            'Tops',
            'Hoodies & Sweatshirts'
        );
        $this->assertInternalType(
            'string',
            $fixture->invokeMethod($categoryMock, '_getBreadcrumb', [0, '1/2/3/4/5']),
            '[Test Get Variation List] Check if return is a string'
        );
        $this->assertEquals(
            '',
            $fixture->invokeMethod($categoryMock, '_getBreadcrumb', [0, '1/2/3/4/5']),
            '[Test Get Name] Check return when category id is equal 0'
        );
        $this->assertEquals(
            '',
            $fixture->invokeMethod($categoryMock, '_getBreadcrumb', [10, '']),
            '[Test Get Name] Check return when category path is empty'
        );
        $this->assertEquals(
            'Default Category > Men > Tops > Hoodies & Sweatshirts',
            $fixture->invokeMethod($categoryMock, '_getBreadcrumb', [10, '1/2/3/4/5']),
            '[Test Get Name] Check return when is valid without cache'
        );
        $this->assertEquals(
            [10 => 'Default Category > Men > Tops > Hoodies & Sweatshirts'],
            $fixture->getPrivatePropertyValue($categoryMock, '_cacheCategoryBreadcrumbs'),
            '[Test Get Name] Check cache category breadcrumb'
        );
        $categoryMock2 = $this->getMockBuilder(get_class($this->_category))
            ->setMethods(['_getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $categoryMock2->expects($this->any())->method('_getName')->will($this->returnValue(null));
        $fixture->setPrivatePropertyValue(
            $categoryMock2,
            ['_cacheCategoryBreadcrumbs'],
            [[15 => 'Default Category > Men > Tops > Hoodies & Sweatshirts']]
        );
        $this->assertEquals(
            'Default Category > Men > Tops > Hoodies & Sweatshirts',
            $fixture->invokeMethod($categoryMock2, '_getBreadcrumb', [15, '1/2/3/4/5']),
            '[Test Get Name] Check return with cache category breadcrumb'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Export\Category::_getName
     */
    public function testGetName()
    {
        $fixture = new Fixture();
        $classMock = $fixture->getFakeClass();
        $storeMock = $fixture->mockFunctions($classMock, ['getId'], [1]);
        $categoryMock = $fixture->mockFunctions($classMock, ['getName'], ['Hoodies & Sweatshirts']);
        $categoryRepositoryMock = $fixture->mockFunctions($classMock, ['get'], [$categoryMock]);
        $fixture->setPrivatePropertyValue(
            $this->_category,
            ['_store', '_categoryRepository'],
            [$storeMock, $categoryRepositoryMock]
        );
        $this->assertInternalType(
            'string',
            $fixture->invokeMethod($this->_category, '_getName', [0]),
            '[Test Get Variation List] Check if return is a string'
        );
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_category, '_getName', [0]),
            '[Test Get Name] Check return when category is equal 0'
        );
        $this->assertEquals(
            'Hoodies & Sweatshirts',
            $fixture->invokeMethod($this->_category, '_getName', [10]),
            '[Test Get Name] Check return without cache category name'
        );
        $this->assertEquals(
            [10 => 'Hoodies & Sweatshirts'],
            $fixture->getPrivatePropertyValue($this->_category, '_cacheCategoryNames'),
            '[Test Get Name] Check cache category name'
        );
        $categoryRepositoryMock2 = $fixture->mockFunctions($classMock, ['get'], [null]);
        $fixture->setPrivatePropertyValue(
            $this->_category,
            ['_cacheCategoryNames', '_categoryRepository'],
            [[15 => 'Tops'], $categoryRepositoryMock2]
        );
        $this->assertEquals(
            'Tops',
            $fixture->invokeMethod($this->_category, '_getName', [15]),
            '[Test Get Name] Check return with cache category name'
        );
    }
}
