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

use Lengow\Connector\Model\Config\Source\Tracking;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class TrackingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Lengow\Connector\Model\Config\Source\Tracking
     */
    protected $_tracking;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_tracking = $objectManager->getObject(Tracking::class);
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Tracking::class,
            $this->_tracking,
            '[Test Class Instantiation] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Model\Config\Source\Tracking::toOptionArray
     */
    public function testToOptionArray()
    {
        $mockTrackings = [
            ['value' => 'sku', 'label' => __('Sku')],
            ['value' => 'entity_id', 'label' => __('ID product')]
        ];
        $trackingOptions = $this->_tracking->toOptionArray();
        $this->assertInternalType(
            'array',
            $trackingOptions,
            '[Test To Option Array] Check if return is a array'
        );
        $this->assertEquals(
            $trackingOptions,
            $mockTrackings,
            '[Test To Option Array] Check if return is valid'
        );
    }
}

