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

namespace Lengow\Connector\Test\Unit\Model;

use Lengow\Connector\Model\Connector;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ConnectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Lengow\Connector\Model\Connector
     */
    protected $_connector;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_connector = $objectManager->getObject(Connector::class);
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Connector::class,
            $this->_connector,
            '[Test Class Instantiation] Check class instantiation'
        );
    }
}

