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
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Lengow\Connector\Model\Export\Feed;
use Lengow\Connector\Test\Unit\Fixture;
use Lengow\Connector\Helper\Data as DataHelper;

class FeedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Lengow\Connector\Model\Export\Feed
     */
    protected $_feed;

    /**
     * @var \Lengow\Connector\Helper\Data
     */
    protected $_dataHelper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_feed = $objectManager->getObject(Feed::class);
        $this->_dataHelper = $objectManager->getObject(DataHelper::class);
        $this->_jsonHelper = $objectManager->getObject(JsonHelper::class);
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Feed::class,
            $this->_feed,
            '[Test Class Instantiation] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Feed::_getHtmlHeader
     */
    public function testGetHtmlHeader()
    {
        $fixture = New Fixture();
        $fixture->setPrivatePropertyValue($this->_feed, ['_format' ], ['csv']);
        $this->assertInternalType(
            'string',
            $fixture->invokeMethod($this->_feed, '_getHtmlHeader'),
            '[Test Get Html Header] Check if return is a string value'
        );
        $this->assertEquals(
            'Content-Type: text/csv; charset=UTF-8',
            $fixture->invokeMethod($this->_feed, '_getHtmlHeader'),
            '[Test Get Html Header] Check if return is valid for csv format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format' ], ['xml']);
        $this->assertEquals(
            'Content-Type: application/xml; charset=UTF-8',
            $fixture->invokeMethod($this->_feed, '_getHtmlHeader'),
            '[Test Get Html Header] Check if return is valid for xml format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format' ], ['json']);
        $this->assertEquals(
            'Content-Type: application/json; charset=UTF-8',
            $fixture->invokeMethod($this->_feed, '_getHtmlHeader'),
            '[Test Get Html Header] Check if return is valid for json format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format' ], ['yaml']);
        $this->assertEquals(
            'Content-Type: text/x-yaml; charset=UTF-8',
            $fixture->invokeMethod($this->_feed, '_getHtmlHeader'),
            '[Test Get Html Header] Check if return is valid for yaml format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format' ], ['plop']);
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_feed, '_getHtmlHeader'),
            '[Test Get Html Header] Check if return is valid for fake format'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Feed::_getHeader
     */
    public function testGetHeader()
    {
        $fixture = New Fixture();
        $dataMock = ['id', 'sku', 'name', 'child_name', 'quantity', 'status'];
        $fixture->setPrivatePropertyValue($this->_feed, ['_dataHelper'], [$this->_dataHelper]);
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['csv']);
        $this->assertInternalType(
            'string',
            $fixture->invokeMethod($this->_feed, '_getHeader', [$dataMock]),
            '[Test Get Header] Check if return is a string value'
        );
        $this->assertEquals(
            '"id"|"sku"|"name"|"child_name"|"quantity"|"status"'.Feed::EOL,
            $fixture->invokeMethod($this->_feed, '_getHeader', [$dataMock]),
            '[Test Get Header] Check if return is valid for csv format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['xml']);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>'.Feed::EOL.'<catalog>'.Feed::EOL,
            $fixture->invokeMethod($this->_feed, '_getHeader', [$dataMock]),
            '[Test Get Header] Check if return is valid for xml format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['json']);
        $this->assertEquals(
            '{"catalog":[',
            $fixture->invokeMethod($this->_feed, '_getHeader', [$dataMock]),
            '[Test Get Header] Check if return is valid for json format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['yaml']);
        $this->assertEquals(
            '"catalog":'.Feed::EOL,
            $fixture->invokeMethod($this->_feed, '_getHeader', [$dataMock]),
            '[Test Get Header] Check if return is valid for yaml format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['plop']);
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_feed, '_getHeader', [$dataMock]),
            '[Test Get Header] Check if return is valid for fake format'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Feed::_getFooter
     */
    public function testGetFooter()
    {
        $fixture = New Fixture();
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['csv']);
        $this->assertInternalType(
            'string',
            $fixture->invokeMethod($this->_feed, '_getFooter'),
            '[Test Get Footer] Check if return is a string value'
        );
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_feed, '_getFooter'),
            '[Test Get Footer] Check if return is valid for csv format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['xml']);
        $this->assertEquals(
            '</catalog>',
            $fixture->invokeMethod($this->_feed, '_getFooter'),
            '[Test Get Footer] Check if return is valid for xml format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['json']);
        $this->assertEquals(
            ']}',
            $fixture->invokeMethod($this->_feed, '_getFooter'),
            '[Test Get Footer] Check if return is valid for json format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['yaml']);
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_feed, '_getFooter'),
            '[Test Get Footer] Check if return is valid for yaml format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['plop']);
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_feed, '_getFooter'),
            '[Test Get Footer] Check if return is valid for fake format'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Feed::_getBody
     */
    public function testGetBody()
    {
        $dataMock = ['id' => '110', 'sku' => 'my sku', 'name' => 'my product'];
        $fixture = New Fixture();
        $fixture->setPrivatePropertyValue($this->_feed, ['_dataHelper'], [$this->_dataHelper]);
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['csv']);
        $this->assertInternalType(
            'string',
            $fixture->invokeMethod($this->_feed, '_getBody', [$dataMock, false, 4]),
            '[Test Get Body] Check if return is a string value'
        );
        $this->assertEquals(
            '"110"|"my sku"|"my product"'.Feed::EOL,
            $fixture->invokeMethod($this->_feed, '_getBody', [$dataMock, false, 4]),
            '[Test Get Body] Check if return is a valid for csv format'
        );

        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['xml']);
        $stringXml = '<product><id><![CDATA[110]]></id>'.Feed::EOL
            .'<sku><![CDATA[my sku]]></sku>'.Feed::EOL
            .'<name><![CDATA[my product]]></name>'.Feed::EOL
            .'</product>'.Feed::EOL;
        $this->assertEquals(
            $stringXml,
            $fixture->invokeMethod($this->_feed, '_getBody', [$dataMock, false, 4]),
            '[Test Get Body] Check if return is a valid for xml format'
        );

        $jsonHelperMock = $fixture->mockFunctions($this->_jsonHelper, ['jsonEncode'], [json_encode($dataMock)]);
        $fixture->setPrivatePropertyValue($this->_feed, ['_jsonHelper'], [$jsonHelperMock]);
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['json']);
        $this->assertEquals(
            ',{"id":"110","sku":"my sku","name":"my product"}',
            $fixture->invokeMethod($this->_feed, '_getBody', [$dataMock, false, 4]),
            '[Test Get Body] Check if return is a valid for json format'
        );
        $this->assertEquals(
            '{"id":"110","sku":"my sku","name":"my product"}',
            $fixture->invokeMethod($this->_feed, '_getBody', [$dataMock, true, 4]),
            '[Test Get Body] Check if return is a valid for json format when is a first product'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['yaml']);
        $stringYaml = '  "product":'.Feed::EOL
            .'    "id":    110'.Feed::EOL
            .'    "sku":   my sku'.Feed::EOL
            .'    "name":  my product'.Feed::EOL;
        $this->assertEquals(
            $stringYaml,
            $fixture->invokeMethod($this->_feed, '_getBody', [$dataMock, false, 4]),
            '[Test Get Body] Check if return is a valid for yaml format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['plop']);
        $this->assertEquals(
            '',
            $fixture->invokeMethod($this->_feed, '_getBody', [$dataMock, false, 4]),
            '[Test Get Body] Check if return is a valid for fake format'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Feed::_formatFields
     */
    public function testFormatFields()
    {
        $fixture = New Fixture();
        $fixture->setPrivatePropertyValue($this->_feed, ['_dataHelper'], [$this->_dataHelper]);
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['csv']);
        $string = "price_before_DISCOUNT_excl_tax for_test'with_more_58_characters";
        $this->assertInternalType(
            'string',
            $fixture->invokeMethod($this->_feed, '_formatFields', [$string]),
            '[Test Format Fields] Check if return is a string value'
        );
        $this->assertEquals(
            'price_before_discount_excl_tax_for_test_with_more_58_chara',
            $fixture->invokeMethod($this->_feed, '_formatFields', [$string]),
            '[Test Format Fields] Check if return is valid for csv format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['xml']);
        $this->assertEquals(
            'price_before_discount_excl_tax_for_test_with_more_58_characters',
            $fixture->invokeMethod($this->_feed, '_formatFields', [$string]),
            '[Test Format Fields] Check if return is valid for xml format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['json']);
        $this->assertEquals(
            'price_before_discount_excl_tax_for_test_with_more_58_characters',
            $fixture->invokeMethod($this->_feed, '_formatFields', [$string]),
            '[Test Format Fields] Check if return is valid for json format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['yaml']);
        $this->assertEquals(
            'price_before_discount_excl_tax_for_test_with_more_58_characters',
            $fixture->invokeMethod($this->_feed, '_formatFields', [$string]),
            '[Test Format Fields] Check if return is valid for yaml format'
        );
        $fixture->setPrivatePropertyValue($this->_feed, ['_format'], ['plop']);
        $this->assertEquals(
            'price_before_discount_excl_tax_for_test_with_more_58_characters',
            $fixture->invokeMethod($this->_feed, '_formatFields', [$string]),
            '[Test Format Fields] Check if return is valid for fake format'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Feed::_indentYaml
     */
    public function testIndentYaml()
    {
        $fixture = New Fixture();;
        $this->assertInternalType(
            'string',
            $fixture->invokeMethod($this->_feed, '_indentYaml', ['test', 10]),
            '[Test Indent Yaml] Check if return is a string value'
        );
        $this->assertEquals(
            '      ',
            $fixture->invokeMethod($this->_feed, '_indentYaml', ['test', 10]),
            '[Test Indent Yaml] Check if return is valid'
        );
    }
}
