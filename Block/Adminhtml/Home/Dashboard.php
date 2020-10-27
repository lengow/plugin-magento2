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
 * @subpackage  Block
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Block\Adminhtml\Home;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Model\Connector as LengowConnector;
use Lengow\Connector\Model\Import\Order as LengowOrder;

class Dashboard extends Template
{
    /**
     * @var SyncHelper Lengow sync helper instance
     */
    protected $_syncHelper;

    /**
     * @var LengowOrder Lengow order instance
     */
    protected $_lengowOrder;

    /**
     * @var integer number of Lengow order to be sent
     */
    protected $_numberOrderToBeSent;

    /**
     * Constructor
     *
     * @param Context $context Magento block context instance
     * @param SyncHelper $syncHelper Lengow sync helper instance
     * @param LengowOrder $lengowOrder Lengow order instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        SyncHelper $syncHelper,
        LengowOrder $lengowOrder,
        array $data = []
    ) {
        $this->_syncHelper = $syncHelper;
        $this->_lengowOrder = $lengowOrder;
        if (!$this->_syncHelper->pluginIsBlocked()) {
            $this->_numberOrderToBeSent = $this->_lengowOrder->countOrderToBeSent();
        }
        parent::__construct($context, $data);
    }

    /**
     * Get Lengow solution url
     *
     * @return string
     */
    public function getLengowSolutionUrl()
    {
        return '//my.' . LengowConnector::LENGOW_URL;
    }

    /**
     * Get number order to be sent
     *
     * @return integer|false
     */
    public function getNumberOrderToBeSent()
    {
        return $this->_numberOrderToBeSent > 0 ? (int)$this->_numberOrderToBeSent : false;
    }
}
