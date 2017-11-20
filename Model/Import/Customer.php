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

namespace Lengow\Connector\Model\Import;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Math\Random;
use Magento\Eav\Model\Entity\Context;
use Magento\Framework\Registry;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\AddressFactory;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\DataObject\Copy;

/**
 * Model import customer
 */
class Customer extends \Magento\Customer\Model\ResourceModel\Customer
{
    /**
     * @var Copy
     */
    protected $_copy;

    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Framework\Math\Random Magento math random
     */
    protected $_mathRandom;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface Magento store manager
     */
    protected $_storeManager;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\Collection Magento region collection
     */
    protected $_regionCollection;

    /**
     * @var \Magento\Customer\Model\AddressFactory Magento address factory
     */
    protected $_addressFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory Magento customer factory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface Magento customer repository
     */
    protected $_customerRepository;

    /**
     * @var array API fields for an address
     */
    protected $_addressApiNodes = [
        'company',
        'civility',
        'email',
        'last_name',
        'first_name',
        'first_line',
        'full_name',
        'second_line',
        'complement',
        'zipcode',
        'city',
        'common_country_iso_a2',
        'phone_home',
        'phone_office',
        'phone_mobile',
    ];

    /**
     * Constructor
     *
     * @param \Magento\Eav\Model\Entity\Context $context Magento context instance
     * @param \Magento\Framework\Registry $registry Magento registry instance
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite $entityRelationComposite
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Validator\Factory $validatorFactory
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Magento store manager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory Magento customer factory
     * @param \Magento\Customer\Model\AddressFactory $addressFactory Magento address factory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface Magento customer repository
     * @param \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection Magento region collection
     * @param \Magento\Framework\Math\Random $mathRandom Magento math random
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor encryption interface
     * @param \Magento\Framework\DataObject\Copy $copyService Magento copy service
     */
    public function __construct(
        Context $context,
        Registry $registry,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite $entityRelationComposite,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Validator\Factory $validatorFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        ConfigHelper $configHelper,
        DataHelper $dataHelper,
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        AddressFactory $addressFactory,
        CustomerRepositoryInterface $customerRepository,
        RegionCollection $regionCollection,
        Random $mathRandom,
        EncryptorInterface $encryptor,
        Copy $copyService
    )
    {
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
        $this->_storeManager = $storeManager;
        $this->_customerFactory = $customerFactory;
        $this->_addressFactory = $addressFactory;
        $this->_customerRepository = $customerRepository;
        $this->_regionCollection = $regionCollection;
        $this->_mathRandom = $mathRandom;
        $this->_encryptor = $encryptor;
        $this->_copy = $copyService;
        parent::__construct(
            $context,
            $entitySnapshot,
            $entityRelationComposite,
            $scopeConfig,
            $validatorFactory,
            $dateTime,
            $storeManager
        );
    }

    /**
     * Convert array to customer model
     *
     * @param object $orderData order data
     * @param array $shippingAddress shipping address data
     * @param integer $storeId Magento store id
     * @param string $marketplaceSku marketplace sku
     * @param boolean $logOutput see log or not
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function createCustomer($orderData, $shippingAddress, $storeId, $marketplaceSku, $logOutput)
    {
        $idWebsite = $this->_storeManager->getStore($storeId)->getWebsiteId();
        $array = [
            'billing_address' => $this->_extractAddressDataFromAPI($orderData->billing_address),
            'delivery_address' => $this->_extractAddressDataFromAPI($shippingAddress)
        ];
        // generation of fictitious email
        $domain = (!$this->_dataHelper->getHost($storeId)
            ? 'magento.shop'
            : $this->_dataHelper->getHost($storeId)
        );

        $array['billing_address']['email'] = $marketplaceSku . '-' . $orderData->marketplace . '@' . $domain;
        $this->_dataHelper->log(
            'Import',
            $this->_dataHelper->setLogMessage(
                'generate a unique email %1',
                [$array['billing_address']['email']]
            ),
            $logOutput,
            $marketplaceSku
        );
        // first get by email
        try {
            $customer = $this->_customerFactory->create();
            $customer->setWebsiteId($idWebsite);
            $customer->loadByEmail($array['billing_address']['email']);
        } catch (NoSuchEntityException $e) {
            // if customer doesn't exist catch exception and set null
            $customer = null;
        }
        // Billing address
        $tempBillingNames = [
            'firstname' => $array['billing_address']['first_name'],
            'lastname' => $array['billing_address']['last_name'],
            'fullname' => $array['billing_address']['full_name']
        ];
        $billingNames = $this->_getNames($tempBillingNames);
        $array['billing_address']['first_name'] = $billingNames['firstname'];
        $array['billing_address']['last_name'] = $billingNames['lastname'];
        $billingAddress = $this->_convertAddress($array['billing_address']);

        if (is_null($customer)) {
            $customer->setImportMode(true);# create new subscriber without send a confirmation email
            $customer->setEmail($array['billing_address']['email']);
            $customer->setFirstName($array['billing_address']['first_name']);
            $customer->setLastName($array['billing_address']['last_name']);

            $customer->setConfirmation(null);
            $customer->setForceConfirmed(true);
            $customer->setPasswordHash($this->_encryptor->getHash($this->generatePassword(), true));
            $customer->addData(['FromLengow' => 1]);
        }
        $billingAddress->setCustomer($customer);
        $customer->addAddress($billingAddress);
        // Shipping address
        $tempShippingNames = [
            'firstname' => $array['delivery_address']['first_name'],
            'lastname' => $array['delivery_address']['last_name'],
            'fullname' => $array['delivery_address']['full_name']
        ];
        $shippingNames = $this->_getNames($tempShippingNames);
        $array['delivery_address']['first_name'] = $shippingNames['firstname'];
        $array['delivery_address']['last_name'] = $shippingNames['lastname'];
        // Get relay id if exist
        if (count($shippingAddress->trackings) > 0
            && isset($shippingAddress->trackings[0]->relay)
            && !is_null($shippingAddress->trackings[0]->relay->id)
        ) {
            $array['delivery_address']['tracking_relay'] = $shippingAddress->trackings[0]->relay->id;
        }
        $shippingAddress = $this->_convertAddress($array['delivery_address'], 'shipping');
        $shippingAddress->setCustomer($customer);
        $customer->addAddress($shippingAddress);
        $this->_copy->copyFieldsetToTarget('lengow_convert_address', 'to_customer', $array['billing_address'], $customer);
        // set group
        $customer->setGroupId($this->_configHelper->get('customer_group', $storeId));

        $customer->save();

        return $customer;
    }

    /**
     * Extract address data from API
     *
     * @param array $api API nodes containing the data
     *
     * @return array
     */
    protected function _extractAddressDataFromAPI($api)
    {
        $temp = [];
        foreach ($this->_addressApiNodes as $node) {
            $temp[$node] = (string)$api->{$node};
        }
        return $temp;
    }

    /**
     * Retrieve random password
     *
     * @param   int $length
     * @return  string
     */
    public function generatePassword($length = 8)
    {
        $chars = Random::CHARS_LOWERS . Random::CHARS_UPPERS . Random::CHARS_DIGITS;

        return $password = $this->_mathRandom->getRandomString($length, $chars);
    }

    /**
     * Check if firstname or lastname are empty
     *
     * @param array $array name and lastname of the customer
     *
     * @return array
     */
    protected function _getNames($array)
    {
        if (empty($array['firstname'])) {
            if (!empty($array['lastname'])) {
                $array = $this->_splitNames($array['lastname']);
            }
        }
        if (empty($array['lastname'])) {
            if (!empty($array['firstname'])) {
                $array = $this->_splitNames($array['firstname']);
            }
        }
        // check full name if last_name and first_name are empty
        if (empty($array['lastname']) && empty($array['firstname'])) {
            $array = $this->_splitNames($array['fullname']);
        }
        if (empty($array['lastname'])) {
            $array['lastname'] = '__';
        }
        if (empty($array['firstname'])) {
            $array['firstname'] = '__';
        }
        return $array;
    }

    /**
     * Split fullname
     *
     * @param string $fullname fullname of the customer
     *
     * @return array
     */
    protected function _splitNames($fullname)
    {
        $split = explode(' ', $fullname);
        if ($split && count($split)) {
            $names['firstname'] = $split[0];
            $names['lastname'] = '';
            for ($i = 1; $i < count($split); $i++) {
                if (!empty($names['lastname'])) {
                    $names['lastname'] .= ' ';
                }
                $names['lastname'] .= $split[$i];
            }
        } else {
            $names['firstname'] = '__';
            $names['lastname'] = empty($fullname) ? '__' : $fullname;
        }
        return $names;
    }

    /**
     * Convert a array to customer address model
     *
     * @param array $data address data
     * @param string $type address type (billing or shipping)
     *
     * @return \Magento\Customer\Model\Address
     */
    protected function _convertAddress(array $data, $type = 'billing')
    {
        $address = $this->_addressFactory->create();
        $address->setId(null);
        $address->setIsDefaultBilling(true);
        $address->setIsDefaultShipping(false);
        if ($type == 'shipping') {
            $address->setIsDefaultBilling(false);
            $address->setIsDefaultShipping(true);
        }
        $this->_copy->copyFieldsetToTarget('lengow_convert_address', 'to_' . $type . '_address', $data, $address);
        $firstLine = $data['first_line'];
        $secondLine = $data['second_line'];
        // Fix first line address
        if (empty($firstLine) && !empty($secondLine)) {
            $firstLine = $secondLine;
            $secondLine = null;
        }
        // Fix second line address
        if (!empty($secondLine)) {
            $firstLine = $firstLine . "\n" . $secondLine;
        }
        $thirdLine = $data['complement'];
        if (!empty($thirdLine)) {
            $firstLine = $firstLine . "\n" . $thirdLine;
        }
        // adding relay to address
        if (isset($data['tracking_relay'])) {
            $firstLine .= ' - Relay : ' . $data['tracking_relay'];
        }
        $address->setStreet($firstLine);
        $phoneOffice = $data['phone_office'];
        $phoneMobile = $data['phone_mobile'];
        $phoneHome = $data['phone_home'];
        $phoneOffice = empty($phoneOffice) ? $phoneMobile : $phoneOffice;
        $phoneOffice = empty($phoneOffice) ? $phoneHome : $phoneOffice;
        if (!empty($phoneOffice)) {
            $address->setTelephone($phoneOffice);
        }
        if (!empty($phoneOffice)) {
            $address->setFax($phoneOffice);
        } else {
            if (!empty($phoneMobile)) {
                $address->setFax($phoneMobile);
            } elseif (!empty($phoneHome)) {
                $address->setFax($phoneHome);
            }
        }
        $codeRegion = substr(str_pad($address->getPostcode(), 5, '0', STR_PAD_LEFT), 0, 2);
        $regionId = $this->_regionCollection
            ->addRegionCodeFilter($codeRegion)
            ->addCountryFilter($address->getCountry())
            ->getFirstItem()
            ->getId();
        $address->setRegionId($regionId);
        return $address;
    }
}
