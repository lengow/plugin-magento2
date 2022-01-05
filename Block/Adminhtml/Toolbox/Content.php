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
 * @subpackage  Block
 * @author      Team module <team-module@lengow.com>
 * @copyright   2021 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Block\Adminhtml\Toolbox;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory as ScheduleCollection;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Import as ImportHelper;
use Lengow\Connector\Helper\Toolbox as ToolboxHelper;
use Lengow\Connector\Model\Import as LengowImport;

class Content extends Template
{
    /* Array data for toolbox content creation */
    private const DATA_HEADER = 'header';
    private const DATA_TITLE = 'title';
    private const DATA_STATE = 'state';
    private const DATA_MESSAGE = 'message';
    private const DATA_SIMPLE = 'simple';
    private const DATA_HELP = 'help';
    private const DATA_HELP_LINK = 'help_link';
    private const DATA_HELP_LABEL = 'help_label';

    /* Lengow cron jobs */
    private const CRON_JOB_EXPORT = 'lengow_connector_launch_export';
    private const CRON_JOB_IMPORT = 'lengow_connector_launch_synchronization';

    /**
     * @var ScheduleCollection Magento schedule collection factory
     */
    private $scheduleCollection;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var ImportHelper Lengow import helper instance
     */
    private $importHelper;

    /**
     * @var ToolboxHelper Lengow toolbox helper instance
     */
    private $toolboxHelper;

    /**
     * Constructor
     *
     * @param Context $context Magento block context instance
     * @param ScheduleCollection $scheduleCollection
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param ImportHelper $importHelper Lengow import helper instance
     * @param ToolboxHelper $toolboxHelper Lengow toolbox helper instance
     * @param array $data additional params
     */
    public function __construct(
        Context $context,
        ScheduleCollection $scheduleCollection,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        ImportHelper $importHelper,
        ToolboxHelper $toolboxHelper,
        array $data = []
    ) {
        $this->scheduleCollection = $scheduleCollection;
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->importHelper = $importHelper;
        $this->toolboxHelper = $toolboxHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get array of requirements for toolbox
     *
     * @return string
     */
    public function getCheckList(): string
    {
        $checklistData = $this->toolboxHelper->getData(ToolboxHelper::DATA_TYPE_CHECKLIST);
        $checklist = [
            [
                self::DATA_TITLE => __('Lengow needs the CURL PHP extension'),
                self::DATA_HELP => __('The CURL extension is not installed or enabled in your PHP installation'),
                self::DATA_HELP_LINK => __('https://www.php.net/manual/en/curl.setup.php'),
                self::DATA_HELP_LABEL => __('Go to Curl PHP extension manual'),
                self::DATA_STATE => $checklistData[ToolboxHelper::CHECKLIST_CURL_ACTIVATED],
            ],
            [
                self::DATA_TITLE => __('Lengow needs the SimpleXML PHP extension'),
                self::DATA_HELP => __('The SimpleXML extension is not installed or enabled in your PHP installation'),
                self::DATA_HELP_LINK => __('https://www.php.net/manual/en/book.simplexml.php'),
                self::DATA_HELP_LABEL => __('Go to SimpleXML PHP extension manual'),
                self::DATA_STATE => $checklistData[ToolboxHelper::CHECKLIST_SIMPLE_XML_ACTIVATED],
            ],
            [
                self::DATA_TITLE => __('Lengow needs the JSON PHP extension'),
                self::DATA_HELP =>  __('The JSON extension is not installed or enabled in your PHP installation'),
                self::DATA_HELP_LINK => __('https://www.php.net/manual/en/book.json.php'),
                self::DATA_HELP_LABEL => __('Go to JSON PHP extension manual'),
                self::DATA_STATE => $checklistData[ToolboxHelper::CHECKLIST_JSON_ACTIVATED],
            ],
            [
                self::DATA_TITLE => __('Plugin files check'),
                self::DATA_HELP => __('Some files have been changed by the customer'),
                self::DATA_STATE => $checklistData[ToolboxHelper::CHECKLIST_MD5_SUCCESS],
            ],
        ];
        return $this->getContent($checklist);
    }

    /**
     * Get all global information for toolbox
     *
     * @return string
     */
    public function getPluginInformation(): string
    {
        $pluginData = $this->toolboxHelper->getData(ToolboxHelper::DATA_TYPE_PLUGIN);
        $checklist = [
            [
                self::DATA_TITLE => __('Magento version'),
                self::DATA_MESSAGE => $pluginData[ToolboxHelper::PLUGIN_CMS_VERSION],
            ],
            [
                self::DATA_TITLE => __('Plugin version'),
                self::DATA_MESSAGE => $pluginData[ToolboxHelper::PLUGIN_VERSION],
            ],
            [
                self::DATA_TITLE => __('Server IP'),
                self::DATA_MESSAGE => $pluginData[ToolboxHelper::PLUGIN_SERVER_IP],
            ],
            [
                self::DATA_TITLE => __('Authorisation by IP enabled'),
                self::DATA_STATE => $pluginData[ToolboxHelper::PLUGIN_AUTHORIZED_IP_ENABLE],
            ],
            [
                self::DATA_TITLE => __('Authorised IP'),
                self::DATA_MESSAGE => implode(', ', $pluginData[ToolboxHelper::PLUGIN_AUTHORIZED_IPS]),
            ],
            [
                self::DATA_TITLE => __('Export in a file enabled'),
                self::DATA_STATE => (bool) $this->configHelper->get(ConfigHelper::EXPORT_FILE_ENABLED),
            ],
            [
                self::DATA_TITLE => __('Magento cron job enabled for export'),
                self::DATA_STATE => (bool) $this->configHelper->get(ConfigHelper::EXPORT_MAGENTO_CRON_ENABLED),
            ],
            [
                self::DATA_TITLE => __('Debug Mode disabled'),
                self::DATA_STATE => $pluginData[ToolboxHelper::PLUGIN_DEBUG_MODE_DISABLE],
            ],
            [
                self::DATA_TITLE => __('Read and write permission from media folder'),
                self::DATA_STATE => $pluginData[ToolboxHelper::PLUGIN_WRITE_PERMISSION],
            ],
            [
                self::DATA_TITLE => __('Toolbox URL'),
                self::DATA_MESSAGE => $pluginData[ToolboxHelper::PLUGIN_TOOLBOX_URL],
            ],
        ];
        return $this->getContent($checklist);
    }

    /**
     * Get all import information for toolbox
     *
     * @return string
     */
    public function getImportInformation(): string
    {
        $synchronizationData = $this->toolboxHelper->getData(ToolboxHelper::DATA_TYPE_SYNCHRONIZATION);
        $lastSynchronization = $synchronizationData[ToolboxHelper::SYNCHRONIZATION_LAST_SYNCHRONIZATION];
        if ($lastSynchronization === 0) {
            $lastImportDate = __('none');
            $lastImportType = __('none');
        } else {
            $lastImportDate = $this->dataHelper->getDateInCorrectFormat($lastSynchronization, true);
            $lastSynchronizationType = $synchronizationData[ToolboxHelper::SYNCHRONIZATION_LAST_SYNCHRONIZATION_TYPE];
            $lastImportType = $lastSynchronizationType === LengowImport::TYPE_CRON ? __('cron') : __('manual');
        }
        if ($synchronizationData[ToolboxHelper::SYNCHRONIZATION_SYNCHRONIZATION_IN_PROGRESS]) {
            $importInProgress = __(
                'wait %1 seconds before the next import',
                [$this->importHelper->restTimeToImport()]
            );
        } else {
            $importInProgress = __('No import in progress');
        }
        $checklist = [
            [
                self::DATA_TITLE => __('Import token'),
                self::DATA_MESSAGE => $synchronizationData[ToolboxHelper::SYNCHRONIZATION_CMS_TOKEN],
            ],
            [
                self::DATA_TITLE => __('Import URL'),
                self::DATA_MESSAGE => $synchronizationData[ToolboxHelper::SYNCHRONIZATION_CRON_URL],
            ],
            [
                self::DATA_TITLE => __('Magento cron job enabled for import'),
                self::DATA_STATE => (bool) $this->configHelper->get(ConfigHelper::SYNCHRONISATION_MAGENTO_CRON_ENABLED),
            ],
            [
                self::DATA_TITLE => __('Orders imported by Lengow'),
                self::DATA_MESSAGE => $synchronizationData[ToolboxHelper::SYNCHRONIZATION_NUMBER_ORDERS_IMPORTED],
            ],
            [
                self::DATA_TITLE => __('Orders waiting to be sent'),
                self::DATA_MESSAGE => $synchronizationData[
                    ToolboxHelper::SYNCHRONIZATION_NUMBER_ORDERS_WAITING_SHIPMENT
                ],
            ],
            [
                self::DATA_TITLE => __('Orders with errors'),
                self::DATA_MESSAGE => $synchronizationData[ToolboxHelper::SYNCHRONIZATION_NUMBER_ORDERS_IN_ERROR],
            ],
            [
                self::DATA_TITLE => __('Import in progress'),
                self::DATA_MESSAGE => $importInProgress,
            ],
            [
                self::DATA_TITLE => __('Last import'),
                self::DATA_MESSAGE => $lastImportDate,
            ],
            [
                self::DATA_TITLE => __('Last import type'),
                self::DATA_MESSAGE => $lastImportType,
            ],
        ];
        return $this->getContent($checklist);
    }

    /**
     * Get all shop information for toolbox
     *
     * @return string
     */
    public function getExportInformation(): string
    {
        $content = '';
        $exportData = $this->toolboxHelper->getData(ToolboxHelper::DATA_TYPE_SHOP);
        foreach ($exportData as $data) {
            $lastExportMessage = $data[ToolboxHelper::SHOP_LAST_EXPORT] !== 0
                ? $this->dataHelper->getDateInCorrectFormat($data[ToolboxHelper::SHOP_LAST_EXPORT], true)
                : __('none');
            $checklist = [
                [
                    self::DATA_HEADER => $data[ToolboxHelper::SHOP_NAME]
                        . ' (' . $data[ToolboxHelper::SHOP_ID] . ') '
                        . $data[ToolboxHelper::SHOP_DOMAIN_URL],
                ],
                [
                    self::DATA_TITLE => __('Store followed by Lengow'),
                    self::DATA_STATE => $data[ToolboxHelper::SHOP_ENABLED],
                ],
                [
                    self::DATA_TITLE => __('Lengow catalogs id synchronized'),
                    self::DATA_MESSAGE => implode(', ', $data[ToolboxHelper::SHOP_CATALOG_IDS]),
                ],
                [
                    self::DATA_TITLE => __('Products available in the store'),
                    self::DATA_MESSAGE => $data[ToolboxHelper::SHOP_NUMBER_PRODUCTS_AVAILABLE],
                ],
                [
                    self::DATA_TITLE => __('Products exported in the store'),
                    self::DATA_MESSAGE => $data[ToolboxHelper::SHOP_NUMBER_PRODUCTS_EXPORTED],
                ],
                [
                    self::DATA_TITLE => __('Export token'),
                    self::DATA_MESSAGE => $data[ToolboxHelper::SHOP_TOKEN],
                ],
                [
                    self::DATA_TITLE => __('Export URL for this store'),
                    self::DATA_MESSAGE => $data[ToolboxHelper::SHOP_FEED_URL],
                ],
                [
                    self::DATA_TITLE => __('Last export'),
                    self::DATA_MESSAGE => $lastExportMessage,
                ],
            ];
            $content .= $this->getContent($checklist);
        }
        return $content;
    }

    /**
     * Get all file information for toolbox
     *
     * @return string
     */
    public function getFileInformation(): string
    {
        $content = '';
        $stores = $this->configHelper->getAllStore();
        foreach ($stores as $store) {
            $sep = DIRECTORY_SEPARATOR;
            $storePath = DataHelper::LENGOW_FOLDER . $sep . $store->getCode() . $sep;
            $folderPath = $this->dataHelper->getMediaPath() . $sep . $storePath;
            $folderUrl = $this->dataHelper->getMediaUrl() . $storePath;
            $files = file_exists($folderPath) ? array_diff(scandir($folderPath), ['..', '.']) : [];
            $checklist = [
                [self::DATA_HEADER => $store->getName() . ' (' . $store->getId() . ') ' . $store->getBaseUrl()]
            ];
            $checklist[] = [self::DATA_TITLE => __('Folder path'), self::DATA_MESSAGE => $folderPath];
            if (!empty($files)) {
                $checklist[] = [self::DATA_SIMPLE => __('File list')];
                foreach ($files as $file) {
                    $fileTimestamp = filectime($folderPath . $file);
                    $fileLink = '<a href="' . $folderUrl . $file . '" target="_blank">' . $file . '</a>';
                    $checklist[] = [
                        self::DATA_TITLE => $fileLink,
                        self::DATA_MESSAGE => $this->dataHelper->getDateInCorrectFormat($fileTimestamp, true),
                    ];
                }
            } else {
                $checklist[] = [self::DATA_SIMPLE => __('No file exported')];
            }
            $content .= $this->getContent($checklist);
        }
        return $content;
    }

    /**
     * Get array of file information
     *
     * @param string $type cron type (export or import)
     *
     * @return string
     */
    public function getCronInformation(string $type): string
    {
        $jobCode = $type === 'import' ? self::CRON_JOB_IMPORT : self::CRON_JOB_EXPORT;
        $lengowCronJobs = $this->scheduleCollection->create()
            ->addFieldToFilter('job_code', $jobCode)
            ->getData();
        $lengowCronJobs = array_slice(array_reverse($lengowCronJobs), 0, 20);
        return $this->getCronContent($lengowCronJobs);
    }

    /**
     * Get files checksum
     *
     * @return string
     */
    public function checkFileMd5(): string
    {
        $checklist = [];
        $checksumData = $this->toolboxHelper->getData(ToolboxHelper::DATA_TYPE_CHECKSUM);
        $html = '<h3><i class="fa fa-commenting"></i> ' . __('Summary') . '</h3>';
        if ($checksumData[ToolboxHelper::CHECKSUM_AVAILABLE]) {
            $checklist[] = [
                self::DATA_TITLE => __(
                    '%1 files checked',
                    [$checksumData[ToolboxHelper::CHECKSUM_NUMBER_FILES_CHECKED]]
                ),
                self::DATA_STATE => true,
            ];
            $checklist[] = [
                self::DATA_TITLE => __(
                    '%1 files changed',
                    [$checksumData[ToolboxHelper::CHECKSUM_NUMBER_FILES_MODIFIED]]
                ),
                self::DATA_STATE => $checksumData[ToolboxHelper::CHECKSUM_NUMBER_FILES_MODIFIED] === 0,
            ];
            $checklist[] = [
                self::DATA_TITLE => __(
                    '%1 files deleted',
                    [$checksumData[ToolboxHelper::CHECKSUM_NUMBER_FILES_DELETED]]
                ),
                self::DATA_STATE => $checksumData[ToolboxHelper::CHECKSUM_NUMBER_FILES_DELETED] === 0,
            ];
            $html .= $this->getContent($checklist);
            if (!empty($checksumData[ToolboxHelper::CHECKSUM_FILE_MODIFIED])) {
                $fileModified = [];
                foreach ($checksumData[ToolboxHelper::CHECKSUM_FILE_MODIFIED] as $file) {
                    $fileModified[] = [
                        self::DATA_TITLE => $file,
                        self::DATA_STATE => 0,
                    ];
                }
                $html .= '<h3><i class="fa fa-list"></i> ' . __('List of changed files') . '</h3>';
                $html .= $this->getContent($fileModified);
            }
            if (!empty($checksumData[ToolboxHelper::CHECKSUM_FILE_DELETED])) {
                $fileDeleted = [];
                foreach ($checksumData[ToolboxHelper::CHECKSUM_FILE_DELETED] as $file) {
                    $fileModified[] = [
                        self::DATA_TITLE => $file,
                        self::DATA_STATE => 0,
                    ];
                }
                $html .= '<h3><i class="fa fa-list"></i> ' . __('List of deleted files') . '</h3>';
                $html .= $this->getContent($fileDeleted);
            }
        } else {
            $checklist[] = [
                self::DATA_TITLE => __('checkmd5.csv file is not available. Checking is impossible!'),
                self::DATA_STATE => false,
            ];
            $html .= $this->getContent($checklist);
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
    private function getContent(array $checklist = []): string
    {
        if (empty($checklist)) {
            return '';
        }
        $out = '<table cellpadding="0" cellspacing="0">';
        foreach ($checklist as $check) {
            $out .= '<tr>';
            if (isset($check[self::DATA_HEADER])) {
                $out .= '<td colspan="2" align="center" style="border:0"><h4>'
                    . $check[self::DATA_HEADER] . '</h4></td>';
            } elseif (isset($check[self::DATA_SIMPLE])) {
                $out .= '<td colspan="2" align="center"><h5>' . $check[self::DATA_SIMPLE] . '</h5></td>';
            } else {
                $out .= '<td><b>' . $check[self::DATA_TITLE] . '</b></td>';
                if (isset($check[self::DATA_STATE])) {
                    if ($check[self::DATA_STATE]) {
                        $out .= '<td align="right"><i class="fa fa-check lengow-green"></td>';
                    } else {
                        $out .= '<td align="right"><i class="fa fa-times lengow-red"></td>';
                    }
                    if (!$check[self::DATA_STATE] && isset($check[self::DATA_HELP])) {
                        $out .= '<tr><td colspan="2"><p>' . $check[self::DATA_HELP];
                        if (array_key_exists(self::DATA_HELP_LINK, $check) && $check[self::DATA_HELP_LINK] !== '') {
                            $out .= '<br /><a target="_blank" href="'
                                . $check[self::DATA_HELP_LINK] . '">' . $check[self::DATA_HELP_LABEL] . '</a>';
                        }
                        $out .= '</p></td></tr>';
                    }
                } else {
                    $out .= '<td align="right">' . $check[self::DATA_MESSAGE] . '</td>';
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
    private function getCronContent(array $lengowCronJobs = []): string
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
                    ? $this->dataHelper->getDateInCorrectFormat(strtotime($lengowCronJob['scheduled_at'], true))
                    : '';
                $out .= '<td>' . $scheduledAt . '</td>';
                $executedAt = $lengowCronJob['executed_at'] !== null
                    ? $this->dataHelper->getDateInCorrectFormat(strtotime($lengowCronJob['executed_at'], true))
                    : '';
                $out .= '<td>' . $executedAt . '</td>';
                $finishedAt = $lengowCronJob['finished_at'] !== null
                    ? $this->dataHelper->getDateInCorrectFormat(strtotime($lengowCronJob['finished_at'], true))
                    : '';
                $out .= '<td>' . $finishedAt . '</td>';
                $out .= '</tr>';
            }
        }
        $out .= '</table>';
        return $out;
    }
}
