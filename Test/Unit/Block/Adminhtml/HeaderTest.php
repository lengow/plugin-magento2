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
use Lengow\Connector\Block\Adminhtml\Header;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class HeaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Lengow\Connector\Block\Adminhtml\Header
     */
    protected $_header;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    public function setUp() : void
    {
        $objectManager = new ObjectManager($this);
        $contextMock = $this->createMock(\Magento\Backend\Block\Template\Context::class);


        $this->_header = $objectManager->getObject(Header::class, ['context' => $contextMock]);
    }

    public function testClassInstantiation()
    {
        $this->assertInstanceOf(
            Header::class,
            $this->_header,
            '[Test Class Instantiation] Check class instantiation'
        );
    }

    /**
     * @covers \Lengow\Connector\Block\Adminhtml\Header::freeTrialIsEnabled
     */
    public function testFreeTrialIsEnabled()
    {
        $fixture = new Fixture();
        $this->assertIsBool(
            $this->_header->freeTrialIsEnabled(),
            '[Test Free Trial Is Enabled] Check if return is a boolean'
        );
        $this->assertFalse(
            $this->_header->freeTrialIsEnabled(),
            '[Test Free Trial Is Enabled] Check if return is valid when status account is empty'
        );
        $fixture->setPrivatePropertyValue(
            $this->_header,
            ['statusAccount'],
            [['type' => 'free_trial', 'expired' => false]]
        );
        $this->assertTrue(
            $this->_header->freeTrialIsEnabled(),
            '[Test Free Trial Is Enabled] Check if return is valid when free trial is not expired'
        );
        $fixture->setPrivatePropertyValue(
            $this->_header,
            ['statusAccount'],
            [['type' => 'free_trial', 'expired' => true]]
        );
        $this->assertFalse(
            $this->_header->freeTrialIsEnabled(),
            '[Test Free Trial Is Enabled] Check if return is valid when free trial is expired'
        );
        $fixture->setPrivatePropertyValue(
            $this->_header,
            ['statusAccount'],
            [['type' => '', 'expired' => false]]
        );
        $this->assertFalse(
            $this->_header->freeTrialIsEnabled(),
            '[Test Free Trial Is Enabled] Check if return is valid when type is unknown and expired is false'
        );
        $fixture->setPrivatePropertyValue(
            $this->_header,
            ['statusAccount'],
            [['type' => '', 'expired' => true]]
        );
        $this->assertFalse(
            $this->_header->freeTrialIsEnabled(),
            '[Test Free Trial Is Enabled] Check if return is valid when type is unknown and expired is true'
        );
    }

    /**
     * @covers \Lengow\Connector\Block\Adminhtml\Header::getFreeTrialDays
     */
    public function testGetFreeTrialDays()
    {
        $fixture = new Fixture();
        $this->assertIsInt(
            $this->_header->getFreeTrialDays(),
            '[Test Get Free Trial Days] Check if return is a integer'
        );
        $this->assertEquals(
            0,
            $this->_header->getFreeTrialDays(),
            '[Test Get Free Trial Days] Check if return is valid when status account is empty'
        );
        $fixture->setPrivatePropertyValue($this->_header, ['statusAccount'], [['day' => 12]]);
        $this->assertEquals(
            12,
            $this->_header->getFreeTrialDays(),
            '[Test Get Free Trial Days] Check if return is valid'
        );
    }
}
