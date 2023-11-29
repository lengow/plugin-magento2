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

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigDataCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\ResourceModel\Store\Collection as StoreCollection;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Laminas\Validator\EmailAddress;
use Lengow\Connector\Model\Connector as LengowConnector;
use Lengow\Connector\Model\Config\Source\Environment as EnvironmentSourceModel;

class Config extends AbstractHelper
{
    /* Settings database key */
    public const ACCOUNT_ID = 'global_account_id';
    public const ACCESS_TOKEN = 'global_access_token';
    public const SECRET = 'global_secret_token';
    public const PLUGIN_ENV = 'global_environment';
    public const CMS_TOKEN = 'token';
    public const AUTHORIZED_IP_ENABLED = 'global_authorized_ip_enable';
    public const AUTHORIZED_IPS = 'global_authorized_ip';
    public const TRACKING_ENABLED = 'global_tracking_enable';
    public const TRACKING_ID = 'global_tracking_id';
    public const DEBUG_MODE_ENABLED = 'import_debug_mode_enable';
    public const REPORT_MAIL_ENABLED = 'import_report_mail_enable';
    public const REPORT_MAILS = 'import_report_mail_address';
    public const AUTHORIZATION_TOKEN = 'authorization_token';
    public const PLUGIN_DATA = 'plugin_data';
    public const ACCOUNT_STATUS_DATA = 'account_status';
    public const SHOP_TOKEN = 'global_shop_token';
    public const SHOP_ACTIVE = 'global_store_enable';
    public const CATALOG_IDS = 'global_catalog_id';
    public const SELECTION_ENABLED = 'export_selection_enable';
    public const INACTIVE_ENABLED = 'export_product_status';
    public const EXPORT_PRODUCT_TYPES = 'export_product_type';
    public const EXPORT_ATTRIBUTES = 'export_attribute';
    public const EXPORT_PARENT_ATTRIBUTES = 'export_link_parent_attribute_to_child';
    public const EXPORT_PARENT_IMAGE_ENABLED = 'export_parent_image';
    public const EXPORT_FILE_ENABLED = 'export_file_enable';
    public const EXPORT_MAGENTO_CRON_ENABLED = 'export_cron_enable';
    public const DEFAULT_EXPORT_SHIPPING_COUNTRY = 'export_default_shipping_country';
    public const DEFAULT_EXPORT_CARRIER_ID = 'export_default_shipping_method';
    public const DEFAULT_EXPORT_SHIPPING_PRICE = 'export_default_shipping_price';
    public const SYNCHRONIZATION_DAY_INTERVAL = 'import_days';
    public const DEFAULT_IMPORT_CARRIER_ID = 'import_default_shipping_method';
    public const CURRENCY_CONVERSION_ENABLED = 'import_currency_conversion_enable';
    public const CHECK_ROUNDING_ENABLED = 'import_rounding_taxes_check_enable';
    public const B2B_WITHOUT_TAX_ENABLED = 'import_b2b_without_tax';
    public const SHIPPED_BY_MARKETPLACE_ENABLED = 'import_ship_mp_enabled';
    public const SHIPPED_BY_MARKETPLACE_STOCK_ENABLED = 'import_stock_ship_mp';
    public const IMPORT_ANONYMIZED_EMAIL = 'import_anonymized_email';
    public const SYNCHRONISATION_MAGENTO_CRON_ENABLED = 'import_cron_enable';
    public const SYNCHRONISATION_CUSTOMER_GROUP = 'import_customer_group';
    public const SYNCHRONIZATION_IN_PROGRESS = 'import_in_progress';
    public const LAST_UPDATE_EXPORT = 'export_last_export';
    public const LAST_UPDATE_CRON_SYNCHRONIZATION = 'last_import_cron';
    public const LAST_UPDATE_MANUAL_SYNCHRONIZATION = 'last_import_manual';
    public const LAST_UPDATE_ACTION_SYNCHRONIZATION = 'last_action_sync';
    public const LAST_UPDATE_CATALOG = 'last_catalog_update';
    public const LAST_UPDATE_MARKETPLACE = 'last_marketplace_update';
    public const LAST_UPDATE_ACCOUNT_STATUS_DATA = 'last_status_update';
    public const LAST_UPDATE_OPTION_CMS = 'last_option_cms_update';
    public const LAST_UPDATE_SETTING = 'last_setting_update';
    public const LAST_UPDATE_PLUGIN_DATA = 'last_plugin_data_update';
    public const LAST_UPDATE_AUTHORIZATION_TOKEN = 'last_authorization_token_update';
    public const LAST_UPDATE_PLUGIN_MODAL = 'last_plugin_modal_update';

    /* Configuration parameters */
    public const PARAM_EXPORT = 'export';
    public const PARAM_EXPORT_TOOLBOX = 'export_toolbox';
    public const PARAM_GLOBAL = 'global';
    public const PARAM_LOG = 'log';
    public const PARAM_NO_CACHE = 'no_cache';
    public const PARAM_RESET_TOKEN = 'reset_token';
    public const PARAM_RETURN = 'return';
    public const PARAM_SECRET = 'secret';
    public const PARAM_SHOP = 'store';
    public const PARAM_PATH = 'path';
    public const PARAM_UPDATE = 'update';

    /* Configuration value return type */
    public const RETURN_TYPE_BOOLEAN = 'boolean';
    public const RETURN_TYPE_INTEGER = 'integer';
    public const RETURN_TYPE_ARRAY = 'array';
    public const RETURN_TYPE_FLOAT = 'float';

    /**
     * @var array params correspondence keys for toolbox
     */
    public static $genericParamKeys = [
        self::ACCOUNT_ID => 'account_id',
        self::PLUGIN_ENV => 'global_environment',
        self::ACCESS_TOKEN => 'access_token',
        self::SECRET => 'secret',
        self::CMS_TOKEN => 'cms_token',
        self::AUTHORIZED_IP_ENABLED => 'authorized_ip_enabled',
        self::AUTHORIZED_IPS => 'authorized_ips',
        self::TRACKING_ENABLED => 'tracking_enabled',
        self::TRACKING_ID => 'tracking_id',
        self::DEBUG_MODE_ENABLED => 'debug_mode_enabled',
        self::REPORT_MAIL_ENABLED => 'report_mail_enabled',
        self::REPORT_MAILS => 'report_mails',
        self::AUTHORIZATION_TOKEN => 'authorization_token',
        self::PLUGIN_DATA => 'plugin_data',
        self::ACCOUNT_STATUS_DATA => 'account_status_data',
        self::SHOP_TOKEN => 'shop_token',
        self::SHOP_ACTIVE => 'shop_active',
        self::CATALOG_IDS => 'catalog_ids',
        self::SELECTION_ENABLED => 'selection_enabled',
        self::INACTIVE_ENABLED => 'inactive_enabled',
        self::EXPORT_PRODUCT_TYPES => 'export_product_types',
        self::EXPORT_ATTRIBUTES => 'export_attributes',
        self::EXPORT_PARENT_ATTRIBUTES => 'export_parent_attributes',
        self::EXPORT_PARENT_IMAGE_ENABLED => 'export_parent_image_enabled',
        self::EXPORT_FILE_ENABLED => 'export_file_enabled',
        self::EXPORT_MAGENTO_CRON_ENABLED => 'export_magento_cron_enable',
        self::DEFAULT_EXPORT_SHIPPING_COUNTRY => 'default_export_shipping_country',
        self::DEFAULT_EXPORT_CARRIER_ID => 'default_export_carrier_id',
        self::DEFAULT_EXPORT_SHIPPING_PRICE => 'default_export_shipping_price',
        self::SYNCHRONIZATION_DAY_INTERVAL => 'synchronization_day_interval',
        self::DEFAULT_IMPORT_CARRIER_ID => 'default_import_carrier_id',
        self::CURRENCY_CONVERSION_ENABLED => 'currency_conversion_enabled',
        self::CHECK_ROUNDING_ENABLED => 'rounding_taxes_check_enable',
        self::B2B_WITHOUT_TAX_ENABLED => 'b2b_without_tax_enabled',
        self::SHIPPED_BY_MARKETPLACE_ENABLED => 'shipped_by_marketplace_enabled',
        self::SHIPPED_BY_MARKETPLACE_STOCK_ENABLED => 'shipped_by_marketplace_stock_enabled',
        self::IMPORT_ANONYMIZED_EMAIL => 'import_anonymized_email',
        self::SYNCHRONISATION_MAGENTO_CRON_ENABLED => 'synchronization_magento_cron_enabled',
        self::SYNCHRONISATION_CUSTOMER_GROUP => 'synchronization_customer_group',
        self::SYNCHRONIZATION_IN_PROGRESS => 'synchronization_in_progress',
        self::LAST_UPDATE_EXPORT => 'last_update_export',
        self::LAST_UPDATE_CRON_SYNCHRONIZATION => 'last_update_cron_synchronization',
        self::LAST_UPDATE_MANUAL_SYNCHRONIZATION => 'last_update_manual_synchronization',
        self::LAST_UPDATE_ACTION_SYNCHRONIZATION => 'last_update_action_synchronization',
        self::LAST_UPDATE_CATALOG => 'last_update_catalog',
        self::LAST_UPDATE_MARKETPLACE => 'last_update_marketplace',
        self::LAST_UPDATE_ACCOUNT_STATUS_DATA => 'last_update_account_status_data',
        self::LAST_UPDATE_OPTION_CMS => 'last_update_option_cms',
        self::LAST_UPDATE_SETTING => 'last_update_setting',
        self::LAST_UPDATE_PLUGIN_DATA => 'last_update_plugin_data',
        self::LAST_UPDATE_AUTHORIZATION_TOKEN => 'last_update_authorization_token',
        self::LAST_UPDATE_PLUGIN_MODAL => 'last_update_plugin_modal',
    ];

    /**
     * @var WriterInterface Magento writer instance
     */
    private $writerInterface;

    /**
     * @var CacheManager Magento cache manager instance
     */
    private $cacheManager;

    /**
     * @var CustomerGroupCollectionFactory Magento customer group collection factory
     */
    private $customerGroupCollectionFactory;

    /**
     * @var AttributeCollectionFactory Magento attribute collection factory
     */
    private $attributeCollectionFactory;

    /**
     * @var ConfigDataCollectionFactory Magento config data collection factory
     */
    private $configDataCollectionFactory;

    /**
     * @var StoreCollectionFactory Magento store collection factory
     */
    private $storeCollectionFactory;

    /**
     * @var EavConfig Magento eav config
     */
    private $eavConfig;

    /**
     * @var SearchCriteriaBuilderFactory Magento criteria builder factory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var array all Lengow options path
     */
    public static $lengowSettings = [
        self::ACCOUNT_ID => [
            self::PARAM_PATH => 'lengow_global_options/store_credential/global_account_id',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
        ],
        self::PLUGIN_ENV => [
            self::PARAM_PATH => 'lengow_global_options/store_credential/global_environment',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
        ],
        self::ACCESS_TOKEN => [
            self::PARAM_PATH => 'lengow_global_options/store_credential/global_access_token',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
            self::PARAM_SECRET => true,
            self::PARAM_RESET_TOKEN => true,
        ],
        self::SECRET => [
            self::PARAM_PATH => 'lengow_global_options/store_credential/global_secret_token',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
            self::PARAM_SECRET => true,
            self::PARAM_RESET_TOKEN => true,
        ],
        self::CMS_TOKEN => [
            self::PARAM_PATH => 'lengow_global_options/store_credential/token',
            self::PARAM_GLOBAL => true,
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT_TOOLBOX => false,
        ],
        self::AUTHORIZED_IP_ENABLED => [
            self::PARAM_PATH => 'lengow_global_options/advanced/global_authorized_ip_enable',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::AUTHORIZED_IPS => [
            self::PARAM_PATH => 'lengow_global_options/advanced/global_authorized_ip',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_ARRAY,
        ],
        self::TRACKING_ENABLED => [
            self::PARAM_PATH => 'lengow_global_options/advanced/global_tracking_enable',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::TRACKING_ID => [
            self::PARAM_PATH => 'lengow_global_options/advanced/global_tracking_id',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
        ],
        self::DEBUG_MODE_ENABLED => [
            self::PARAM_PATH => 'lengow_import_options/advanced/import_debug_mode_enable',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::REPORT_MAIL_ENABLED => [
            self::PARAM_PATH => 'lengow_import_options/advanced/import_report_mail_enable',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::REPORT_MAILS => [
            self::PARAM_PATH => 'lengow_import_options/advanced/import_report_mail_address',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_ARRAY,
        ],
        self::AUTHORIZATION_TOKEN => [
            self::PARAM_PATH => 'lengow_global_options/store_credential/authorization_token',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
            self::PARAM_LOG => false,
        ],
        self::PLUGIN_DATA => [
            self::PARAM_PATH => 'lengow_global_options/advanced/plugin_data',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
            self::PARAM_LOG => false,
        ],
        self::ACCOUNT_STATUS_DATA => [
            self::PARAM_PATH => 'lengow_global_options/advanced/account_status',
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
            self::PARAM_LOG => false,
        ],
        self::SHOP_ACTIVE => [
            self::PARAM_PATH => 'lengow_global_options/store_credential/global_store_enable',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::CATALOG_IDS => [
            self::PARAM_PATH => 'lengow_global_options/store_credential/global_catalog_id',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_UPDATE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_ARRAY,
        ],
        self::SELECTION_ENABLED => [
            self::PARAM_PATH => 'lengow_export_options/simple/export_selection_enable',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::INACTIVE_ENABLED => [
            self::PARAM_PATH => 'lengow_export_options/simple/export_product_status',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::EXPORT_PRODUCT_TYPES => [
            self::PARAM_PATH => 'lengow_export_options/simple/export_product_type',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_ARRAY,
        ],
        self::EXPORT_ATTRIBUTES => [
            self::PARAM_PATH => 'lengow_export_options/advanced/export_attribute',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_ARRAY,
        ],
        self::EXPORT_PARENT_ATTRIBUTES => [
            self::PARAM_PATH => 'lengow_export_options/advanced/export_link_parent_attribute_to_child',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_ARRAY,
        ],
        self::EXPORT_PARENT_IMAGE_ENABLED => [
            self::PARAM_PATH => 'lengow_export_options/advanced/export_parent_image',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::EXPORT_FILE_ENABLED => [
            self::PARAM_PATH => 'lengow_export_options/advanced/export_file_enable',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::EXPORT_MAGENTO_CRON_ENABLED => [
            self::PARAM_PATH => 'lengow_export_options/advanced/export_cron_enable',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::DEFAULT_EXPORT_SHIPPING_COUNTRY => [
            self::PARAM_PATH => 'lengow_export_options/advanced/export_default_shipping_country',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
        ],
        self::DEFAULT_EXPORT_CARRIER_ID => [
            self::PARAM_PATH => 'lengow_export_options/advanced/export_default_shipping_method',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
        ],
        self::DEFAULT_EXPORT_SHIPPING_PRICE => [
            self::PARAM_PATH => 'lengow_export_options/advanced/export_default_shipping_price',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_FLOAT,
        ],
        self::SYNCHRONIZATION_DAY_INTERVAL => [
            self::PARAM_PATH => 'lengow_import_options/simple/import_days',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_UPDATE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ],
        self::DEFAULT_IMPORT_CARRIER_ID => [
            self::PARAM_PATH => 'lengow_import_options/simple/import_default_shipping_method',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
        ],
        self::CURRENCY_CONVERSION_ENABLED => [
            self::PARAM_PATH => 'lengow_import_options/simple/import_currency_conversion_enable',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::CHECK_ROUNDING_ENABLED => [
            self::PARAM_PATH => 'lengow_import_options/simple/import_rounding_taxes_check_enable',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::B2B_WITHOUT_TAX_ENABLED => [
            self::PARAM_PATH => 'lengow_import_options/advanced/import_b2b_without_tax',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::SHIPPED_BY_MARKETPLACE_ENABLED => [
            self::PARAM_PATH => 'lengow_import_options/advanced/import_ship_mp_enabled',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::SHIPPED_BY_MARKETPLACE_STOCK_ENABLED => [
            self::PARAM_PATH => 'lengow_import_options/advanced/import_stock_ship_mp',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::IMPORT_ANONYMIZED_EMAIL => [
            self::PARAM_PATH => 'lengow_import_options/advanced/import_anonymized_email',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::SYNCHRONISATION_MAGENTO_CRON_ENABLED => [
            self::PARAM_PATH => 'lengow_import_options/advanced/import_cron_enable',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_BOOLEAN,
        ],
        self::SYNCHRONISATION_CUSTOMER_GROUP => [
            self::PARAM_PATH => 'lengow_import_options/simple/import_customer_group',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => false,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
        ],
        self::SYNCHRONIZATION_IN_PROGRESS => [
            self::PARAM_PATH => 'lengow_import_options/advanced/import_in_progress',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT => false,
            self::PARAM_LOG => false,
        ],
        self::LAST_UPDATE_EXPORT => [
            self::PARAM_PATH => 'lengow_export_options/advanced/export_last_export',
            self::PARAM_SHOP => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ],
        self::LAST_UPDATE_CRON_SYNCHRONIZATION => [
            self::PARAM_PATH => 'lengow_import_options/advanced/last_import_cron',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ],
        self::LAST_UPDATE_MANUAL_SYNCHRONIZATION => [
            self::PARAM_PATH => 'lengow_import_options/advanced/last_import_manual',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_EXPORT_TOOLBOX => false,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ],
        self::LAST_UPDATE_ACTION_SYNCHRONIZATION => [
            self::PARAM_PATH => 'lengow_import_options/advanced/last_action_sync',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ],
        self::LAST_UPDATE_CATALOG => [
            self::PARAM_PATH => 'lengow_global_options/advanced/last_catalog_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ],
        self::LAST_UPDATE_MARKETPLACE => [
            self::PARAM_PATH => 'lengow_global_options/advanced/last_marketplace_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ],
        self::LAST_UPDATE_ACCOUNT_STATUS_DATA => [
            self::PARAM_PATH => 'lengow_global_options/advanced/last_status_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ],
        self::LAST_UPDATE_OPTION_CMS => [
            self::PARAM_PATH => 'lengow_global_options/advanced/last_option_cms_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ],
        self::LAST_UPDATE_SETTING => [
            self::PARAM_PATH => 'lengow_global_options/advanced/last_setting_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ],
        self::LAST_UPDATE_PLUGIN_DATA => [
            self::PARAM_PATH => 'lengow_global_options/advanced/last_plugin_data_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ],
        self::LAST_UPDATE_AUTHORIZATION_TOKEN => [
            self::PARAM_PATH => 'lengow_global_options/store_credential/last_authorization_token_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ],
        self::LAST_UPDATE_PLUGIN_MODAL => [
            self::PARAM_PATH => 'lengow_global_options/store_credential/last_plugin_modal_update',
            self::PARAM_GLOBAL => true,
            self::PARAM_NO_CACHE => true,
            self::PARAM_RETURN => self::RETURN_TYPE_INTEGER,
            self::PARAM_LOG => false,
        ],
    ];

    /**
     * @var array attributes excludes
     */
    private $excludeAttributes = [
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
     * @param Context $context Magento context instance
     * @param WriterInterface $writerInterface Magento writer instance
     * @param CacheManager $cacheManager Magento Cache manager instance
     * @param CustomerGroupCollectionFactory $customerGroupCollectionFactory Magento Customer group factory instance
     * @param EavConfig $eavConfig Magento eav config instance
     * @param AttributeCollectionFactory $attributeCollectionFactory Magento Attribute factory instance
     * @param ConfigDataCollectionFactory $configDataCollectionFactory Magento config data factory instance
     * @param StoreCollectionFactory $storeCollectionFactory Magento store factory instance
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory Magento search criteria builder instance
     */
    public function __construct(
        Context $context,
        WriterInterface $writerInterface,
        CacheManager $cacheManager,
        CustomerGroupCollectionFactory $customerGroupCollectionFactory,
        EavConfig $eavConfig,
        AttributeCollectionFactory $attributeCollectionFactory,
        ConfigDataCollectionFactory $configDataCollectionFactory,
        StoreCollectionFactory $storeCollectionFactory,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        $this->writerInterface = $writerInterface;
        $this->cacheManager = $cacheManager;
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->configDataCollectionFactory = $configDataCollectionFactory;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
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
    public function get(string $key, int $storeId = 0)
    {
        if (!array_key_exists($key, self::$lengowSettings)) {
            return null;
        }
        if (self::$lengowSettings[$key][self::PARAM_NO_CACHE]) {
            $results = $this->configDataCollectionFactory->create()
                ->addFieldToFilter('path', self::$lengowSettings[$key]['path'])
                ->addFieldToFilter('scope_id', $storeId)
                ->load()
                ->getData();
            $value = !empty($results) ? $results[0]['value'] : '';
        } else {
            $scope = $storeId === 0 ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT : ScopeInterface::SCOPE_STORES;
            $value = $this->scopeConfig->getValue(self::$lengowSettings[$key]['path'], $scope, $storeId);
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
    public function set(string $key, $value, int $storeId = 0): void
    {
        if ($storeId === 0) {
            $this->writerInterface->save(self::$lengowSettings[$key]['path'], $value);
        } else {
            $this->writerInterface->save(
                self::$lengowSettings[$key]['path'],
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
    public function delete(string $path, int $storeId = 0): void
    {
        if ($storeId === 0) {
            $this->writerInterface->delete($path);
        } else {
            $this->writerInterface->delete($path, ScopeInterface::SCOPE_STORES, $storeId);
        }
    }

    /**
     * Clean configuration cache
     */
    public function cleanConfigCache(): void
    {
        $this->cacheManager->flush([CacheTypeConfig::CACHE_TAG]);
    }

    /**
     * Get valid account id / access token / secret token by store
     *
     * @return array
     */
    public function getAccessIds(): array
    {
        $accountId = $this->get(self::ACCOUNT_ID);
        $accessToken = $this->get(self::ACCESS_TOKEN);
        $secretToken = $this->get(self::SECRET);
        if ($accountId && $accessToken && $secretToken) {
            return [(int) $accountId, $accessToken, $secretToken];
        }
        return [null, null, null];
    }

    /**
     * Set Valid Account id / Access token / Secret token
     *
     * @param array $accessIds Account id / Access token / Secret token
     *
     * @return bool
     */
    public function setAccessIds(array $accessIds): bool
    {
        $count = 0;
        $listKey = [self::ACCOUNT_ID, self::ACCESS_TOKEN, self::SECRET];
        foreach ($accessIds as $key => $value) {
            if (!in_array($key, $listKey, true)) {
                continue;
            }
            if ($value !== '') {
                $count++;
                $this->set($key, $value);
            }
        }
        return $count === count($listKey);
    }

    /**
     * Reset access ids
     */
    public function resetAccessIds(): void
    {
        $accessIds = [self::ACCOUNT_ID, self::ACCESS_TOKEN, self::SECRET];
        foreach ($accessIds as $accessId) {
            $value = $this->get($accessId);
            if ($value !== '') {
                $this->set($accessId, '');
            }
        }
    }

    /**
     * Reset authorization token
     */
    public function resetAuthorizationToken(): void
    {
        $this->set(self::AUTHORIZATION_TOKEN, '');
        $this->set(self::LAST_UPDATE_AUTHORIZATION_TOKEN, '');
    }

    /**
     * Delete catalog ID and disable store
     */
    public function resetCatalogIds(): void
    {
        $lengowActiveStores = $this->getLengowActiveStores();
        foreach ($lengowActiveStores as $store) {
            $this->set(self::CATALOG_IDS, '', (int) $store->getId());
            $this->set(self::SHOP_ACTIVE, false, (int) $store->getId());
        }
    }

    /**
     * Get catalog ids for a specific store
     *
     * @param integer $storeId Magento store id
     *
     * @return array
     */
    public function getCatalogIds(int $storeId): array
    {
        $catalogIds = [];
        $storeCatalogIds = $this->get(self::CATALOG_IDS, $storeId);
        if (!empty($storeCatalogIds)) {
            $ids = trim(str_replace(["\r\n", ',', '-', '|', ' ', '/'], ';', $storeCatalogIds), ';');
            $ids = array_filter(explode(';', $ids));
            foreach ($ids as $id) {
                if (is_numeric($id) && $id > 0) {
                    $catalogIds[] = (int) $id;
                }
            }
        }
        return $catalogIds;
    }

    /**
     * Get list of Magento stores that have been activated in Lengow
     *
     * @param integer|null $storeId Magento store id
     *
     * @return array
     */
    public function getLengowActiveStores(int $storeId = null): array
    {
        $lengowActiveStores = [];
        $storeCollection = $this->storeCollectionFactory->create()->load()->addFieldToFilter('is_active', 1);
        foreach ($storeCollection as $store) {
            if ($storeId && (int) $store->getId() !== $storeId) {
                continue;
            }
            // get Lengow config for this store
            if ($this->storeIsActive((int) $store->getId())) {
                $lengowActiveStores[] = $store;
            }
        }
        return $lengowActiveStores;
    }

    /**
     * Set catalog ids for a specific shop
     *
     * @param array $catalogIds Lengow catalog ids
     * @param integer $storeId Magento store id
     *
     * @return boolean
     */
    public function setCatalogIds(array $catalogIds, int $storeId): bool
    {
        $valueChange = false;
        $storeCatalogIds = $this->getCatalogIds($storeId);
        foreach ($catalogIds as $catalogId) {
            if ($catalogId > 0 && is_numeric($catalogId) && !in_array($catalogId, $storeCatalogIds, true)) {
                $storeCatalogIds[] = (int) $catalogId;
                $valueChange = true;
            }
        }
        $this->set(self::CATALOG_IDS, implode(';', $storeCatalogIds), $storeId);
        return $valueChange;
    }

    /**
     * Recovers if a store is active or not
     *
     * @param integer $storeId Magento store id
     *
     * @return boolean
     */
    public function storeIsActive(int $storeId): bool
    {
        return (bool) $this->get(self::SHOP_ACTIVE, $storeId);
    }

    /**
     * Set active store or not
     *
     * @param integer $storeId Magento store id
     *
     * @return boolean
     */
    public function setActiveStore(int $storeId): bool
    {
        $storeIsActive = $this->storeIsActive($storeId);
        $catalogIds = $this->getCatalogIds($storeId);
        $storeHasCatalog = !empty($catalogIds);
        $this->set(self::SHOP_ACTIVE, $storeHasCatalog, $storeId);
        return $storeIsActive !== $storeHasCatalog;
    }

    /**
     * Recovers if a store is active or not
     *
     * @return boolean
     */
    public function debugModeIsActive(): bool
    {
        return (bool) $this->get(self::DEBUG_MODE_ENABLED);
    }

    /**
     * Get all Magento customer group
     *
     * @return array
     */
    public function getAllCustomerGroup(): array
    {
        return $this->customerGroupCollectionFactory->create()->toOptionArray();
    }

    /**
     * Get all stores
     *
     * @return StoreCollection
     */
    public function getAllStore(): StoreCollection
    {
        return $this->storeCollectionFactory->create();
    }

    /**
     * Get all store code
     *
     * @return array
     */
    public function getAllStoreCode(): array
    {
        $storeCollection = $this->storeCollectionFactory->create();
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
    public function getAllStoreId(): array
    {
        $storeCollection = $this->storeCollectionFactory->create();
        $storeIds = [];
        foreach ($storeCollection as $store) {
            $storeIds[] = (int) $store->getId();
        }
        return $storeIds;
    }

    /**
     * Get all sources options
     *
     * @return array
     */
    public function getAllSources(): array
    {
        $options = [];
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder->create();
        // We use object manager here because SourceRepositoryInterface is only available for version >= 2.3
        $objectManager = ObjectManager::getInstance();
        $sourceRepository = $objectManager->create(SourceRepositoryInterface::class);
        $sources = $sourceRepository->getList($searchCriteria)->getItems();
        foreach ($sources as $source) {
            $options[] = $source->getSourceCode();
        }
        return $options;
    }

    /**
     * Check if is a new merchant
     *
     * @return boolean
     */
    public function isNewMerchant(): bool
    {
        list($accountId, $accessToken, $secretToken) = $this->getAccessIds();
        return !($accountId && $accessToken && $secretToken);
    }

    /**
     * Get Selected attributes
     *
     * @param integer $storeId Magento store id
     *
     * @return array
     */
    public function getSelectedAttributes(int $storeId = 0): array
    {
        $selectedAttributes = [];
        $attributes = $this->get(self::EXPORT_ATTRIBUTES, $storeId);
        if ($attributes !== null) {
            $attributes = explode(',', $attributes);
            foreach ($attributes as $attribute) {
                $selectedAttributes[] = $attribute;
            }
        }
        return $selectedAttributes;
    }

    /**
     * Get parent selected attributes to export instead of child data
     *
     * @param integer $storeId Magento store id
     *
     * @return array
     */
    public function getParentSelectedAttributes(int $storeId = 0): array
    {
        $selectedAttributes = [];
        $attributes = $this->get(self::EXPORT_PARENT_ATTRIBUTES, $storeId);
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
    public function getAllAttributes(): array
    {
        try {
            // add filter by entity type to get product attributes only
            $productEntityId = (int) $this->eavConfig->getEntityType(Product::ENTITY)->getEntityTypeId();
            $attributes = $this->attributeCollectionFactory->create()
                ->addFieldToFilter(AttributeSet::KEY_ENTITY_TYPE_ID, $productEntityId)
                ->load()
                ->getData();
            $allAttributes = [
                ['value' => 'none', 'label' => ''],
            ];
            foreach ($attributes as $attribute) {
                if (!in_array($attribute['attribute_code'], $this->excludeAttributes, true)) {
                    $allAttributes[] = [
                        'value' => $attribute['attribute_code'],
                        'label' => $attribute['attribute_code'],
                    ];
                }
            }
        } catch (Exception $e) {
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
    public function getToken(int $storeId = 0): string
    {
        $token = $this->get(self::CMS_TOKEN, $storeId);
        if ($token) {
            return $token;
        }
        $token = bin2hex(openssl_random_pseudo_bytes(16));
        $this->set(self::CMS_TOKEN, $token, $storeId);
        return $token;
    }

    /**
     * Get Store by token
     *
     * @param string $token Lengow store token
     *
     * @return StoreInterface|false
     */
    public function getStoreByToken(string $token)
    {
        if (strlen($token) <= 0) {
            return false;
        }
        $storeCollection = $this->storeCollectionFactory->create();
        foreach ($storeCollection as $store) {
            if ($token === $this->get(self::CMS_TOKEN, (int) $store->getId())) {
                return $store;
            }
        }
        return false;
    }

    /**
     * Set default attributes
     */
    public function setDefaultAttributes(): void
    {
        if ($this->get(self::EXPORT_ATTRIBUTES) === null) {
            $attributeList = '';
            $attributes = $this->getAllAttributes();
            foreach ($attributes as $attribute) {
                if ($attribute['value'] !== 'none') {
                    $attributeList .= $attribute['value'] . ',';
                }
            }
            $attributeList = rtrim($attributeList, ',');
            $this->set(self::EXPORT_ATTRIBUTES, $attributeList);
            $this->cleanConfigCache();
        }
    }

    /**
     * Get all report mails
     *
     * @return array
     */
    public function getReportEmailAddress(): array
    {
        $reportEmailAddress = [];
        $emails = $this->get(self::REPORT_MAILS);
        $emails = trim(str_replace(["\r\n", ',', ' '], ';', $emails ?? ''), ';');
        $emails = explode(';', $emails);
        foreach ($emails as $email) {
            try {
                if ($email !== '' && (new EmailAddress())->isValid($email)) {
                    $reportEmailAddress[] = $email;
                }
            } catch (Exception $e) {
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
     * Get authorized IPs
     *
     * @return array
     */
    public function getAuthorizedIps(): array
    {
        $authorizedIps = [];
        $ips = $this->get(self::AUTHORIZED_IPS);
        if (!empty($ips)) {
            $authorizedIps = trim(str_replace(["\r\n", ',', '-', '|', ' '], ';', $ips), ';');
            $authorizedIps = array_filter(explode(';', $authorizedIps));
        }
        return $authorizedIps;
    }

    /**
     * Get Values by store or global
     *
     * @param integer|null $storeId Magento store id
     * @param boolean $toolbox get all values for toolbox or not
     *
     * @return array
     */
    public function getAllValues(int $storeId = null, bool $toolbox = false): array
    {
        $rows = [];
        foreach (self::$lengowSettings as $key => $keyParams) {
            $value = null;
            if ((isset($keyParams[self::PARAM_EXPORT]) && !$keyParams[self::PARAM_EXPORT])
                || ($toolbox
                    && isset($keyParams[self::PARAM_EXPORT_TOOLBOX])
                    && !$keyParams[self::PARAM_EXPORT_TOOLBOX]
                )
            ) {
                continue;
            }
            if ($storeId) {
                if (isset($keyParams[self::PARAM_SHOP]) && $keyParams[self::PARAM_SHOP]) {
                    $value = $this->get($key, $storeId);
                    // added a check to differentiate the token shop from the cms token which are the same.
                    $genericKey = self::CMS_TOKEN === $key
                        ? self::$genericParamKeys[self::SHOP_TOKEN]
                        : self::$genericParamKeys[$key];
                    $rows[$genericKey] = $this->getValueWithCorrectType($key, $value);
                }
            } elseif (isset($keyParams[self::PARAM_GLOBAL]) && $keyParams[self::PARAM_GLOBAL]) {
                $value = $this->get($key);
                $rows[self::$genericParamKeys[$key]] = $this->getValueWithCorrectType($key, $value);
            }
        }
        return $rows;
    }

    /**
     * Check if a specific module is enabled
     *
     * @param string $moduleName module name [Vendor]_[Module]
     *
     * @return boolean
     */
    public function moduleIsEnabled(string $moduleName): bool
    {
        return $this->_moduleManager->isEnabled($moduleName);
    }

    /**
     * Returns whether prod-environment is configured to be used
     * @return bool
     */
    public function isProdEnvironment(): bool
    {
        $configuredEnvironment = $this->get(self::PLUGIN_ENV);
        if (empty($configuredEnvironment)
                || $configuredEnvironment === EnvironmentSourceModel::PROD_ENVIRONMENT) {
            return true;
        }

        return false;
    }

    /**
     * returns the url for my lengow
     *
     * @return string
     */
    public function getLengowUrl() : string
    {
        $url = LengowConnector::LENGOW_URL;
        if ($this->isProdEnvironment()) {
            $url = str_replace(
                LengowConnector::TEST_SUFFIX,
                LengowConnector::LIVE_SUFFIX,
                $url
            );
        } else {
            $url = str_replace(
                LengowConnector::LIVE_SUFFIX,
                LengowConnector::TEST_SUFFIX,
                $url
            );
        }
        return $url;
    }

    /**
     * returns the url for api
     *
     * @return string
     */
    public function getLengowApiUrl(): string
    {
        $url = LengowConnector::LENGOW_API_URL;
        if ($this->isProdEnvironment()) {
            $url = str_replace(
                LengowConnector::TEST_SUFFIX,
                LengowConnector::LIVE_SUFFIX,
                $url
            );
        } else {
            $url = str_replace(
                LengowConnector::LIVE_SUFFIX,
                LengowConnector::TEST_SUFFIX,
                $url
            );
        }
        return $url;
    }



    /**
     * Get configuration value in correct type
     *
     * @param string $key Lengow configuration key
     * @param string|null $value configuration value for conversion
     *
     * @return array|boolean|integer|float|string|string[]|null
     */
    private function getValueWithCorrectType(string $key, string $value = null)
    {
        $keyParams = self::$lengowSettings[$key];
        if (isset($keyParams[self::PARAM_RETURN])) {
            switch ($keyParams[self::PARAM_RETURN]) {
                case self::RETURN_TYPE_BOOLEAN:
                    return (bool) $value;
                case self::RETURN_TYPE_INTEGER:
                    return (int) $value;
                case self::RETURN_TYPE_FLOAT:
                    return (float) $value;
                case self::RETURN_TYPE_ARRAY:
                    return !empty($value)
                        ? explode(';', trim(str_replace(["\r\n", ',', ' '], ';', $value), ';'))
                        : [];
            }
        }
        return $value;
    }
}
