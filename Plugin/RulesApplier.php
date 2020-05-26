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
 * @subpackage  Model
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Plugin;

use Magento\Framework\Session\SessionManager;
use Magento\Backend\Model\Session as BackendSession;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;


/*
 * Class RulesApplier
 * this class is used to prevent magento from applying the discounts
 * to the orders imported by Lengow
 */
class RulesApplier
{
    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection
     */
    private $rules;

    /**
     * @var BackendSession $_backendSession Backend session instance
     */
    protected $_backendSession;

    /**
     * @param BackendSession $backendSession Backend session instance
     * @param CollectionFactory $rulesFactory Magento Rules Factory
     */
    public function __construct(
        CollectionFactory $rulesFactory,
        BackendSession $backendSession
    ) {
        $this->ruleCollection = $rulesFactory;
        $this->_backendSession = $backendSession;
    }

    /**
     * @param \Magento\SalesRule\Model\RulesApplier $subject
     * @param \Closure $proceed
     * @param $item
     * @param $rules
     * @param $skipValidation
     * @param $couponCode
     *
     * @return mixed
     */
    public function aroundApplyRules(
        \Magento\SalesRule\Model\RulesApplier $subject,
        \Closure $proceed,
        $item,
        $rules,
        $skipValidation,
        $couponCode
    ) {
        if ((bool)$this->_backendSession->getIsFromlengow()) {
            $nRules = $this->ruleCollection->create()->addFieldToFilter('rule_id', ['eq'=>0]);
            return $proceed($item, $nRules, $skipValidation, $couponCode);
        }
        return $proceed($item, $rules, $skipValidation, $couponCode);
    }
}