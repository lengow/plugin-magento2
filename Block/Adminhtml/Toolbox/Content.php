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
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory as ScheduleCollection;
use Magento\Framework\Module\Dir\Reader;
use Magento\Store\Model\Store;
use Magento\Store\Model\ResourceModel\Store\Collection as StoreCollection;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Model\Export as LengowExport;
use Lengow\Connector\Model\Import as LengowImport;
use Lengow\Connector\Model\Import\Order as LengowOrder;

class Content extends Template
{
    /**
     * @var ScheduleCollection Magento schedule collection factory
     */
    protected $_scheduleCollection;

    /**
     * @var Reader Magento module reader instance
     */
    protected $_moduleReader;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var ImportHelper Lengow import helper instance
     */
    protected $_importHelper;

    /**
     * @var SecurityHelper Lengow security helper instance
     */
    protected $_securityHelper;

    /**
     * @var LengowExport Lengow export instance
     */
    protected $_export;

    /**
     * @var LengowOrder Lengow order instance
     */
    protected $_lengowOrder;

    /**
     * Constructor
     *
     * @param Context $context Magento block context instance
     * @param Reader $moduleReader Magento module reader instance
     * @param ScheduleCollection $scheduleCollection
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param SecurityHelper $securityHelper Lengow security helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param ImportHelper $importHelper Lengow import helper instance
     * @param LengowOrder $lengowOrder Lengow order instance
     * @param LengowExport $export Lengow export instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        Reader $moduleReader,
        ScheduleCollection $scheduleCollection,
        DataHelper $dataHelper,
        SecurityHelper $securityHelper,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        LengowOrder $lengowOrder,
        LengowExport $export,
        array $data = []
    ) {
        $this->_moduleReader = $moduleReader;
        $this->_scheduleCollection = $scheduleCollection;
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
     * @return StoreCollection
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
            'message' => $_SERVER['SERVER_ADDR'],
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
            'title' => __('Debug Mode disabled'),
            'state' => !$this->_configHelper->debugModeIsActive(),
        ];
        $sep = DIRECTORY_SEPARATOR;
        $filePath = $this->_dataHelper->getMediaPath() . $sep . DataHelper::LENGOW_FOLDER . $sep . 'test.txt';
        try {
            $file = fopen($filePath, 'w+');
            if (!$file) {
                $state = false;
            } else {
                $state = true;
                unlink($filePath);
            }
        } catch (\Exception $e) {
            $state = false;
        }
        $checklist[] = [
            'title' => __('Read and write permission from media folder'),
            'state' => $state,
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
        $lastImportDate = $lastImport['timestamp'] === 'none'
            ? __('none')
            : $this->_dataHelper->getDateInCorrectFormat($lastImport['timestamp'], true);
        if ($lastImport['type'] === 'none') {
            $lastImportType = __('none');
        } elseif ($lastImport['type'] === LengowImport::TYPE_CRON) {
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
     * @param Store $store Magento store instance
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
            'message' => $this->_configHelper->get('catalog_id', $store->getId()),
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
        $lastExportMessage = $lastExportDate === ''
            ? __('none')
            : $this->_dataHelper->getDateInCorrectFormat($lastExportDate, true);
        $checklist[] = [
            'title' => __('Last export'),
            'message' => $lastExportMessage,
        ];
        return $this->_getContent($checklist);
    }

    /**
     * Get array of file informations
     *
     * @param Store $store Magento store instance
     *
     * @return string
     */
    public function getFileInformations($store)
    {
        $sep = DIRECTORY_SEPARATOR;
        $storePath = DataHelper::LENGOW_FOLDER . $sep . $store->getCode() . $sep;
        $folderPath = $this->_dataHelper->getMediaPath() . $sep . $storePath;
        $folderUrl = $this->_dataHelper->getMediaUrl() . $storePath;
        try {
            $files = array_diff(scandir($folderPath), ['..', '.']);
        } catch (\Exception $e) {
            $files = [];
        }
        $checklist = [];
        $checklist[] = [
            'header' => $store->getName() . ' (' . $store->getId() . ') ' . $store->getBaseUrl(),
        ];
        $checklist[] = ['title' => __('Folder path'), 'message' => $folderPath];
        if (!empty($files)) {
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
     * Get array of file informations
     *
     * @param string $type cron type (export or import)
     *
     * @return string
     */
    public function getCronInformation($type)
    {
        $jobCode = $type === 'import' ? 'lengow_connector_launch_synchronization' : 'lengow_connector_launch_export';
        $lengowCronJobs = $this->_scheduleCollection->create()
            ->addFieldToFilter('job_code', $jobCode)
            ->getData();
        $lengowCronJobs = array_slice(array_reverse($lengowCronJobs), 0, 20);
        return $this->_getCronContent($lengowCronJobs);
    }

    /**
     * Get files checksum
     *
     * @return string
     */
    public function checkFileMd5()
    {
        $checklist = [];
        $sep = DIRECTORY_SEPARATOR;
        $fileName = $this->_moduleReader->getModuleDir('etc', 'Lengow_Connector') . $sep . 'checkmd5.csv';
        $html = '<h3><i class="fa fa-commenting"></i> ' . __('Summary') . '</h3>';
        $fileCounter = 0;
        if (file_exists($fileName)) {
            $fileErrors = [];
            $fileDeletes = [];
            $base = $this->_moduleReader->getModuleDir('', 'Lengow_Connector');
            if (($file = fopen($fileName, 'r')) !== false) {
                while (($data = fgetcsv($file, 1000, '|')) !== false) {
                    $fileCounter++;
                    $filePath = $base . $data[0];
                    if (file_exists($filePath)) {
                        $fileMd = md5_file($filePath);
                        if ($fileMd !== $data[1]) {
                            $fileErrors[] = [
                                'title' => $filePath,
                                'state' => false,
                            ];
                        }
                    } else {
                        $fileDeletes[] = [
                            'title' => $filePath,
                            'state' => false,
                        ];
                    }
                }
                fclose($file);
            }
            $checklist[] = [
                'title' => __('%1 files checked', [$fileCounter]),
                'state' => true,
            ];
            $checklist[] = [
                'title' => __('%1 files changed', [count($fileErrors)]),
                'state' => !empty($fileErrors) ? false : true,
            ];
            $checklist[] = [
                'title' => __('%1 files deleted', [count($fileDeletes)]),
                'state' => !empty($fileDeletes) ? false : true,
            ];
            $html .= $this->_getContent($checklist);
            if (!empty($fileErrors)) {
                $html .= '<h3><i class="fa fa-list"></i> ' . __('List of changed files') . '</h3>';
                $html .= $this->_getContent($fileErrors);
            }
            if (!empty($fileDeletes)) {
                $html .= '<h3><i class="fa fa-list"></i> ' . __('List of deleted files') . '</h3>';
                $html .= $this->_getContent($fileDeletes);
            }
        } else {
            $checklist[] = [
                'title' => __('checkmd5.csv file is not available. Checking is impossible!'),
                'state' => false,
            ];
            $html .= $this->_getContent($checklist);
        }
        return $html;
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

    /**
     * Get HTML Table content of cron job
     *
     * @param array $lengowCronJobs Lengow cron jobs
     *
     * @return string
     */
    protected function _getCronContent($lengowCronJobs = [])
    {
        $out = '<table cellpadding="0" cellspacing="0" style="text-align: left">';
        if (empty($lengowCronJobs)) {
            $out .= '<tr><td style="border:0">' . __('Any scheduled cron job for now') . '</td></tr>';
        } else {
            $out .= '<tr>';
            $out .= '<th>' . __('Status') . '</th>';
            $out .= '<th>' . __('Message') . '</th>';
            $out .= '<th>' . __('Scheduled at') . '</th>';
            $out .= '<th>' . __('Executed at') . '</th>';
            $out .= '<th>' . __('Finished at') . '</th>';
            $out .= '</tr>';
            foreach ($lengowCronJobs as $lengowCronJob) {
                $out .= '<tr>';
                $out .= '<td>' . $lengowCronJob['status'] . '</td>';
                if ($lengowCronJob['messages'] !== '') {
                    $out .= '<td><a class="lengow_tooltip" href="#">'
                        . __('see message')
                        . '<span class="lengow_toolbox_message">'
                        . $lengowCronJob['messages'] . '</span></a></td>';
                } else {
                    $out .= '<td></td>';
                }
                $scheduledAt = $lengowCronJob['scheduled_at'] !== null
                    ? $this->_dataHelper->getDateInCorrectFormat(strtotime($lengowCronJob['scheduled_at'], true))
                    : '';
                $out .= '<td>' . $scheduledAt . '</td>';
                $executedAt = $lengowCronJob['executed_at'] !== null
                    ? $this->_dataHelper->getDateInCorrectFormat(strtotime($lengowCronJob['executed_at'], true))
                    : '';
                $out .= '<td>' . $executedAt . '</td>';
                $finishedAt = $lengowCronJob['finished_at'] !== null
                    ? $this->_dataHelper->getDateInCorrectFormat(strtotime($lengowCronJob['finished_at'], true))
                    : '';
                $out .= '<td>' . $finishedAt . '</td>';
                $out .= '</tr>';
            }
        }
        $out .= '</table>';
        return $out;
    }
}
