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
 * @subpackage  Plugin
 * @author      Team module <team-module@lengow.com>
 * @copyright   2020 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Plugin;

use Closure;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Model\RulesApplier as RulesApplierModel;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;

/*
 * Class RulesApplier
 * this class is used to prevent magento from applying the discounts
 * to the orders imported by Lengow
 */
class RulesApplier
{
    /**
     * @var BackendSession Backend session instance
     */
    private $backendSession;

    /**
     * @var RuleCollectionFactory Magento Rule Factory
     */
    private $ruleFactory;

    /**
     * @param RuleCollectionFactory $rulesFactory Magento Rules Factory
     * @param BackendSession $backendSession Backend session instance
     */
    public function __construct(
        RuleCollectionFactory $rulesFactory,
        BackendSession $backendSession
    ) {
        $this->ruleFactory = $rulesFactory;
        $this->backendSession = $backendSession;
    }

    /**
     * This method is executed each time magento call his own ApplyRule method
     * It allows lengow orders to be imported without any Cart Rules
     *
     * @param  RulesApplierModel  $subject  Magento RulesApplier base class
     * @param  Closure  $proceed  Callable (have to be called otherwise magento prevent the execution of the next plugins)
     * @param  AbstractItem  $item  Magento Abstract Item representing a Quote
     * @param  array  $rules  Magento Rule Collection assigned to the Quote
     * @param  bool  $skipValidation  Magento option to skip rule validation
     * @param  mixed  $couponCode  Magento Coupon Code
     *
     * @return mixed
     */
    public function aroundApplyRules(
        RulesApplierModel $subject,
        Closure $proceed,
        AbstractItem $item,
        array $rules,
        bool $skipValidation,
        $couponCode
    ) {
        if ($this->backendSession->getIsFromlengow()) {
            $rules = $this->ruleFactory->create()->addFieldToFilter('rule_id', ['eq' => 0]);
        }
        return $proceed($item, $rules, $skipValidation, $couponCode);
    }
}
