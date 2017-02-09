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
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Magento\Framework\App\Helper\Context;
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
     * @var array all Lengow options path
     */
    protected $_options = [
        'token' => [
            'path'     => 'lengow_global_options/store_credential/token',
            'store'    => true,
            'no_cache' => true,
        ],
        'store_enable' => [
            'path'     => 'lengow_global_options/store_credential/global_store_enable',
            'store'    => true,
            'no_cache' => false,
        ],
        'account_id' =>[
            'path'     => 'lengow_global_options/store_credential/global_account_id',
            'store'    => true,
            'no_cache' => false,
        ],
        'access_token' => [
            'path'     => 'lengow_global_options/store_credential/global_access_token',
            'store'    => true,
            'no_cache' => false,
        ],
        'secret_token' => [
            'path'     => 'lengow_global_options/store_credential/global_secret_token',
            'store'    => true,
            'no_cache' => false,
        ],
        'tracking_id' => [
            'path'     => 'lengow_global_options/advanced/global_tracking_id',
            'global'   => true,
            'no_cache' => false,
        ],
        'authorized_ip' => [
            'path'     => 'lengow_global_options/advanced/global_authorized_ip',
            'global'   => true,
            'no_cache' => false,
        ],
        'last_statistic_update' => [
            'path'     => 'lengow_global_options/advanced/last_statistic_update',
            'export'   => false,
            'no_cache' => false,
        ],
        'order_statistic' => [
            'path'     => 'lengow_global_options/advanced/order_statistic',
            'export'   => false,
            'no_cache' => false,
        ],
        'last_status_update' => [
            'path'     => 'lengow_global_options/advanced/last_status_update',
            'export'   => false,
            'no_cache' => false,
        ],
        'account_status' => [
            'path'     => 'lengow_global_options/advanced/account_status',
            'export'   => false,
            'no_cache' => false,
        ],
        'last_option_cms_update' => [
            'path'     => 'lengow_global_options/advanced/last_option_cms_update',
            'export'   => false,
            'no_cache' => false,
        ],
        'selection_enable' => [
            'path'     => 'lengow_export_options/simple/export_selection_enable',
            'store'    => true,
            'no_cache' => false,
        ],
        'product_type' => [
            'path'     => 'lengow_export_options/simple/export_product_type',
            'store'    => true,
            'no_cache' => false,
        ],
        'product_status' => [
            'path'     => 'lengow_export_options/simple/export_product_status',
            'store'    => true,
            'no_cache' => false,
        ],
        'export_attribute' => [
            'path'     => 'lengow_export_options/advanced/export_attribute',
            'export'   => false,
            'no_cache' => false,
        ],
        'shipping_country' => [
            'path'     => 'lengow_export_options/advanced/export_default_shipping_country',
            'store'    => true,
            'no_cache' => false,
        ],
        'shipping_method' => [
            'path'     => 'lengow_export_options/advanced/export_default_shipping_method',
            'store'    => true,
            'no_cache' => false,
        ],
        'shipping_price' => [
            'path'     => 'lengow_export_options/advanced/export_default_shipping_price',
            'store'    => true,
            'no_cache' => false,
        ],
        'parent_image' => [
            'path'     => 'lengow_export_options/advanced/export_parent_image',
            'store'    => true,
            'no_cache' => false,
        ],
        'file_enable' => [
            'path'     => 'lengow_export_options/advanced/export_file_enable',
            'global'   => true,
            'no_cache' => false,
        ],
        'export_cron_enable' => [
            'path'     => 'lengow_export_options/advanced/export_cron_enable',
            'global'   => true,
            'no_cache' => false,
        ],
        'last_export' => [
            'path'     => 'lengow_export_options/advanced/export_last_export',
            'store'    => true,
            'no_cache' => false,
        ],
        'days' => [
            'path'     => 'lengow_import_options/simple/import_days',
            'store'    => true,
            'no_cache' => false,
        ],
        'customer_group' => [
            'path'     => 'lengow_import_options/simple/import_customer_group',
            'store'    => true,
            'no_cache' => false,
        ],
        'import_shipping_method' => [
            'path'     => 'lengow_import_options/simple/import_default_shipping_method',
            'store'    => true,
            'no_cache' => false,
        ],
        'report_mail_enable' =>[
            'path'     => 'lengow_import_options/advanced/import_report_mail_enable',
            'global'   => true,
            'no_cache' => false,
        ],
        'report_mail_address' => [
            'path'     => 'lengow_import_options/advanced/import_report_mail_address',
            'global'   => true,
            'no_cache' => false,
        ],
        'import_ship_mp_enabled' => [
            'path'     =>  'lengow_import_options/advanced/import_ship_mp_enabled',
            'global'   => true,
            'no_cache' => false,
        ],
        'import_stock_ship_mp' => [
            'path'     =>  'lengow_import_options/advanced/import_stock_ship_mp',
            'global'   => true,
            'no_cache' => false,
        ],
        'preprod_mode_enable' => [
            'path'     => 'lengow_import_options/advanced/import_preprod_mode_enable',
            'global'   => true,
            'no_cache' => false,
        ],
        'import_cron_enable' => [
            'path'     => 'lengow_import_options/advanced/import_cron_enable',
            'global'   => true,
            'no_cache' => false,
        ],
        'import_in_progress' => [
            'path'     => 'lengow_import_options/advanced/import_in_progress',
            'global'   => true,
            'no_cache' => false,
        ],
        'last_import_manual' => [
            'path'     => 'lengow_import_options/advanced/last_import_manual',
            'global'   => true,
            'no_cache' => false,
        ],
        'last_import_cron' => [
            'path'     => 'lengow_import_options/advanced/last_import_cron',
            'global'   => true,
            'no_cache' => false,
        ],
    ];

    /**
     * @var array attributes excludes
     */
    protected $_excludeAttributes = [
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
     * @param \Magento\Framework\App\Cache\Manager $cacheManager Cache manager instance
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $customerGroupCollectionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $attributeCollectionFactory
     * @param \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $configDataCollectionFactory
     * @param \Magento\Store\Model\ResourceModel\Store\CollectionFactory $storeCollectionFactory
     */
    public function __construct(
        Context $context,
        WriterInterface $writerInterface,
        CacheManager $cacheManager,
        CustomerGroupCollectionFactory $customerGroupCollectionFactory,
        AttributeCollectionFactory $attributeCollectionFactory,
        ConfigDataCollectionFactory $configDataCollectionFactory,
        StoreCollectionFactory $storeCollectionFactory
    ) {
        $this->_writerInterface = $writerInterface;
        $this->_cacheManager = $cacheManager;
        $this->_customerGroupCollectionFactory = $customerGroupCollectionFactory;
        $this->_attributeCollectionFactory = $attributeCollectionFactory;
        $this->_configDataCollectionFactory = $configDataCollectionFactory;
        $this->_storeCollectionFactory = $storeCollectionFactory;
        parent::__construct($context);
    }

    /**
     * Get Value
     *
     * @param string  $key     Lengow setting key
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
            $value = count($results) > 0 ? $results[0]['value'] : '';
        } else {
            $scope = $storeId == 0 ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT : ScopeInterface::SCOPE_STORES;
            $value = $this->scopeConfig->getValue($this->_options[$key]['path'], $scope, $storeId);
        }
        return $value;
    }

    /**
     * Set Value
     *
     * @param string  $key        Lengow setting key
     * @param mixed   $value      Lengow setting value
     * @param integer $storeId    Magento store id
     * @param boolean $cleanCache clean config cache to valid configuration
     */
    public function set($key, $value, $storeId = 0, $cleanCache = true)
    {
        if ($storeId == 0) {
            $this->_writerInterface->save(
                $this->_options[$key]['path'],
                $value
            );
        } else {
            $this->_writerInterface->save(
                $this->_options[$key]['path'],
                $value,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
        }
        if ($cleanCache) {
            $this->_cacheManager->flush([CacheTypeConfig::CACHE_TAG]);
        }
    }

    /**
     * Get valid account id / access token / secret token by store
     *
     * @param integer $storeId Magento store Id
     *
     * @return array
     */
    public function getAccessId($storeId = null)
    {
        $accountId = '';
        $accessToken = '';
        $secretToken = '';
        if ($storeId) {
            $accountId = (int)$this->get('account_id', $storeId);
            $accessToken = $this->get('access_token', $storeId);
            $secretToken = $this->get('secret_token', $storeId);
        } else {
            $storeCollection = $this->_storeCollectionFactory->create()->addFieldToFilter('is_active', 1);
            foreach ($storeCollection as $store) {
                $accountId = $this->get('account_id', $store->getId());
                $accessToken = $this->get('access_token', $store->getId());
                $secretToken = $this->get('secret_token', $store->getId());
                if (strlen($accountId) > 0 && strlen($accessToken) > 0 && strlen($secretToken) > 0) {
                    break;
                }
            }
        }
        if (strlen($accountId) > 0 && strlen($accessToken) > 0 && strlen($secretToken) > 0) {
            return [$accountId, $accessToken, $secretToken];
        } else {
            return [null, null, null];
        }
    }

    /**
     * Get all Magento customer group
     *
     * @return array
     */
    public function getAllCustomerGroup()
    {
        $allCustomerGroups = $this->_customerGroupCollectionFactory->create()
            ->toOptionArray();
        return $allCustomerGroups;
    }

    /**
     * Get all Magento attributes
     *
     * @return array
     */
    public function getAllAttributes()
    {
        // add filter by entity type to get product attributes only
        $attributes = $this->_attributeCollectionFactory->create()
            ->addFieldToFilter(AttibuteSet::KEY_ENTITY_TYPE_ID, 4)
            ->load()
            ->getData();
        $allAttributes = [
            ['value' => 'none', 'label' => '']
        ];
        foreach ($attributes as $attribute) {
            if (!in_array($attribute['attribute_code'], $this->_excludeAttributes)) {
                $allAttributes[] = [
                    'value' => $attribute['attribute_code'],
                    'label' => $attribute['attribute_code']
                ];
            }
        }
        return $allAttributes;
    }

    /**
     * Set default attributes
     */
    public function setDefaultAttributes()
    {
        if (is_null($this->get('export_attribute'))) {
            $attributeList = '';
            $attributes = $this->getAllAttributes();
            foreach ($attributes as $attribute) {
                if ($attribute['value'] != 'none') {
                    $attributeList.= $attribute['value'].',';
                }
            }
            $attributeList = rtrim($attributeList, ',');
            $this->set('export_attribute', $attributeList, 0, true);
        }
    }
}
