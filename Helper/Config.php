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
 * @subpackage  Helper
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Magento\Framework\App\Helper\Context;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\Set as AttibuteSet;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigDataCollectionFactory;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;

class Config extends AbstractHelper
{

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface Magento writer instance
     */
    protected $_writerInterface;

    /**
     * @var \Magento\Framework\App\Cache\Manager Magento cache manager instance
     */
    protected $_cacheManager;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory Magento customer group collection factory
     */
    protected $_customerGroupCollectionFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory Magento attribute collection factory
     */
    protected $_attributeCollectionFactory;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory Magento config data collection factory
     */
    protected $_configDataCollectionFactory;

    /**
     * @var \Magento\Store\Model\ResourceModel\Store\CollectionFactory Magento store collection factory
     */
    protected $_storeCollectionFactory;

    /**
     * @var \Magento\Eav\Model\Config Magento eav config
     */
    protected $_eavConfig;

    /**
     * @var array all Lengow options path
     */
    protected $_options = [
        'token' => [
            'path' => 'lengow_global_options/store_credential/token',
            'store' => true,
            'no_cache' => true,
        ],
        'account_id' => [
            'path' => 'lengow_global_options/store_credential/global_account_id',
            'global' => true,
            'no_cache' => true,
        ],
        'access_token' => [
            'path' => 'lengow_global_options/store_credential/global_access_token',
            'global' => true,
            'no_cache' => true,
        ],
        'secret_token' => [
            'path' => 'lengow_global_options/store_credential/global_secret_token',
            'global' => true,
            'no_cache' => true,
        ],
        'authorization_token' => [
            'path' => 'lengow_global_options/store_credential/authorization_token',
            'global' => true,
            'export' => false,
            'no_cache' => true,
        ],
        'last_authorization_token_update' => [
            'path' => 'lengow_global_options/store_credential/last_authorization_token_update',
            'global' => true,
            'export' => false,
            'no_cache' => true,
        ],
        'store_enable' => [
            'path' => 'lengow_global_options/store_credential/global_store_enable',
            'store' => true,
            'no_cache' => true,
        ],
        'catalog_id' => [
            'path' => 'lengow_global_options/store_credential/global_catalog_id',
            'store' => true,
            'no_cache' => true,
        ],
        'tracking_enable' => [
            'path' => 'lengow_global_options/advanced/global_tracking_enable',
            'global' => true,
            'no_cache' => false,
        ],
        'tracking_id' => [
            'path' => 'lengow_global_options/advanced/global_tracking_id',
            'global' => true,
            'no_cache' => false,
        ],
        'ip_enable' => [
            'path' => 'lengow_global_options/advanced/global_authorized_ip_enable',
            'global' => true,
            'no_cache' => false,
        ],
        'authorized_ip' => [
            'path' => 'lengow_global_options/advanced/global_authorized_ip',
            'global' => true,
            'no_cache' => false,
        ],
        'last_status_update' => [
            'path' => 'lengow_global_options/advanced/last_status_update',
            'global' => true,
            'no_cache' => true,
        ],
        'account_status' => [
            'path' => 'lengow_global_options/advanced/account_status',
            'export' => false,
            'no_cache' => true,
        ],
        'last_option_cms_update' => [
            'path' => 'lengow_global_options/advanced/last_option_cms_update',
            'global' => true,
            'no_cache' => true,
        ],
        'last_marketplace_update' => [
            'path' => 'lengow_global_options/advanced/last_marketplace_update',
            'global' => true,
            'no_cache' => true,
        ],
        'last_catalog_update' => [
            'path' => 'lengow_global_options/advanced/last_catalog_update',
            'global' => true,
            'no_cache' => true,
        ],
        'last_setting_update' => [
            'path' => 'lengow_global_options/advanced/last_setting_update',
            'global' => true,
            'no_cache' => true,
        ],
        'selection_enable' => [
            'path' => 'lengow_export_options/simple/export_selection_enable',
            'store' => true,
            'no_cache' => false,
        ],
        'product_type' => [
            'path' => 'lengow_export_options/simple/export_product_type',
            'store' => true,
            'no_cache' => false,
        ],
        'product_status' => [
            'path' => 'lengow_export_options/simple/export_product_status',
            'store' => true,
            'no_cache' => false,
        ],
        'export_attribute' => [
            'path' => 'lengow_export_options/advanced/export_attribute',
            'export' => false,
            'no_cache' => false,
        ],
        'shipping_country' => [
            'path' => 'lengow_export_options/advanced/export_default_shipping_country',
            'store' => true,
            'no_cache' => false,
        ],
        'shipping_method' => [
            'path' => 'lengow_export_options/advanced/export_default_shipping_method',
            'store' => true,
            'no_cache' => false,
        ],
        'shipping_price' => [
            'path' => 'lengow_export_options/advanced/export_default_shipping_price',
            'store' => true,
            'no_cache' => false,
        ],
        'parent_image' => [
            'path' => 'lengow_export_options/advanced/export_parent_image',
            'store' => true,
            'no_cache' => false,
        ],
        'file_enable' => [
            'path' => 'lengow_export_options/advanced/export_file_enable',
            'global' => true,
            'no_cache' => false,
        ],
        'export_cron_enable' => [
            'path' => 'lengow_export_options/advanced/export_cron_enable',
            'global' => true,
            'no_cache' => false,
        ],
        'last_export' => [
            'path' => 'lengow_export_options/advanced/export_last_export',
            'store' => true,
            'no_cache' => true,
        ],
        'days' => [
            'path' => 'lengow_import_options/simple/import_days',
            'store' => true,
            'no_cache' => false,
        ],
        'customer_group' => [
            'path' => 'lengow_import_options/simple/import_customer_group',
            'store' => true,
            'no_cache' => false,
        ],
        'import_shipping_method' => [
            'path' => 'lengow_import_options/simple/import_default_shipping_method',
            'store' => true,
            'no_cache' => false,
        ],
        'currency_conversion_enabled' => [
            'path' => 'lengow_import_options/simple/import_currency_conversion_enable',
            'store' => true,
            'no_cache' => false,
        ],
        'report_mail_enable' => [
            'path' => 'lengow_import_options/advanced/import_report_mail_enable',
            'global' => true,
            'no_cache' => false,
        ],
        'report_mail_address' => [
            'path' => 'lengow_import_options/advanced/import_report_mail_address',
            'global' => true,
            'no_cache' => false,
        ],
        'import_ship_mp_enabled' => [
            'path' => 'lengow_import_options/advanced/import_ship_mp_enabled',
            'global' => true,
            'no_cache' => false,
        ],
        'import_stock_ship_mp' => [
            'path' => 'lengow_import_options/advanced/import_stock_ship_mp',
            'global' => true,
            'no_cache' => false,
        ],
        'preprod_mode_enable' => [
            'path' => 'lengow_import_options/advanced/import_preprod_mode_enable',
            'global' => true,
            'no_cache' => false,
        ],
        'import_cron_enable' => [
            'path' => 'lengow_import_options/advanced/import_cron_enable',
            'global' => true,
            'no_cache' => false,
        ],
        'import_in_progress' => [
            'path' => 'lengow_import_options/advanced/import_in_progress',
            'global' => true,
            'no_cache' => true,
        ],
        'last_import_manual' => [
            'path' => 'lengow_import_options/advanced/last_import_manual',
            'global' => true,
            'no_cache' => true,
        ],
        'last_import_cron' => [
            'path' => 'lengow_import_options/advanced/last_import_cron',
            'global' => true,
            'no_cache' => true,
        ],
        'last_action_sync' => array(
            'path' => 'lengow_import_options/advanced/last_action_sync',
            'global' => true,
            'no_cache' => true,
        ),
    ];

    /**
     * @var array attributes excludes
     */
    protected $_excludeAttributes = [
        'sku',
        'name',
        'media_gallery',
        'tier_price',
        'short_description',
        'description',
        'quantity',
        'price',
        'lengow_product',
        'status',
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context Magento context instance
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $writerInterface Magento writer instance
     * @param \Magento\Framework\App\Cache\Manager $cacheManager Magento Cache manager instance
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $customerGroupCollectionFactory
     * @param \Magento\Eav\Model\Config $eavConfig Magento eav config
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $attributeCollectionFactory
     * @param \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $configDataCollectionFactory
     * @param \Magento\Store\Model\ResourceModel\Store\CollectionFactory $storeCollectionFactory
     */
    public function __construct(
        Context $context,
        WriterInterface $writerInterface,
        CacheManager $cacheManager,
        CustomerGroupCollectionFactory $customerGroupCollectionFactory,
        EavConfig $eavConfig,
        AttributeCollectionFactory $attributeCollectionFactory,
        ConfigDataCollectionFactory $configDataCollectionFactory,
        StoreCollectionFactory $storeCollectionFactory
    )
    {
        $this->_writerInterface = $writerInterface;
        $this->_cacheManager = $cacheManager;
        $this->_customerGroupCollectionFactory = $customerGroupCollectionFactory;
        $this->_eavConfig = $eavConfig;
        $this->_attributeCollectionFactory = $attributeCollectionFactory;
        $this->_configDataCollectionFactory = $configDataCollectionFactory;
        $this->_storeCollectionFactory = $storeCollectionFactory;
        parent::__construct($context);
    }

    /**
     * Get Value
     *
     * @param string $key Lengow setting key
     * @param integer $storeId Magento store id
     *
     * @return mixed
     */
    public function get($key, $storeId = 0)
    {
        if (!array_key_exists($key, $this->_options)) {
            return null;
        }
        if ($this->_options[$key]['no_cache']) {
            $results = $this->_configDataCollectionFactory->create()
                ->addFieldToFilter('path', $this->_options[$key]['path'])
                ->addFieldToFilter('scope_id', $storeId)
                ->load()
                ->getData();
            $value = !empty($results) ? $results[0]['value'] : '';
        } else {
            $scope = (int)$storeId === 0 ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT : ScopeInterface::SCOPE_STORES;
            $value = $this->scopeConfig->getValue($this->_options[$key]['path'], $scope, $storeId);
        }
        return $value;
    }

    /**
     * Set Value
     *
     * @param string $key Lengow setting key
     * @param mixed $value Lengow setting value
     * @param integer $storeId Magento store id
     */
    public function set($key, $value, $storeId = 0)
    {
        if ((int)$storeId === 0) {
            $this->_writerInterface->save($this->_options[$key]['path'], $value);
        } else {
            $this->_writerInterface->save(
                $this->_options[$key]['path'],
                $value,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
        }
    }

    /**
     * Delete configuration key
     *
     * @param string $path Magento setting path
     * @param integer $storeId Magento store id
     */
    public function delete($path, $storeId = 0)
    {
        if ((int)$storeId === 0) {
            $this->_writerInterface->delete($path);
        } else {
            $this->_writerInterface->delete($path, ScopeInterface::SCOPE_STORES, $storeId);
        }
    }

    /**
     * Clean configuration cache
     */
    public function cleanConfigCache()
    {
        $this->_cacheManager->flush([CacheTypeConfig::CACHE_TAG]);
    }

    /**
     * Get valid account id / access token / secret token by store
     *
     * @return array
     */
    public function getAccessIds()
    {
        $accountId = $this->get('account_id');
        $accessToken = $this->get('access_token');
        $secretToken = $this->get('secret_token');
        if (strlen($accountId) > 0 && strlen($accessToken) > 0 && strlen($secretToken) > 0) {
            return [(int)$accountId, $accessToken, $secretToken];
        } else {
            return [null, null, null];
        }
    }

    /**
     * Set Valid Account id / Access token / Secret token
     *
     * @param array $accessIds Account id / Access token / Secret token
     */
    public function setAccessIds($accessIds)
    {
        $listKey = ['account_id', 'access_token', 'secret_token'];
        foreach ($accessIds as $key => $value) {
            if (!in_array($key, array_keys($listKey))) {
                continue;
            }
            if (strlen($value) > 0) {
                $this->set($key, $value);
            }
        }
    }

    /**
     * Get catalog ids for a specific store
     *
     * @param integer $storeId Magento store id
     *
     * @return array
     */
    public function getCatalogIds($storeId)
    {
        $catalogIds = [];
        $storeCatalogIds = $this->get('catalog_id', $storeId);
        if (strlen($storeCatalogIds) > 0 && $storeCatalogIds != 0) {
            $ids = trim(str_replace(["\r\n", ',', '-', '|', ' ', '/'], ';', $storeCatalogIds), ';');
            $ids = array_filter(explode(';', $ids));
            foreach ($ids as $id) {
                if (is_numeric($id) && $id > 0) {
                    $catalogIds[] = (int)$id;
                }
            }
        }
        return $catalogIds;
    }

    /**
     * Set catalog ids for a specific shop
     *
     * @param array $catalogIds Lengow catalog ids
     * @param integer $storeId Magento store id
     *
     * @return boolean
     */
    public function setCatalogIds($catalogIds, $storeId)
    {
        $valueChange = false;
        $storeCatalogIds = self::getCatalogIds($storeId);
        foreach ($catalogIds as $catalogId) {
            if (!in_array($catalogId, $storeCatalogIds) && is_numeric($catalogId) && $catalogId > 0) {
                $storeCatalogIds[] = (int)$catalogId;
                $valueChange = true;
            }
        }
        $this->set('catalog_id', implode(';', $storeCatalogIds), $storeId);
        return $valueChange;
    }

    /**
     * Recovers if a store is active or not
     *
     * @param integer $storeId Magento store id
     *
     * @return boolean
     */
    public function storeIsActive($storeId)
    {
        return (bool)$this->get('store_enable', $storeId);
    }

    /**
     * Set active store or not
     *
     * @param integer $storeId Magento store id
     *
     * @return boolean
     */
    public function setActiveStore($storeId)
    {
        $storeIsActive = $this->storeIsActive($storeId);
        $storeHasCatalog = !empty(self::getCatalogIds($storeId));
        $this->set('store_enable', $storeHasCatalog, $storeId);
        return $storeIsActive !== $storeHasCatalog ? true : false;
    }

    /**
     * Get all Magento customer group
     *
     * @return array
     */
    public function getAllCustomerGroup()
    {
        $allCustomerGroups = $this->_customerGroupCollectionFactory->create()->toOptionArray();
        return $allCustomerGroups;
    }

    /**
     * Get all stores
     *
     * @return \Magento\Store\Model\ResourceModel\Store\Collection
     */
    public function getAllStore()
    {
        return $this->_storeCollectionFactory->create();
    }

    /**
     * Get all store code
     *
     * @return array
     */
    public function getAllStoreCode()
    {
        $storeCollection = $this->_storeCollectionFactory->create();
        $storeCodes = [];
        foreach ($storeCollection as $store) {
            $storeCodes[] = $store->getCode();
        }
        return $storeCodes;
    }

    /**
     * Get all store id
     *
     * @return array
     */
    public function getAllStoreId()
    {
        $storeCollection = $this->_storeCollectionFactory->create();
        $storeIds = [];
        foreach ($storeCollection as $store) {
            $storeIds[] = $store->getId();
        }
        return $storeIds;
    }

    /**
     * Get all available currency codes
     *
     * @return array
     */
    public function getAllAvailableCurrencyCodes()
    {
        $storeCollection = $this->_storeCollectionFactory->create();
        $allCurrencies = [];
        foreach ($storeCollection as $store) {
            // get store currencies
            $storeCurrencies = $store->getAvailableCurrencyCodes();
            if (is_array($storeCurrencies)) {
                foreach ($storeCurrencies as $currency) {
                    if (!in_array($currency, $allCurrencies)) {
                        $allCurrencies[] = $currency;
                    }
                }
            }
        }
        return $allCurrencies;
    }

    /**
     * Check if is a new merchant
     *
     * @return boolean
     */
    public function isNewMerchant()
    {
        list($accountId, $accessToken, $secretToken) = $this->getAccessIds();
        if ($accountId !== null && $accessToken !== null && $secretToken !== null) {
            return false;
        }
        return true;
    }

    /**
     * Get Selected attributes
     *
     * @param integer $storeId Magento store id
     *
     * @return array
     */
    public function getSelectedAttributes($storeId = 0)
    {
        $selectedAttributes = [];
        $attributes = $this->get('export_attribute', $storeId);
        if ($attributes !== null) {
            $attributes = explode(',', $attributes);
            foreach ($attributes as $attribute) {
                $selectedAttributes[] = $attribute;
            }
        }
        return $selectedAttributes;
    }

    /**
     * Get all Magento attributes
     *
     * @return array
     */
    public function getAllAttributes()
    {
        try {
            // add filter by entity type to get product attributes only
            $productEntityId = (int)$this->_eavConfig->getEntityType(Product::ENTITY)->getEntityTypeId();
            $attributes = $this->_attributeCollectionFactory->create()
                ->addFieldToFilter(AttibuteSet::KEY_ENTITY_TYPE_ID, $productEntityId)
                ->load()
                ->getData();
            $allAttributes = [
                ['value' => 'none', 'label' => ''],
            ];
            foreach ($attributes as $attribute) {
                if (!in_array($attribute['attribute_code'], $this->_excludeAttributes)) {
                    $allAttributes[] = [
                        'value' => $attribute['attribute_code'],
                        'label' => $attribute['attribute_code'],
                    ];
                }
            }
        } catch (\Exception $e) {
            $allAttributes = [];
        }
        return $allAttributes;
    }

    /**
     * Get and generate token
     *
     * @param integer $storeId Magento store id
     *
     * @return string
     */
    public function getToken($storeId = 0)
    {
        $token = $this->get('token', $storeId);
        if ($token && strlen($token) > 0) {
            return $token;
        } else {
            $token = bin2hex(openssl_random_pseudo_bytes(16));
            $this->set('token', $token, $storeId);
        }
        return $token;
    }

    /**
     * Get Store by token
     *
     * @param string $token Lengow store token
     *
     * @return \Magento\Store\Api\Data\StoreInterface|false
     */
    public function getStoreByToken($token)
    {
        if (strlen($token) <= 0) {
            return false;
        }
        $storeCollection = $this->_storeCollectionFactory->create();
        foreach ($storeCollection as $store) {
            if ($token === $this->get('token', $store->getId())) {
                return $store;
            }
        }
        return false;
    }

    /**
     * Set default attributes
     */
    public function setDefaultAttributes()
    {
        if ($this->get('export_attribute') === null) {
            $attributeList = '';
            $attributes = $this->getAllAttributes();
            foreach ($attributes as $attribute) {
                if ($attribute['value'] !== 'none') {
                    $attributeList .= $attribute['value'] . ',';
                }
            }
            $attributeList = rtrim($attributeList, ',');
            $this->set('export_attribute', $attributeList);
            $this->cleanConfigCache();
        }
    }

    /**
     * Get all report mails
     *
     * @return array
     */
    public function getReportEmailAddress()
    {
        $reportEmailAddress = [];
        $emails = $this->get('report_mail_address');
        $emails = trim(str_replace(["\r\n", ',', ' '], ';', $emails), ';');
        $emails = explode(';', $emails);
        foreach ($emails as $email) {
            try {
                if (strlen($email) > 0 && \Zend_Validate::is($email, 'EmailAddress')) {
                    $reportEmailAddress[] = $email;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        if (empty($reportEmailAddress)) {
            $reportEmailAddress[] = $this->scopeConfig->getValue(
                'trans_email/ident_general/email',
                ScopeInterface::SCOPE_STORE
            );
        }
        return $reportEmailAddress;
    }

    /**
     * Get Values by store or global
     *
     * @param integer|null $storeId Magento store id
     *
     * @return array
     */
    public function getAllValues($storeId = null)
    {
        $rows = [];
        foreach ($this->_options as $key => $value) {
            if (isset($value['export']) && !$value['export']) {
                continue;
            }
            if ($storeId) {
                if (isset($value['store']) && $value['store']) {
                    $rows[$key] = $this->get($key, $storeId);
                }
            } else {
                if (isset($value['global']) && $value['global']) {
                    $rows[$key] = $this->get($key);
                }
            }
        }
        return $rows;
    }
}
