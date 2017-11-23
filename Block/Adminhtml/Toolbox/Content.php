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
 * @subpackage  Block
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Block\Adminhtml\Toolbox;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Export;

class Content extends Template
{
    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Helper\Security Lengow security helper instance
     */
    protected $_securityHelper;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var \Lengow\Connector\Helper\Import Lengow import helper instance
     */
    protected $_importHelper;

    /**
     * @var \Lengow\Connector\Model\Import\Order Lengow order instance
     */
    protected $_lengowOrder;

    /**
     * @var \Lengow\Connector\Model\Export Lengow export instance
     */
    protected $_export;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context Magento block context instance
     * @param array $data additional params
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Security $securityHelper Lengow security helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Lengow\Connector\Helper\Import $importHelper Lengow import helper instance
     * @param \Lengow\Connector\Model\Import\Order $lengowOrder Lengow order instance
     * @param \Lengow\Connector\Model\Export $export Lengow export instance
     */
    public function __construct(
        Context $context,
        array $data = [],
        DataHelper $dataHelper,
        SecurityHelper $securityHelper,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        LengowOrder $lengowOrder,
        Export $export
    ) {
        $this->_dataHelper = $dataHelper;
        $this->_securityHelper = $securityHelper;
        $this->_configHelper = $configHelper;
        $this->_importHelper = $importHelper;
        $this->_lengowOrder = $lengowOrder;
        $this->_export = $export;
        parent::__construct($context, $data);
    }

    /**
     * Get all Magento stores
     *
     * @return \Magento\Store\Model\ResourceModel\Store\Collection
     */
    public function getStores()
    {
        return $this->_configHelper->getAllStore();
    }

    /**
     * Get array of plugin informations
     *
     * @return string
     */
    public function getPluginInformations()
    {
        $checklist = [];
        $checklist[] = [
            'title' => __('Magento version'),
            'message' => $this->_securityHelper->getMagentoVersion(),
        ];
        $checklist[] = [
            'title' => __('Plugin version'),
            'message' => $this->_securityHelper->getPluginVersion(),
        ];
        $checklist[] = [
            'title' => __('Server IP'),
            'message' => $_SERVER["SERVER_ADDR"],
        ];
        $checklist[] = [
            'title' => __('Authorisation by IP enabled'),
            'state' => (bool)$this->_configHelper->get('ip_enable'),
        ];
        $checklist[] = [
            'title' => __('Authorised IP'),
            'message' => $this->_configHelper->get('authorized_ip'),
        ];
        $checklist[] = [
            'title' => __('Export in a file enabled'),
            'state' => (bool)$this->_configHelper->get('file_enable'),
        ];
        $checklist[] = [
            'title' => __('Magento cron job enabled for export'),
            'state' => (bool)$this->_configHelper->get('export_cron_enable'),
        ];
        $checklist[] = [
            'title' => __('Pre-production mode disabled'),
            'state' => !(bool)$this->_configHelper->get('preprod_mode_enable'),
        ];
        $sep = DIRECTORY_SEPARATOR;
        $filePath = $this->_dataHelper->getMediaPath() . $sep . 'lengow' . $sep . 'test.txt';
        $file = fopen($filePath, "w+");
        if ($file == false) {
            $state = false;
        } else {
            $state = true;
            unlink($filePath);
        }
        $checklist[] = [
            'title' => __('Write permission from media folder'),
            'state' => $state
        ];
        return $this->_getContent($checklist);
    }

    /**
     * Get array of import information
     *
     * @return string
     */
    public function getImportInformations()
    {
        $checklist = [];
        $checklist[] = [
            'title' => __('Import token'),
            'message' => $this->_configHelper->getToken(),
        ];
        $checklist[] = [
            'title' => __('Import URL'),
            'message' => $this->_dataHelper->getCronUrl(),
        ];
        $checklist[] = [
            'title' => __('Magento cron job enabled for import'),
            'state' => (bool)$this->_configHelper->get('import_cron_enable'),
        ];
        $nbOrderImported = $this->_lengowOrder->countOrderImportedByLengow();
        $orderWithError = $this->_lengowOrder->countOrderWithError();
        $orderToBeSent = $this->_lengowOrder->countOrderToBeSent();
        $checklist[] = [
            'title' => __('Orders imported by Lengow'),
            'message' => $nbOrderImported,
        ];
        $checklist[] = [
            'title' => __('Orders waiting to be sent'),
            'message' => $orderToBeSent,
        ];
        $checklist[] = [
            'title' => __('Orders with errors'),
            'message' => $orderWithError,
        ];
        $lastImport = $this->_importHelper->getLastImport();
        $lastImportDate = $lastImport['timestamp'] == 'none'
            ? __('none')
            : $this->_dataHelper->getDateInCorrectFormat($lastImport['timestamp'], true);
        if ($lastImport['type'] == 'none') {
            $lastImportType = __('none');
        } elseif ($lastImport['type'] == 'cron') {
            $lastImportType = __('cron');
        } else {
            $lastImportType = __('manual');
        }
        if ($this->_importHelper->importIsInProcess()) {
            $importInProgress = __(
                'wait %1 seconds before the next import',
                [$this->_importHelper->restTimeToImport()]
            );
        } else {
            $importInProgress = __('No import in progress');
        }
        $checklist[] = [
            'title' => __('Import in progress'),
            'message' => $importInProgress,
        ];
        $checklist[] = [
            'title' => __('Last import'),
            'message' => $lastImportDate,
        ];
        $checklist[] = [
            'title' => __('Last import type'),
            'message' => $lastImportType,
        ];
        return $this->_getContent($checklist);
    }

    /**
     * Get array of export informations
     *
     * @param \Magento\Store\Model\Store $store Magento store instance
     *
     * @return string
     */
    public function getExportInformations($store)
    {
        $this->_export->init(['store_id' => $store->getId()]);
        $checklist = [];
        $checklist[] = [
            'header' => $store->getName() . ' (' . $store->getId() . ') ' . $store->getBaseUrl(),
        ];
        $checklist[] = [
            'title' => __('Store followed by Lengow'),
            'state' => (bool)$this->_configHelper->get('store_enable', $store->getId()),
        ];
        $checklist[] = [
            'title' => __('Lengow catalogs id synchronized'),
            'message' =>  $this->_configHelper->get('catalog_id', $store->getId()),
        ];
        $checklist[] = [
            'title' => __('Products available in the store'),
            'message' => $this->_export->getTotalProduct(),
        ];
        $checklist[] = [
            'title' => __('Products exported in the store'),
            'message' => $this->_export->getTotalExportedProduct(),
        ];
        $checklist[] = [
            'title' => __('Export token'),
            'message' => $this->_configHelper->getToken($store->getId()),
        ];
        $checklist[] = [
            'title' => __('Export URL for this store'),
            'message' => $this->_dataHelper->getExportUrl($store->getId()),
        ];
        $lastExportDate = $this->_configHelper->get('last_export', $store->getId());
        $checklist[] = [
            'title' => __('Last export'),
            'message' => $this->_dataHelper->getDateInCorrectFormat($lastExportDate, true),
        ];
        return $this->_getContent($checklist);
    }

    /**
     * Get array of file informations
     *
     * @param \Magento\Store\Model\Store $store Magento store instance
     *
     * @return string
     */
    public function getFileInformations($store)
    {
        $sep = DIRECTORY_SEPARATOR;
        $folderPath = $this->_dataHelper->getMediaPath() . $sep . 'lengow' . $sep . $store->getCode() . $sep;
        $folderUrl = $this->_dataHelper->getMediaUrl() . 'lengow' . $sep . $store->getCode() . $sep;
        $files = @array_diff(scandir($folderPath), ['..', '.']);
        $checklist =[];
        $checklist[] = [
            'header' => $store->getName() . ' (' . $store->getId() . ') ' . $store->getBaseUrl(),
        ];
        $checklist[] = ['title' => __('Folder path'), 'message' => $folderPath];
        if (count($files) > 0) {
            $checklist[] = ['simple' => __('File list')];
            foreach ($files as $file) {
                $fileTimestamp = filectime($folderPath . $file);
                $fileLink = '<a href="' . $folderUrl . $file . '" target="_blank">' . $file . '</a>';
                $checklist[] = [
                    'title' => $fileLink,
                    'message' => $this->_dataHelper->getDateInCorrectFormat($fileTimestamp, true),
                ];
            }
        } else {
            $checklist[] = ['simple' => __('No file exported')];
        }
        return $this->_getContent($checklist);
    }

    /**
     * Get HTML Table content of checklist
     *
     * @param array $checklist all information for toolbox
     *
     * @return string
     */
    protected function _getContent($checklist = [])
    {
        if (empty($checklist)) {
            return null;
        }
        $out = '<table cellpadding="0" cellspacing="0">';
        foreach ($checklist as $check) {
            $out .= '<tr>';
            if (isset($check['header'])) {
                $out .= '<td colspan="2" align="center" style="border:0"><h4>' . $check['header'] . '</h4></td>';
            } elseif (isset($check['simple'])) {
                $out .= '<td colspan="2" align="center"><h5>' . $check['simple'] . '</h5></td>';
            } else {
                $out .= '<td><b>' . $check['title'] . '</b></td>';
                if (isset($check['state'])) {
                    if ($check['state']) {
                        $out .= '<td align="right"><i class="fa fa-check lengow-green"></td>';
                    } else {
                        $out .= '<td align="right"><i class="fa fa-times lengow-red"></td>';
                    }
                } else {
                    $out .= '<td align="right">' . $check['message'] . '</td>';
                }
            }
            $out .= '</tr>';
        }
        $out .= '</table>';
        return $out;
    }
}
