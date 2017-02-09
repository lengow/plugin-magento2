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

namespace Lengow\Connector\Test\Unit\Model\Config\Source;

use Lengow\Connector\Model\Config\Source\Type;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class TypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Lengow\Connector\Model\Config\Source\Type
     */
    protected $_type;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_type = $objectManager->getObject(Type::class);
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Type::class,
            $this->_type,
            '[Test Class Instantiation] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Config\Source\Type::toOptionArray
     */
    public function testToOptionArray()
    {
        $mockTypes = [
            ['value' => 'configurable', 'label' => __('Configurable')],
            ['value' => 'simple', 'label' => __('Simple')],
            ['value' => 'downloadable', 'label' => __('Downloadable')],
            ['value' => 'grouped', 'label' => __('Grouped')],
            ['value' => 'virtual', 'label' => __('Virtual')]
        ];
        $options = $this->_type->toOptionArray();
        $this->assertInternalType(
            'array',
            $options,
            '[Test To Option Array] Check if return is a array'
        );
        $this->assertEquals(
            $options,
            $mockTypes,
            '[Test To Option Array] Check if return is valid'
        );
    }
}
