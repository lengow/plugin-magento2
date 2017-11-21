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
use Lengow\Connector\Model\Import\OrderFactory;

class Dashboard extends Template
{
    /**
     * @var \Lengow\Connector\Helper\Sync Lengow sync helper instance
     */
    protected $_syncHelper;

    /**
     * @var \Lengow\Connector\Model\Import\OrderFactory Lengow order instance
     */
    protected $_lengowOrderFactory;

    /**
     * @var array Lengow statistics
     */
    protected $_stats = [];

    /**
     * @var integer number of Lengow order to be sent
     */
    protected $_numberOrderToBeSent;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context Magento block context instance
     * @param array $data additional params
     * @param \Lengow\Connector\Helper\Sync $syncHelper Lengow sync helper instance
     * @param \Lengow\Connector\Model\Import\OrderFactory $lengowOrderFactory Lengow order instance
     */
    public function __construct(
        Context $context,
        array $data = [],
        SyncHelper $syncHelper,
        OrderFactory $lengowOrderFactory
    ) {
        parent::__construct($context, $data);
        $this->_syncHelper = $syncHelper;
        $this->_lengowOrderFactory = $lengowOrderFactory;
        if (!$this->_syncHelper->pluginIsBlocked()) {
            $this->_stats = $this->_syncHelper->getStatistic();
        }
        $this->_numberOrderToBeSent = $this->_lengowOrderFactory->create()->countOrderToBeSent();
    }

    /**
     * Get total order
     *
     * @return integer
     */
    public function getNumberOrder()
    {
        return isset($this->_stats['nb_order']) ? (int)$this->_stats['nb_order']  : 0;
    }

    /**
     * Get turnover
     *
     * @return string
     */
    public function getTurnover()
    {
        return isset($this->_stats['total_order']) ? $this->_stats['total_order'] : '';
    }

    /**
     * Get statistics is available
     *
     * @return boolean
     */
    public function statIsAvailable()
    {
        return isset($this->_stats['available']) ? (bool)$this->_stats['available'] : false;
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
