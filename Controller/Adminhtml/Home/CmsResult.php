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
 * @subpackage  Controller
 * @author      Team module <team-module@lengow.com>
 * @copyright   2021 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Controller\Adminhtml\Home;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json as MagentoJsonResult;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\View\Result\PageFactory;
use Lengow\Connector\Block\Adminhtml\Main;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Model\Catalog as LengowCatalog;
use Lengow\Connector\Model\Connector as LengowConnector;

class CmsResult extends Action
{
    /**
     * @var PageFactory Magento Page factory
     */
    private $resultPageFactory;

    /**
     * @var JsonFactory Magento Json factory
     */
    private $resultJsonFactory;

    /**
     * @var JsonHelper Magento Json Helper
     */
    private $jsonHelper;

    /**
     * @var ConfigHelper Lengow config helper
     */
    private $configHelper;

    /**
     * @var LengowConnector Lengow connector helper
     */
    private $connector;

    /**
     * @var LengowCatalog Lengow catalog helper
     */
    private $catalog;

    /**
     * @var DataHelper Lengow data helper
     */
    private $dataHelper;

    /**
     * @var SyncHelper Lengow sync helper
     */
    private $syncHelper;

    /**
     * View constructor
     * @param Context $context Magento context
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param LengowConnector $connector Lengow connector instance
     * @param LengowCatalog $catalog Lengow catalog instance
     * @param DataHelper $dataHelper Lengow data helper
     * @param SyncHelper $syncHelper Lengow sync helper
     * @param JsonHelper $jsonHelper Magento json helper
     * @param PageFactory $resultPageFactory Magento Page factory
     * @param JsonFactory $resultJsonFactory Magento Json factory
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        LengowConnector $connector,
        LengowCatalog $catalog,
        DataHelper $dataHelper,
        SyncHelper $syncHelper,
        JsonHelper $jsonHelper,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory
    ) {
        $this->configHelper = $configHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->jsonHelper = $jsonHelper;
        $this->catalog = $catalog;
        $this->connector = $connector;
        $this->syncHelper = $syncHelper;
        $this->dataHelper = $dataHelper;

        parent::__construct($context);
    }

    /**
     * Return CMS connection result page
     *
     * @return MagentoJsonResult
     */
    public function execute(): MagentoJsonResult
    {
        $result = $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();
        $cmsConnected = false;
        $hasCatalogToLink = false;
        $accessToken = $this->getRequest()->getParam('access_token');
        $secret = $this->getRequest()->getParam('secret');
        $isCredentialsValid = $this->checkApiCredentials($accessToken, $secret);
        if ($isCredentialsValid) {
            $cmsConnected = $this->connectCms();
            if ($cmsConnected) {
                $hasCatalogToLink = $this->hasCatalogToLink();
            }
        }
        if (!$hasCatalogToLink && $cmsConnected) {
            $this->configHelper->cleanConfigCache();
        }
        $data = [
            'cmsConnected' => $cmsConnected,
            'isCredentialsValid' => $isCredentialsValid,
            'hasCatalogToLink' => $hasCatalogToLink,
        ];
        $block = $resultPage->getLayout()
            ->createBlock(Main::class)
            ->setTemplate('Lengow_Connector::home/cms_result.phtml')
            ->setData('data', $data)
            ->toHtml();
        $result->setData(['output' => $block]);
        return $result;
    }

    /**
     * Check if credentials are valid using the API
     *
     * @param string $accessToken Access token
     * @param string $secret secret token
     *
     * @return bool
     */
    private function checkApiCredentials(string $accessToken, string $secret): bool
    {
        $accountId = $this->connector->getAccountIdByCredentials($accessToken, $secret);
        if ($accountId) {
            return $this->configHelper->setAccessIds(
                [
                    ConfigHelper::ACCOUNT_ID => $accountId,
                    ConfigHelper::ACCESS_TOKEN => $accessToken,
                    ConfigHelper::SECRET => $secret,
                ]
            );
        }
        return false;
    }

    /**
     * Try to connect cms or send true if cms is already connected
     *
     * @return bool
     */
    private function connectCms(): bool
    {
        $cmsToken = $this->configHelper->getToken();
        $cmsConnected = $this->syncHelper->syncCatalog(true);
        if (!$cmsConnected) {
            $syncData = $this->jsonHelper->jsonEncode($this->syncHelper->getSyncData());
            $result = $this->connector->queryApi(
                LengowConnector::POST,
                LengowConnector::API_CMS,
                [],
                $syncData
            );
            if (isset($result->common_account)) {
                $cmsConnected = true;
                $messageKey = 'CMS successfully created with Lengow webservice (CMS token %1)';
            } else {
                $messageKey = 'WARNING! CMS could NOT be created with Lengow webservice (CMS token %1)';
            }
        } else {
            $messageKey = 'CMS already created in Lengow (CMS token %1)';
        }
        $this->dataHelper->log(
            DataHelper::CODE_CONNECTION,
            $this->dataHelper->setLogMessage($messageKey, [$cmsToken])
        );
        if (!$cmsConnected) {
            $this->configHelper->resetAccessIds();
            $this->configHelper->resetAuthorizationToken();
        }
        return $cmsConnected;
    }

    /**
     * Get all catalogs available in Lengow
     *
     * @return boolean
     */
    private function hasCatalogToLink(): bool
    {
        $lengowActiveStores = $this->configHelper->getLengowActiveStores();
        if (empty($lengowActiveStores)) {
            return $this->catalog->hasCatalogNotLinked();
        }
        return false;
    }
}
