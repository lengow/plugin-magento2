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

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Customer\Model\CustomerFactory as MagentoCustomerFactory;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\ResourceModel\Customer as MagentoResourceCustomer;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
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
    /* Country iso codes */
    public const ISO_A2_FR = 'FR';
    public const ISO_A2_ES = 'ES';
    public const ISO_A2_IT = 'IT';

    /**
     * @var CustomerRepositoryInterface Magento customer repository instance
     */
    protected $_customerRepository;

    /**
     * @var MagentoCustomerFactory Magento customer factory instance
     */
    protected $_customerFactory;

    /**
     * @var AddressFactory Magento address factory instance
     */
    protected $_addressFactory;

    /**
     * @var RegionCollectionFactory Magento region collection factory instance
     */
    protected $_regionCollectionFactory;

    /**
     * @var EncryptorInterface encryption interface instance
     */
    protected $_encryptor;

    /**
     * @var Random Magento math random instance
     */
    protected $_mathRandom;

    /**
     * @var StoreManagerInterface Magento store manager instance
     */
    protected $_storeManager;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var array current alias of mister
     */
    private $currentMale = [
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
        'sir',
    ];

    /**
     * @var array current alias of miss
     */
    private $currentFemale = [
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
     * @var array All region codes for correspondence
     */
    private $regionCodes = [
        self::ISO_A2_ES => [
            '01' => 'Alava',
            '02' => 'Albacete',
            '03' => 'Alicante',
            '04' => 'Almeria',
            '05' => 'Avila',
            '06' => 'Badajoz',
            '07' => 'Baleares',
            '08' => 'Barcelona',
            '09' => 'Burgos',
            '10' => 'Caceres',
            '11' => 'Cadiz',
            '12' => 'Castellon',
            '13' => 'Ciudad Real',
            '14' => 'Cordoba',
            '15' => 'A Coruсa',
            '16' => 'Cuenca',
            '17' => 'Girona',
            '18' => 'Granada',
            '19' => 'Guadalajara',
            '20' => 'Guipuzcoa',
            '21' => 'Huelva',
            '22' => 'Huesca',
            '23' => 'Jaen',
            '24' => 'Leon',
            '25' => 'Lleida',
            '26' => 'La Rioja',
            '27' => 'Lugo',
            '28' => 'Madrid',
            '29' => 'Malaga',
            '30' => 'Murcia',
            '31' => 'Navarra',
            '32' => 'Ourense',
            '33' => 'Asturias',
            '34' => 'Palencia',
            '35' => 'Las Palmas',
            '36' => 'Pontevedra',
            '37' => 'Salamanca',
            '38' => 'Santa Cruz de Tenerife',
            '39' => 'Cantabria',
            '40' => 'Segovia',
            '41' => 'Sevilla',
            '42' => 'Soria',
            '43' => 'Tarragona',
            '44' => 'Teruel',
            '45' => 'Toledo',
            '46' => 'Valencia',
            '47' => 'Valladolid',
            '48' => 'Vizcaya',
            '49' => 'Zamora',
            '50' => 'Zaragoza',
            '51' => 'Ceuta',
            '52' => 'Melilla',
        ],
        self::ISO_A2_IT => [
            '00' => 'RM',
            '01' => 'VT',
            '02' => 'RI',
            '03' => 'FR',
            '04' => 'LT',
            '05' => 'TR',
            '06' => 'PG',
            '07' => [
                '07000-07019' => 'SS',
                '07020-07029' => 'OT',
                '07030-07049' => 'SS',
                '07050-07999' => 'SS',
            ],
            '08' => [
                '08000-08010' => 'OR',
                '08011-08012' => 'NU',
                '08013-08013' => 'OR',
                '08014-08018' => 'NU',
                '08019-08019' => 'OR',
                '08020-08020' => 'OT',
                '08021-08029' => 'NU',
                '08030-08030' => 'OR',
                '08031-08032' => 'NU',
                '08033-08033' => 'CA',
                '08034-08034' => 'OR',
                '08035-08035' => 'CA',
                '08036-08039' => 'NU',
                '08040-08042' => 'OG',
                '08043-08043' => 'CA',
                '08044-08049' => 'OG',
                '08050-08999' => 'NU',
            ],
            '09' => [
                '09000-09009' => 'CA',
                '09010-09017' => 'CI',
                '09018-09019' => 'CA',
                '09020-09041' => 'VS',
                '09042-09069' => 'CA',
                '09070-09099' => 'OR',
                '09100-09169' => 'CA',
                '09170-09170' => 'OR',
                '09171-09999' => 'CA',
            ],
            '10' => 'TO',
            '11' => 'AO',
            '12' => [
                '12000-12070' => 'CN',
                '12071-12071' => 'SV',
                '12072-12999' => 'CN',
            ],
            '13' => [
                '13000-13799' => 'VC',
                '13800-13999' => 'BI',
            ],
            '14' => 'AT',
            '15' => 'AL',
            '16' => 'GE',
            '17' => 'SV',
            '18' => [
                '18000-18024' => 'IM',
                '18025-18025' => 'CN',
                '18026-18999' => 'IM',
            ],
            '19' => 'SP',
            '20' => [
                '20000-20799' => 'MI',
                '20800-20999' => 'MB',
            ],
            '21' => 'VA',
            '22' => 'CO',
            '23' => [
                '23000-23799' => 'SO',
                '23800-23999' => 'LC',
            ],
            '24' => 'BG',
            '25' => 'BS',
            '26' => [
                '26000-26799' => 'CR',
                '26800-26999' => 'LO',
            ],
            '27' => 'PV',
            '28' => [
                '28000-28799' => 'NO',
                '28800-28999' => 'VB',
            ],
            '29' => 'PC',
            '30' => 'VE',
            '31' => 'TV',
            '32' => 'BL',
            '33' => [
                '33000-33069' => 'UD',
                '33070-33099' => 'PN',
                '33100-33169' => 'UD',
                '33170-33999' => 'PN',
            ],
            '34' => [
                '34000-34069' => 'TS',
                '34070-34099' => 'GO',
                '34100-34169' => 'TS',
                '34170-34999' => 'GO',
            ],
            '35' => 'PD',
            '36' => 'VI',
            '37' => 'VR',
            '38' => 'TN',
            '39' => 'BZ',
            '40' => 'BO',
            '41' => 'MO',
            '42' => 'RE',
            '43' => 'PR',
            '44' => 'FE',
            '45' => 'RO',
            '46' => 'MN',
            '47' => [
                '47000-47799' => 'FC',
                '47800-47999' => 'RN',
            ],
            '48' => 'RA',
            '50' => 'FI',
            '51' => 'PT',
            '52' => 'AR',
            '53' => 'SI',
            '54' => 'MS',
            '55' => 'LU',
            '56' => 'PI',
            '57' => 'LI',
            '58' => 'GR',
            '59' => 'PO',
            '60' => 'AN',
            '61' => 'PU',
            '62' => 'MC',
            '63' => [
                '63000-63799' => 'AP',
                '63800-63999' => 'FM',
            ],
            '64' => 'TE',
            '65' => 'PE',
            '66' => 'CH',
            '67' => 'AQ',
            '70' => 'BA',
            '71' => 'FG',
            '72' => 'BR',
            '73' => 'LE',
            '74' => 'TA',
            '75' => 'MT',
            '76' => 'BT',
            '80' => 'NA',
            '81' => 'CE',
            '82' => 'BN',
            '83' => 'AV',
            '84' => 'SA',
            '85' => 'PZ',
            '86' => [
                '86000-86069' => 'CB',
                '86070-86099' => 'IS',
                '86100-86169' => 'CB',
                '86170-86999' => 'IS',
            ],
            '87' => 'CS',
            '88' => [
                '88000-88799' => 'CZ',
                '88800-88999' => 'KR',
            ],
            '89' => [
                '89000-89799' => 'RC',
                '89800-89999' => 'VV',
            ],
            '90' => 'PA',
            '91' => 'TP',
            '92' => 'AG',
            '93' => 'CL',
            '94' => 'EN',
            '95' => 'CT',
            '96' => 'SR',
            '97' => 'RG',
            '98' => 'ME',
        ],
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
     * @param StoreManagerInterface $storeManager Magento store manager instance
     * @param MagentoCustomerFactory $customerFactory Magento customer factory instance
     * @param AddressFactory $addressFactory Magento address factory instance
     * @param CustomerRepositoryInterface $customerRepository Magento customer repository instance
     * @param RegionCollectionFactory $regionCollectionFactory Magento region collection instance
     * @param Random $mathRandom Magento math random instance
     * @param EncryptorInterface $encryptor encryption interface instance
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
        RegionCollectionFactory $regionCollectionFactory,
        Random $mathRandom,
        EncryptorInterface $encryptor
    ) {
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->_storeManager = $storeManager;
        $this->_customerFactory = $customerFactory;
        $this->_addressFactory = $addressFactory;
        $this->_customerRepository = $customerRepository;
        $this->_regionCollectionFactory = $regionCollectionFactory;
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
     * @return MagentoCustomer
     *
     * @throws Exception
     */
    public function createCustomer(
        $orderData,
        $shippingAddress,
        int $storeId,
        string $marketplaceSku,
        bool $logOutput
    ): MagentoCustomer {

        if ($this->configHelper->get(ConfigHelper::IMPORT_ANONYMIZED_EMAIL, $storeId)) {
            // generation of fictitious email
            $customerEmail = hash('sha256', $marketplaceSku . '-' . $orderData->marketplace) . '@lengow.com';
        } else {
            // get customer email
            $customerEmail = $orderData->billing_address->email;
        }
        $this->dataHelper->log(
            DataHelper::CODE_IMPORT,
            $this->dataHelper->setLogMessage('generate a unique email %1', [$customerEmail]),
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
        if (!$shippingAddress->getId()) {
            $customer->addAddress($shippingAddress);
        }
        $customer->save();
        return $customer;
    }

    /**
     * Update customer vat number
     *
     * @param string $customerEmail
     * @param int $storeId
     * @param type $billingData
     * @return type
     */
    public function updateCustomerVatNumber(
        string $customerEmail,
        int $storeId,
        string $vatNumber): MagentoCustomer
    {
        $idWebsite = $this->_storeManager->getStore($storeId)->getWebsiteId();
        // first get by email
        $customer = $this->_customerFactory->create();
        $customer->setWebsiteId($idWebsite);
        $customer->loadByEmail($customerEmail);


        if ($customer && $customer->getId()) {
            $customer->setTaxvat((string) $vatNumber)
                ->save();
            $billingAddress = $customer->getDefaultBillingAddress();
            if ($billingAddress && $billingAddress->getId()) {
                $billingAddress->setVatId((string) $vatNumber)
                ->save();
            }
        }
        
        return $customer;

    }

    /**
     * Create or load customer based on API data
     *
     * @param string $customerEmail fictitious customer email or customer email
     * @param integer $storeId Magento store id
     * @param object $billingData billing address data
     *
     * @return MagentoCustomer
     *
     * @throws Exception
     */
    private function getOrCreateCustomer(string $customerEmail, int $storeId, $billingData): MagentoCustomer
    {
        $idWebsite = $this->_storeManager->getStore($storeId)->getWebsiteId();
        // first get by email
        $customer = $this->_customerFactory->create();
        $customer->setWebsiteId($idWebsite);
        $customer->loadByEmail($customerEmail);
        // add the client id group after uploading by mail because the data is all reset
        $customer->setGroupId($this->configHelper->get(ConfigHelper::SYNCHRONISATION_CUSTOMER_GROUP, $storeId));
        // create new subscriber without send a confirmation email
        if (!$customer->getId()) {
            $customerNames = $this->getNames($billingData);
            $customer->setImportMode(true);
            $customer->setWebsiteId($idWebsite);
            $customer->setCompany((string) $billingData->company);
            $customer->setLastname($customerNames['lastName']);
            $customer->setFirstname($customerNames['firstName']);
            $customer->setEmail($customerEmail);
            $customer->setTaxvat((string) $billingData->vat_number);
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
     * @return string
     *
     * @throws Exception
     */
    private function generatePassword(int $length = 8): string
    {
        $chars = Random::CHARS_LOWERS . Random::CHARS_UPPERS . Random::CHARS_DIGITS;
        return $this->_mathRandom->getRandomString($length, $chars);
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
    private function getOrCreateAddress(
        MagentoCustomer $customer,
        $addressData,
        bool $isShippingAddress = false
    ): Address {
        $names = $this->getNames($addressData);
        $street = $this->getAddressStreet($addressData, $isShippingAddress);
        $postcode = (string) $addressData->zipcode;
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
            $address->setCompany((string) $addressData->company);
            $address->setFirstname($names['firstName']);
            $address->setLastname($names['lastName']);
            $address->setStreet($street);
            $address->setPostcode($postcode);
            $address->setCity($city);
            $address->setCountryId((string) $addressData->common_country_iso_a2);
            $phoneNumbers = $this->getPhoneNumbers($addressData);
            $address->setTelephone($phoneNumbers['phone']);
            $address->setFax($phoneNumbers['secondPhone']);
            $address->setVatId((string) $addressData->vat_number);
            // get region id by postcode or state region
            $regionId = false;
            if (in_array($address->getCountry(), [self::ISO_A2_FR, self::ISO_A2_ES, self::ISO_A2_IT])) {
                $regionId = $this->searchRegionIdByPostcode($address->getCountry(), $postcode);
            } elseif ($addressData->state_region !== null) {
                $regionId = $this->searchRegionIdByStateRegion($address->getCountry(), $addressData->state_region);
            }
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
    private function addressIsAlreadyCreated(
        Address $defaultAddress,
        array $names,
        string $street,
        string $postcode,
        string $city
    ): bool {
        $firstName = $names['firstName'] ?? '';
        $lastName = $names['lastName'] ?? '';
        $defaultAddressStreet = is_array($defaultAddress->getStreet())
            ? implode("\n", $defaultAddress->getStreet())
            : $defaultAddress->getStreet();
        return $defaultAddressStreet === $street
            && $defaultAddress->getFirstname() === $firstName
            && $defaultAddress->getLastname() === $lastName
            && $defaultAddress->getPostcode() === $postcode
            && $defaultAddress->getCity() === $city;
    }

    /**
     * Check if first name or last name are empty
     *
     * @param object $addressData API address data
     *
     * @return array
     */
    private function getNames($addressData): array
    {
        $names = [
            'firstName' => trim((string) $addressData->first_name),
            'lastName' => trim((string) $addressData->last_name),
            'fullName' => $this->cleanFullName((string) $addressData->full_name),
        ];
        if (empty($names['lastName']) && empty($names['firstName'])) {
            $names = $this->splitNames((string) $names['fullName']);
        } elseif (empty($names['firstName'])) {
            $names = $this->splitNames((string) $names['lastName']);
        } elseif (empty($names['lastName'])) {
            $names = $this->splitNames((string) $names['firstName']);
        }
        unset($names['fullName']);
        $names['firstName'] = !empty($names['firstName']) ? ucfirst(strtolower((string) $names['firstName'])) : '__';
        $names['lastName'] = !empty($names['lastName']) ? ucfirst(strtolower((string) $names['lastName'])) : '__';
        return $names;
    }

    /**
     * Clean full name field without salutation
     *
     * @param string|null $fullName full name of the customer
     *
     * @return string
     */
    private function cleanFullName(string $fullName = null): string
    {
        $split = explode(' ', $fullName ?? '');
        if (!empty($split)) {
            $fullName = (in_array($split[0], $this->currentMale, true)
                || in_array($split[0], $this->currentFemale, true)
            ) ? '' : $split[0];
            $countSplit = count($split);
            for ($i = 1; $i < $countSplit; $i++) {
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
    private function splitNames(string $fullName): array
    {
        $split = explode(' ', $fullName ?? '');
        if (!empty($split)) {
            $names['firstName'] = $split[0];
            $names['lastName'] = '';
            $countSplit = count($split);
            for ($i = 1; $i < $countSplit; $i++) {
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
    private function getAddressStreet($addressData, bool $isShippingAddress = false): string
    {
        $street = trim((string)$addressData->first_line);
        $secondLine = trim((string)$addressData->second_line);
        $complement = trim((string)$addressData->complement);
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
    private function getPhoneNumbers($addressData): array
    {
        $phoneHome = $addressData->phone_home;
        $phoneMobile = $addressData->phone_mobile;
        $phoneOffice = $addressData->phone_office;
        if (empty($phoneHome)) {
            if (!empty($phoneMobile)) {
                $phoneHome = $phoneMobile;
                $phoneMobile = $phoneOffice ?: '';
            } elseif (!empty($phoneOffice)) {
                $phoneHome = $phoneOffice;
            }
        } elseif (empty($phoneMobile) && !empty($phoneOffice)) {
            $phoneMobile = $phoneOffice;
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
     * @param string|null $phoneNumber phone number to clean
     *
     * @return string
     */
    private function cleanPhoneNumber(string $phoneNumber = null): string
    {
        if (!$phoneNumber) {
            return '';
        }
        return preg_replace('/[^0-9]*/', '', $phoneNumber);
    }

    /**
     * Search Magento region id by postcode for specific countries
     *
     * @param string $countryIsoA2 country iso A2
     * @param string $postcode address postcode
     *
     * @return string|false
     */
    private function searchRegionIdByPostcode(string $countryIsoA2, string $postcode)
    {
        $regionId = false;
        $postcodeSubstr = substr(str_pad($postcode, 5, '0', STR_PAD_LEFT), 0, 2);
        switch ($countryIsoA2) {
            case self::ISO_A2_FR:
                $regionCode = ltrim($postcodeSubstr, '0');
                break;
            case self::ISO_A2_ES:
                $regionCode = $this->regionCodes[$countryIsoA2][$postcodeSubstr] ?? false;
                break;
            case self::ISO_A2_IT:
                $regionCode = $this->regionCodes[$countryIsoA2][$postcodeSubstr] ?? false;
                if (is_array($regionCode) && !empty($regionCode)) {
                    $regionCode = $this->getRegionCodeFromIntervalPostcodes((int) $postcode, $regionCode);
                }
                break;
            default:
                $regionCode = false;
                break;
        }
        if ($regionCode) {
            $regionId = $this->_regionCollectionFactory->create()
                ->addRegionCodeFilter($regionCode)
                ->addCountryFilter($countryIsoA2)
                ->getFirstItem()
                ->getId();
        }
        return $regionId;
    }

    /**
     * Get region code from interval postcodes
     *
     * @param integer $postcode address postcode
     * @param array $intervalPostcodes postcode intervals
     *
     * @return string|false
     */
    private function getRegionCodeFromIntervalPostcodes(int $postcode, array $intervalPostcodes)
    {
        foreach ($intervalPostcodes as $intervalPostcode => $regionCode) {
            $intervalPostcodes = explode('-', $intervalPostcode);
            if (!empty($intervalPostcodes) && count($intervalPostcodes) === 2) {
                $minPostcode = is_numeric($intervalPostcodes[0]) ? (int) $intervalPostcodes[0] : false;
                $maxPostcode = is_numeric($intervalPostcodes[1]) ? (int) $intervalPostcodes[1] : false;
                if (($minPostcode && $maxPostcode) && ($postcode >= $minPostcode && $postcode <= $maxPostcode)) {
                    return $regionCode;
                }
            }
        }
        return false;
    }

    /**
     * Search Magento region id by state return by api
     *
     * @param string $countryIsoA2 country iso A2
     * @param string $stateRegion state region return by api
     *
     * @return string|false
     */
    private function searchRegionIdByStateRegion(string $countryIsoA2, string $stateRegion)
    {
        $regionId = false;
        $regionCollection = $this->_regionCollectionFactory->create()
            ->addCountryFilter($countryIsoA2)
            ->getData();
        $stateRegionCleaned = $this->cleanString($stateRegion);
        if (!empty($regionCollection) && !empty($stateRegion)) {
            // strict search on the region code
            foreach ($regionCollection as $region) {
                $regionCodeCleaned = $this->cleanString($region['code']);
                if ($stateRegionCleaned === $regionCodeCleaned) {
                    $regionId = $region['region_id'];
                    break;
                }
            }
            // approximate search on the default region name
            if (!$regionId) {
                $results = [];
                foreach ($regionCollection as $region) {
                    $nameCleaned = $this->cleanString($region['default_name']);
                    similar_text($stateRegionCleaned, $nameCleaned, $percent);
                    if ($percent > 70) {
                        $results[(int) $percent] = $region['region_id'];
                    }
                }
                if (!empty($results)) {
                    krsort($results);
                    $regionId = current($results);
                }
            }
        }
        return $regionId;
    }

    /**
     * Cleaning a string before search
     *
     * @param string $string string to clean
     *
     * @return string
     */
    private function cleanString(string $string): string
    {
        $string = strtolower(str_replace([' ', '-', '_', '.'], '', trim($string)));
        return $this->dataHelper->replaceAccentedChars(html_entity_decode($string));
    }
}
