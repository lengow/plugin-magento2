<?php
/**
 * Copyright 2021 Lengow SAS
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
 * @copyright   2021 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Model;

use Magento\Framework\Json\Helper\Data as JsonHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Connector as LengowConnector;

/**
 * Lengow connector
 */
class Catalog
{
    /**
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var LengowConnector
     */
    private $connector;

    /**
     * Constructor
     *
     * @param JsonHelper $jsonHelper Magento json helper
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param LengowConnector $connector Lengow connector instance
     */
    public function __construct(
        JsonHelper $jsonHelper,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        LengowConnector $connector
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->connector = $connector;
    }

    /**
     * Check if the account has catalogs not linked to a cms
     *
     * @return boolean
     */
    public function hasCatalogNotLinked(): bool
    {
        $lengowCatalogs = $this->connector->queryApi(
            LengowConnector::GET,
            LengowConnector::API_CMS_CATALOG
        );
        if (!$lengowCatalogs) {
            return false;
        }
        foreach ($lengowCatalogs as $catalog) {
            if (!is_object($catalog) || $catalog->shop) {
                continue;
            }
            return true;
        }
        return false;
    }

    /**
     * Get all catalogs available in Lengow
     *
     * @return array
     */
    public function getCatalogList(): array
    {
        $catalogList = [];
        $lengowCatalogs = $this->connector->queryApi(
            LengowConnector::GET,
            LengowConnector::API_CMS_CATALOG
        );
        if (!$lengowCatalogs) {
            return $catalogList;
        }
        foreach ($lengowCatalogs as $catalog) {
            if (!is_object($catalog) || $catalog->shop) {
                continue;
            }
            $name = $catalog->name ?? $this->dataHelper->decodeLogMessage(
                'Catalogue %1',
                true,
                ['catalog_id' => $catalog->id]
            );
            $status = $catalog->is_active
                ?  $this->dataHelper->decodeLogMessage('Active')
                :  $this->dataHelper->decodeLogMessage('Draft');
            $label = $this->dataHelper->decodeLogMessage(
                '%1 - %2 - %3 products - %4',
                false,
                [
                    $catalog->id,
                    $name,
                    $catalog->products ?: 0,
                    $status,
                ]
            );
            $catalogList[] = [
                'label' => $label,
                'value' => $catalog->id,
            ];
        }
        return $catalogList;
    }

    /**
     * Save catalogs ids and synchronise them with lengow
     *
     * @param array $catalogSelected catalog select by user to be linked
     *
     * @return bool
     */
    public function saveCatalogsLinked(array $catalogSelected = []): bool
    {
        $catalogsLinked = true;
        $catalogsByStores = [];
        foreach ($catalogSelected as $catalog) {
            $catalogsByStores[$catalog['shopId']] = $catalog['catalogId'];
        }
        if (!empty($catalogsByStores)) {
            // save catalogs ids and active shop in lengow configuration
            foreach ($catalogsByStores as $storeId => $catalogIds) {
                $this->configHelper->setCatalogIds($catalogIds, $storeId);
                $this->configHelper->setActiveStore($storeId);
            }
            // save last update date for a specific settings (change synchronisation interval time)
            $this->configHelper->set(ConfigHelper::LAST_UPDATE_SETTING, time());
            // link all catalogs selected by API
            $catalogsLinked = $this->linkCatalogs($catalogsByStores);
            $messageKey = $catalogsLinked
                ? 'catalogues successfully linked with Lengow webservice'
                : 'WARNING! catalogues could NOT be linked with Lengow webservice';
            $this->dataHelper->log(DataHelper::CODE_CONNECTION, $this->dataHelper->decodeLogMessage($messageKey));
        }
        return $catalogsLinked;
    }

    /**
     * Associate lengow catalog with magento stores
     *
     * @param array $catalogsByStores catalog to link sorted by store
     *
     * @return bool
     */
    public function linkCatalogs(array $catalogsByStores = []): bool
    {
        $catalogsLinked = false;
        if (empty($catalogsByStores)) {
            return $catalogsLinked;
        }
        $hasCatalogToLink = false;
        $linkCatalogData = [
            'cms_token' => $this->configHelper->getToken(),
            'shops' => [],
        ];
        foreach ($catalogsByStores as $storeId => $catalogIds) {
            if (empty($catalogIds)) {
                continue;
            }
            $hasCatalogToLink = true;
            $shopToken = $this->configHelper->getToken($storeId);
            $linkCatalogData['shops'][] = [
                'shop_token' => $shopToken,
                'catalogs_id' => $catalogIds,
            ];
            $this->dataHelper->log(DataHelper::CODE_CONNECTION, $this->dataHelper->decodeLogMessage(
                'try to associate catalogue IDs %1 to the shop (SHOP token %2) for the Magento store ID %3',
                false,
                [
                    implode(', ', $catalogIds),
                    $shopToken,
                    $storeId,
                ]
            ));
        }
        if ($hasCatalogToLink) {
            $result = $this->connector->queryApi(
                LengowConnector::POST,
                LengowConnector::API_CMS_MAPPING,
                [],
                $this->jsonHelper->jsonEncode($linkCatalogData)
            );
            if (isset($result->cms_token)) {
                $catalogsLinked = true;
            }
        }
        return $catalogsLinked;
    }
}
