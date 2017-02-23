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

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;

class Lengow extends AbstractCarrier implements CarrierInterface {
    /**
     * @var string Lengow carrier code
     */
    protected $_code = 'lengow';

    /**
     * @var boolean is fixed
     */
    protected $_isFixed = true;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected $_rateMethodFactory;


    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct( $scopeConfig, $rateErrorFactory, $logger, $data );
    }

    /**
     * Get allowed methods
     *
     * @return array
     */
    public function getAllowedMethods() {
        return [ 'lengow' => $this->getConfigData( 'name' ) ];
    }

    /**
     * Get session
     *
     * @return CheckoutSession
     */
    public function getSession()
    {
        return $this->_checkoutSession;
    }

    /**
     * Lengow carrier is active
     *
     * @return boolean
     */
    public function isActive()
    {
        return (bool)$this->_customerSession->getIsFromlengow();
    }

    /**
     * @param RateRequest $request
     *
     * @return bool|Result
     */
    public function collectRates( RateRequest $request ) {
        if ( ! $this->isActive() ) {
            return false;
        }
        $result = $this->_rateResultFactory->create();
        $method = $this->_rateMethodFactory->create();
        $method->setCarrier( 'lengow' );
        $method->setCarrierTitle( $this->getConfigData( 'title' ) );
        $method->setMethod( 'lengow' );
        $method->setMethodTitle( $this->getConfigData( 'name' ) );
        $method->setPrice( $this->getSession()->getShippingPrice() );
        $method->setCost( $this->getSession()->getShippingPrice() );
        $result->append( $method );
        return $result;
    }

    /**
     * Check if carrier has shipping tracking option available
     *
     * @return boolean
     * @api
     */
    public function isTrackingAvailable() {
        return false;
    }
}
