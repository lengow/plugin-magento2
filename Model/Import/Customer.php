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

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Customer\Model\CustomerFactory as MagentoCustomerFactory;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\ResourceModel\Customer as MagentoResourceCustomer;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Magento\Eav\Model\Entity\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Validator\Factory as ValidatorFactory;
use Magento\Store\Model\StoreManagerInterface;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;

/**
 * Model import customer
 */
class Customer extends MagentoResourceCustomer
{
    /**
     * @var CustomerRepositoryInterface Magento customer repository
     */
    protected $_customerRepository;

    /**
     * @var MagentoCustomerFactory Magento customer factory
     */
    protected $_customerFactory;

    /**
     * @var AddressFactory Magento address factory
     */
    protected $_addressFactory;

    /**
     * @var RegionCollection Magento region collection
     */
    protected $_regionCollection;

    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var Random Magento math random
     */
    protected $_mathRandom;

    /**
     * @var StoreManagerInterface Magento store manager
     */
    protected $_storeManager;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var array current alias of mister
     */
    protected $currentMale = [
        'M',
        'M.',
        'Mr',
        'Mr.',
        'Mister',
        'Monsieur',
        'monsieur',
        'mister',
        'm.',
        'mr ',
    ];

    /**
     * @var array current alias of miss
     */
    protected $currentFemale = [
        'Mme',
        'mme',
        'Mm',
        'mm',
        'Mlle',
        'mlle',
        'Madame',
        'madame',
        'Mademoiselle',
        'madamoiselle',
        'Mrs',
        'mrs',
        'Mrs.',
        'mrs.',
        'Miss',
        'miss',
        'Ms',
        'ms',
    ];

    /**
     * Constructor
     *
     * @param Context $context Magento context instance
     * @param Snapshot $entitySnapshot Magento entity snapshot instance
     * @param RelationComposite $entityRelationComposite Magento entity relation composite instance
     * @param ScopeConfigInterface $scopeConfig Magento scope config instance
     * @param ValidatorFactory $validatorFactory Magento validator factory instance
     * @param DateTime $dateTime Magento date time instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param StoreManagerInterface $storeManager Magento store manager
     * @param MagentoCustomerFactory $customerFactory Magento customer factory
     * @param AddressFactory $addressFactory Magento address factory
     * @param CustomerRepositoryInterface $customerRepository Magento customer repository
     * @param RegionCollection $regionCollection Magento region collection
     * @param Random $mathRandom Magento math random
     * @param EncryptorInterface $encryptor encryption interface
     */
    public function __construct(
        Context $context,
        Snapshot $entitySnapshot,
        RelationComposite $entityRelationComposite,
        ScopeConfigInterface $scopeConfig,
        ValidatorFactory $validatorFactory,
        DateTime $dateTime,
        ConfigHelper $configHelper,
        DataHelper $dataHelper,
        StoreManagerInterface $storeManager,
        MagentoCustomerFactory $customerFactory,
        AddressFactory $addressFactory,
        CustomerRepositoryInterface $customerRepository,
        RegionCollection $regionCollection,
        Random $mathRandom,
        EncryptorInterface $encryptor
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
     * @param object $shippingAddress shipping address data
     * @param integer $storeId Magento store id
     * @param string $marketplaceSku marketplace sku
     * @param boolean $logOutput see log or not
     *
     * @throws \Exception
     *
     * @return MagentoCustomer
     */
    public function createCustomer($orderData, $shippingAddress, $storeId, $marketplaceSku, $logOutput)
    {
        // generation of fictitious email
        $customerEmail = $marketplaceSku . '-' . $orderData->marketplace . '@lengow.com';
        $this->_dataHelper->log(
            DataHelper::CODE_IMPORT,
            $this->_dataHelper->setLogMessage('generate a unique email %1', [$customerEmail]),
            $logOutput,
            $marketplaceSku
        );
        // create or load customer if not exist
        $customer = $this->getOrCreateCustomer($customerEmail, $storeId, $orderData->billing_address);
        // create or load default billing address if not exist
        $billingAddress = $this->getOrCreateAddress($customer, $orderData->billing_address);
        if (!$billingAddress->getId()) {
            $customer->addAddress($billingAddress);
        }
        // create or load default shipping address if not exist
        $shippingAddress = $this->getOrCreateAddress($customer, $shippingAddress, true);
        if (!$billingAddress->getId()) {
            $customer->addAddress($shippingAddress);
        }
        $customer->save();
        return $customer;
    }

    /**
     * Create or load customer based on API data
     *
     * @param string $customerEmail fictitious customer email
     * @param integer $storeId Magento store id
     * @param object $billingData billing address data
     *
     * @throws \Exception
     *
     * @return MagentoCustomer
     */
    private function getOrCreateCustomer($customerEmail, $storeId, $billingData)
    {
        $idWebsite = $this->_storeManager->getStore($storeId)->getWebsiteId();
        // first get by email
        $customer = $this->_customerFactory->create();
        $customer->setWebsiteId($idWebsite);
        $customer->setGroupId($this->_configHelper->get('customer_group', $storeId));
        $customer->loadByEmail($customerEmail);
        // create new subscriber without send a confirmation email
        if (!$customer->getId()) {
            $customerNames = $this->getNames($billingData);
            $customer->setImportMode(true);
            $customer->setWebsiteId($idWebsite);
            $customer->setCompany((string)$billingData->company);
            $customer->setLastname($customerNames['lastName']);
            $customer->setFirstname($customerNames['firstName']);
            $customer->setEmail($customerEmail);
            $customer->setTaxvat((string)$billingData->vat_number);
            $customer->setConfirmation(null);
            $customer->setForceConfirmed(true);
            $customer->setPasswordHash($this->_encryptor->getHash($this->generatePassword(), true));
            $customer->addData(['from_lengow' => true]);
        }
        return $customer;
    }

    /**
     * Retrieve random password
     *
     * @param integer $length length of the password
     *
     * @throws \Exception
     *
     * @return string
     */
    private function generatePassword($length = 8)
    {
        $chars = Random::CHARS_LOWERS . Random::CHARS_UPPERS . Random::CHARS_DIGITS;
        return $password = $this->_mathRandom->getRandomString($length, $chars);
    }

    /**
     * Create or load address based on API data
     *
     * @param MagentoCustomer $customer Magento customer instance
     * @param object $addressData address data
     * @param boolean $isShippingAddress is shipping address
     *
     * @return Address
     */
    private function getOrCreateAddress($customer, $addressData, $isShippingAddress = false)
    {
        $names = $this->getNames($addressData);
        $street = $this->getAddressStreet($addressData, $isShippingAddress);
        $postcode = (string)$addressData->zipcode;
        $city = ucfirst(strtolower(preg_replace('/[!<>?=+@{}_$%]/sim', '', $addressData->city)));
        $defaultAddress = $isShippingAddress
            ? $customer->getDefaultShippingAddress()
            : $customer->getDefaultBillingAddress();
        if (!$defaultAddress || !$this->addressIsAlreadyCreated($defaultAddress, $names, $street, $postcode, $city)) {
            $address = $this->_addressFactory->create();
            $address->setId(null);
            $address->setCustomer($customer);
            $address->setIsDefaultBilling(!$isShippingAddress);
            $address->setIsDefaultShipping($isShippingAddress);
            $address->setCompany((string)$addressData->company);
            $address->setFirstname($names['firstName']);
            $address->setLastname($names['lastName']);
            $address->setStreet($street);
            $address->setPostcode($postcode);
            $address->setCity($city);
            $address->setCountryId((string)$addressData->common_country_iso_a2);
            $phoneNumbers = $this->getPhoneNumbers($addressData);
            $address->setTelephone($phoneNumbers['phone']);
            $address->setFax($phoneNumbers['secondPhone']);
            $address->setVatId((string)$addressData->vat_number);
            $regionId = $this->getMagentoRegionId($address->getCountry(), $postcode);
            if ($regionId) {
                $address->setRegionId($regionId);
            }
        } else {
            $address = $defaultAddress;
        }
        return $address;
    }

    /**
     * Check if address is already created for this customer
     *
     * @param Address $defaultAddress Magento Address instance
     * @param array $names names from Api
     * @param string $street street from Api
     * @param string $postcode postcode from Api
     * @param string $city city from Api
     *
     * @return boolean
     */
    private function addressIsAlreadyCreated($defaultAddress, $names, $street, $postcode, $city)
    {
        $firstName = isset($names['firstName']) ? $names['firstName'] : '';
        $lastName = isset($names['lastName']) ? $names['lastName'] : '';
        $defaultAddressStreet = is_array($defaultAddress->getStreet())
            ? implode("\n", $defaultAddress->getStreet())
            : $defaultAddress->getStreet();
        if ($defaultAddress->getFirstname() === $firstName
            && $defaultAddress->getLastname() === $lastName
            && $defaultAddressStreet === $street
            && $defaultAddress->getPostcode() === $postcode
            && $defaultAddress->getCity() === $city
        ) {
            return true;
        }
        return false;
    }

    /**
     * Check if first name or last name are empty
     *
     * @param object $addressData API address data
     *
     * @return array
     */
    private function getNames($addressData)
    {
        $names = [
            'firstName' => trim($addressData->first_name),
            'lastName' => trim($addressData->last_name),
            'fullName' => $this->cleanFullName($addressData->full_name),
        ];
        if (empty($names['lastName']) && empty($names['firstName'])) {
            $names = $this->splitNames($names['fullName']);
        } else {
            if (empty($names['lastName'])) {
                $names = $this->splitNames($names['lastName']);
            } elseif (empty($names['firstName'])) {
                $names = $this->splitNames($names['firstName']);
            }
        }
        unset($names['fullName']);
        $names['firstName'] = !empty($names['firstName']) ? ucfirst(strtolower($names['firstName'])) : '__';
        $names['lastName'] = !empty($names['lastName']) ? ucfirst(strtolower($names['lastName'])) : '__';
        return $names;
    }

    /**
     * Clean full name field without salutation
     *
     * @param string $fullName full name of the customer
     *
     * @return string
     */
    private function cleanFullName($fullName)
    {
        $split = explode(' ', $fullName);
        if ($split && !empty($split)) {
            $fullName = (in_array($split[0], $this->currentMale) || in_array($split[0], $this->currentFemale))
                ? ''
                : $split[0];
            for ($i = 1; $i < count($split); $i++) {
                if (!empty($fullName)) {
                    $fullName .= ' ';
                }
                $fullName .= $split[$i];
            }
        }
        return $fullName;
    }

    /**
     * Split full name to get first name and last name
     *
     * @param string $fullName full name of the customer
     *
     * @return array
     */
    private function splitNames($fullName)
    {
        $split = explode(' ', $fullName);
        if ($split && !empty($split)) {
            $names['firstName'] = $split[0];
            $names['lastName'] = '';
            for ($i = 1; $i < count($split); $i++) {
                if (!empty($names['lastName'])) {
                    $names['lastName'] .= ' ';
                }
                $names['lastName'] .= $split[$i];
            }
        } else {
            $names = ['firstName' => '', 'lastName' => ''];
        }
        return $names;
    }

    /**
     * Get clean address street
     *
     * @param object $addressData API address data
     * @param boolean $isShippingAddress is shipping address
     *
     * @return string
     */
    private function getAddressStreet($addressData, $isShippingAddress = false)
    {
        $street = trim($addressData->first_line);
        $secondLine = trim($addressData->second_line);
        $complement = trim($addressData->complement);
        if (empty($street)) {
            if (!empty($secondLine)) {
                $street = $secondLine;
                $secondLine = '';
            } elseif (!empty($complement)) {
                $street = $complement;
                $complement = '';
            }
        }
        // get relay id for shipping addresses
        if ($isShippingAddress
            && !empty($addressData->trackings)
            && isset($addressData->trackings[0]->relay)
            && $addressData->trackings[0]->relay->id !== null
        ) {
            $relayId = 'Relay id: ' . $addressData->trackings[0]->relay->id;
            $complement .= !empty($complement) ? ' - ' . $relayId : $relayId;
        }
        if (!empty($secondLine)) {
            $street .= "\n" . $secondLine;
        }
        if (!empty($complement)) {
            $street .= "\n" . $complement;
        }
        return strtolower($street);
    }

    /**
     * Get phone and second phone numbers
     *
     * @param object $addressData API address data
     *
     * @return array
     */
    private function getPhoneNumbers($addressData)
    {
        $phoneHome = $addressData->phone_home;
        $phoneMobile = $addressData->phone_mobile;
        $phoneOffice = $addressData->phone_office;
        if (empty($phoneHome)) {
            if (!empty($phoneMobile)) {
                $phoneHome = $phoneMobile;
                $phoneMobile = $phoneOffice ? $phoneOffice : '';
            } elseif (!empty($phoneOffice)) {
                $phoneHome = $phoneOffice;
            }
        } else {
            if (empty($phoneMobile) && !empty($phoneOffice)) {
                $phoneMobile = $phoneOffice;
            }
        }
        if ($phoneHome === $phoneMobile) {
            $phoneMobile = '';
        }
        return [
            'phone' => !empty($phoneHome) ? $this->cleanPhoneNumber($phoneHome) : '__',
            'secondPhone' => !empty($phoneMobile) ? $this->cleanPhoneNumber($phoneMobile) : '',
        ];
    }

    /**
     * Clean phone number
     *
     * @param string $phoneNumber phone number to clean
     *
     * @return string
     */
    private function cleanPhoneNumber($phoneNumber)
    {
        if (!$phoneNumber) {
            return '';
        }
        return str_replace(['.', ' ', '-', '/'], '', preg_replace('/[^0-9]*/', '', $phoneNumber));
    }

    /**
     * Get Magento region id
     *
     * @param string $country Magento Country
     * @param string $postcode address postcode
     *
     * @return string
     */
    private function getMagentoRegionId($country, $postcode)
    {
        $codeRegion = substr(str_pad($postcode, 5, '0', STR_PAD_LEFT), 0, 2);
        $regionId = $this->_regionCollection
            ->addRegionCodeFilter($codeRegion)
            ->addCountryFilter($country)
            ->getFirstItem()
            ->getId();
        return $regionId;
    }
}
