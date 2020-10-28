<?php
/**
 * Copyright 2020 Lengow SAS
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
 * @subpackage  Observer
 * @author      Team module <team-module@lengow.com>
 * @copyright   2020 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Observer;

use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class B2bTaxesApllicator implements ObserverInterface
{
    /**
     * @var BackendSession $_backendSession Backend session instance
     */
    protected $backendSession;

    /**
     * B2bTaxesApllicator constructor
     *
     * @param BackendSession $backendSession Backend session instance
     */
    public function __construct(
        BackendSession $backendSession
    ) {
        $this->backendSession = $backendSession;
    }

    /**
     * Remove tax class from each products if the order is B2B
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ((bool)$this->backendSession->getIsFromlengow() && $this->backendSession->getIsLengowB2b() === 1) {
            $items = $observer->getEvent()->getQuote()->getAllVisibleItems();
            foreach ($items as $item) {
                $item->getProduct()->setTaxClassId(0);
            }
            $this->backendSession->setIsLengowB2b(0);
        }
    }
}
