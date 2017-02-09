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

use Lengow\Connector\Helper\Data;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class DataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Lengow\Connector\Helper\Data
     */
    protected $_dataHelper;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_dataHelper = $objectManager->getObject(Data::class);
    }

    public function testClassInstance()
    {
        $this->assertInstanceOf(
            Data::class,
            $this->_dataHelper,
            '[Test Class Instance] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Helper\Data::setLogMessage
     */
    public function testSetLogMessage()
    {
        $this->assertInternalType(
            'string',
            $this->_dataHelper->setLogMessage('Check setLogMessage without parameter'),
            '[Test Get All Customer Group] Check if return is a string'
        );
        $this->assertEquals(
            $this->_dataHelper->setLogMessage('Check setLogMessage without parameter'),
            'Check setLogMessage without parameter',
            '[Test Set Log Message] Check simple message without parameters'
        );
        $this->assertEquals(
            $this->_dataHelper->setLogMessage('Check setLogMessage %1 many %2', ['with', 'parameters']),
            'Check setLogMessage %1 many %2[with|parameters]',
            '[Test Set Log Message] Check message with many parameters'
        );
        $this->assertNotEquals(
            $this->_dataHelper->setLogMessage('Check setLogMessage without parameter'),
            'Check setLogMessage without parameter[with|parameters]',
            '[Test Set Log Message] Check when a message contain a array without parameters'
        );
        $this->assertNotEquals(
            $this->_dataHelper->setLogMessage('Check setLogMessage %1 many %2', ['with', 'parameters']),
            'Check setLogMessage %1 many %2',
            '[Test Set Log Message] Check when a message does not contain a array with parameters'
        );
    }

    /**
     * @covers \Lengow\Connector\Helper\Data::decodeLogMessage
     */
    public function testDecodeLogMessage()
    {
        $this->assertInternalType(
            'string',
            $this->_dataHelper->decodeLogMessage('Check decodeLogMessage [ with special character'),
            '[Test Set Log Message] Check if return is a string'
        );
        $this->assertEquals(
            $this->_dataHelper->decodeLogMessage('Check decodeLogMessage [ with special character'),
            'Check decodeLogMessage [ with special character',
            '[Test Set Log Message] Check decodeLogMessage with special character'
        );
        $this->assertEquals(
            $this->_dataHelper->decodeLogMessage('Check decodeLogMessage without parameter'),
            __('Check decodeLogMessage without parameter'),
            '[Test Set Log Message] Check decodeLogMessage without parameter'
        );
        $this->assertEquals(
            $this->_dataHelper->decodeLogMessage('Check decodeLogMessage %1 many %2[with|parameters]'),
            __('Check decodeLogMessage %1 many %2', ['with','parameters']),
            '[Test Set Log Message] Check decodeLogMessage with many parameters'
        );
        $this->assertEquals(
            $this->_dataHelper->decodeLogMessage('Check decodeLogMessage %1 many %2', true, ['with', 'parameters']),
            __('Check decodeLogMessage %1 many %2', ['with','parameters']),
            '[Test Set Log Message] Check decodeLogMessage with force parameters'
        );
        $this->assertEquals(
            $this->_dataHelper->decodeLogMessage('Check decodeLogMessage without parameter', false),
            'Check decodeLogMessage without parameter',
            '[Test Set Log Message] Check decodeLogMessage without parameter and no Magento translation'
        );
        $this->assertEquals(
            $this->_dataHelper->decodeLogMessage('Check decodeLogMessage %1 many %2[with|parameters]', false),
            'Check decodeLogMessage with many parameters',
            '[Test Set Log Message] Check decodeLogMessage with parameters and no Magento translation'
        );
        $this->assertEquals(
            $this->_dataHelper->decodeLogMessage('Check decodeLogMessage %1 many %2', false, ['with', 'parameters']),
            'Check decodeLogMessage with many parameters',
            '[Test Set Log Message] Check decodeLogMessage with force parameters and no Magento translation'
        );
    }
}
