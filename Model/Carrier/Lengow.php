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

namespace Lengow\Connector\Model\Carrier;

use Magento\Backend\Model\Session as BackendSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;

class Lengow extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var string Lengow carrier code
     * @inherited
     */
    protected $_code = 'lengow';

    /**
     * @var boolean is fixed
     * @inherited
     */
    protected $_isFixed = true;

    /**
     * @var BackendSession Magento customer session instance
     */
    private $backendSession;

    /**
     * @var CheckoutSession Magento checkout session instance
     */
    private $checkoutSession;

    /**
     * @var ResultFactory Magento result factory instance
     */
    private $rateResultFactory;

    /**
     * @var MethodFactory Magento method factory instance
     */
    private $rateMethodFactory;

    /**
     * @param BackendSession $backendSession Magento customer session instance
     * @param CheckoutSession $checkoutSession Magento checkout session instance
     * @param ScopeConfigInterface $scopeConfig Magento scope config instance
     * @param ErrorFactory $rateErrorFactory Magento rate error factory instance
     * @param LoggerInterface $logger Psr Logger interface instance
     * @param ResultFactory $rateResultFactory Magento rate result factory instance
     * @param MethodFactory $rateMethodFactory Magento rate method factory
     * @param array $data
     */
    public function __construct(
        BackendSession $backendSession,
        CheckoutSession $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        $this->backendSession = $backendSession;
        $this->checkoutSession = $checkoutSession;
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Get allowed methods
     *
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return ['lengow' => $this->getConfigData('name')];
    }

    /**
     * Get session
     *
     * @return CheckoutSession
     */
    public function getSession(): CheckoutSession
    {
        return $this->checkoutSession;
    }

    /**
     * Lengow carrier is active
     *
     * @return boolean
     */
    public function isActive(): bool
    {
        return (bool) $this->backendSession->getIsFromlengow();
    }

    /**
     * CollectRates lengow implementation
     *
     * @param RateRequest $request
     *
     * @return bool|Result
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->isActive()) {
            return false;
        }
        $result = $this->rateResultFactory->create();
        $method = $this->rateMethodFactory->create();
        $method->setCarrier('lengow');
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod('lengow');
        $method->setMethodTitle($this->getConfigData('name'));
        $method->setPrice($this->getSession()->getShippingPrice());
        $method->setCost($this->getSession()->getShippingPrice());
        $result->append($method);
        return $result;
    }
}
