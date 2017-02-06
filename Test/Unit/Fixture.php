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

namespace Lengow\Connector\Test\Unit;

class Fixture extends \PHPUnit_Framework_TestCase
{
    /**
     * Call protected/private method of a class.
     *
     * @param object &$object Instantiated object that we will run method on
     * @param string $methodName Method name to call
     * @param array $parameters Array of parameters to pass into method
     *
     * @return mixed Method return
     */
    public function invokeMethod(&$object, $methodName, $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Mock specific function
     *
     * @param object &$object Instantiated object that we will run method on
     * @param array $methodNames Method names to call
     * @param array $return Array of parameters to return
     * @param array $constructArgs Args for constructor
     *
     * @return mixed Method return
     */
    public function mockFunctions($object, $methodNames, $return, $constructArgs = [])
    {
        $ii = 0;
        if (count($constructArgs) > 0) {
            $mockFunction = $this->getMockBuilder(get_class($object))
                ->setMethods($methodNames)
                ->setConstructorArgs($constructArgs)
                ->getMock();
        } else {
            $mockFunction = $this->getMockBuilder(get_class($object))
                ->setMethods($methodNames)
                ->disableOriginalConstructor()
                ->getMock();
        }
        foreach ($methodNames as $methodName) {
            $mockFunction->expects($this->any())->method($methodName)->will($this->returnValue($return[$ii]));
            $ii++;
        }
        return $mockFunction;
    }
}

