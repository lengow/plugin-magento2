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

namespace Lengow\Connector\Test\Unit\Block\Adminhtml;

use Lengow\Connector\Test\Unit\Fixture;
use Lengow\Connector\Block\Adminhtml\Main;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class MainTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Lengow\Connector\Block\Adminhtml\Main
     */
    protected $_main;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp() : void
    {
        $objectManager = new ObjectManager($this);
        $contextMock = $this->createMock(\Magento\Backend\Block\Template\Context::class);
        $this->_main = $objectManager->getObject(Main::class, ['context' => $contextMock]);
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Main::class,
            $this->_main,
            '[Test Class Instantiation] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Block\Adminhtml\Main::freeTrialIsExpired
     */
    public function testFreeTrialIsExpired()
    {
        $fixture = new Fixture();
        $this->assertIsBool(
            $this->_main->freeTrialIsExpired(),
            '[Test Free Trial Is Expired] Check if return is a boolean'
        );
        $this->assertFalse(
            $this->_main->freeTrialIsExpired(),
            '[Test Free Trial Is Expired] Check if return is valid when status account is empty'
        );
        $fixture->setPrivatePropertyValue(
            $this->_main,
            ['statusAccount'],
            [['type' => 'free_trial', 'expired' => false]]
        );
        $this->assertFalse(
            $this->_main->freeTrialIsExpired(),
            '[Test Free Trial Is Expired] Check if return is valid when free trial is not expired'
        );
        $fixture->setPrivatePropertyValue(
            $this->_main,
            ['statusAccount'],
            [['type' => 'free_trial', 'expired' => true]]
        );
        $this->assertTrue(
            $this->_main->freeTrialIsExpired(),
            '[Test Free Trial Is Expired] Check if return is valid when free trial is expired'
        );
        $fixture->setPrivatePropertyValue(
            $this->_main,
            ['statusAccount'],
            [['type' => '', 'expired' => true]]
        );
        $this->assertFalse(
            $this->_main->freeTrialIsExpired(),
            '[Test Free Trial Is Expired] Check if return is valid when type is unknown and expired is true'
        );
    }
}
