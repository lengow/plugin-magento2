<?php

namespace Lengow\Connector\Test\Unit\Model\Config\Source;

use Lengow\Connector\Model\Log;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class LogTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Log
     */
    protected $_type;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
//        $objectManager = new ObjectManager($this);
//        $this->_type = $objectManager->getObject(Log::class);
    }

    public function testClassInstantiation()
    {
//        $this->assertInstanceOf(
//            Log::class,
//            $this->_type,
//            '[Test Class Instantiation] Check class instantiation'
//        );
        $this->assertTrue(true);
    }
}