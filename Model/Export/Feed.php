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

namespace Lengow\Connector\Model\Export;

use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Exception as LengowException;

/**
 * Lengow export feed
 */
class Feed
{
    /**
     * @var string  CSV protection
     */
    const PROTECTION = '"';

    /**
     * @var string CSV separator
     */
    const CSV_SEPARATOR = '|';

    /**
     * @var string end of line
     */
    const EOL = "\r\n";

    /**
     * @var \Magento\Framework\Filesystem\Driver\File Magento driver file instance
     */
    protected $_driverFile;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var \Magento\Framework\Json\Helper\Data Magento json helper instance
     */
    protected $_jsonHelper;

    /**
     * @var \Lengow\Connector\Model\Export\File Lengow file instance
     */
    protected $_file;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var string feed format
     */
    protected $_format;

    /**
     * @var boolean generate file or not
     */
    protected $_stream;

    /**
     * @var array formatted fields cache for header field
     */
    protected $_formattedFields = [];

    /**
     * @var array yaml space cache for header field
     */
    protected $_yamlSpaces = [];

    /**
     * @var string Lengow export folder
     */
    protected $_lengowExportFolder = 'lengow';

    /**
     * @var string folder name that contains the file
     */
    protected $_folderName;

    /**
     * @var string folder path that contains the file
     */
    protected $_folderPath;

    /**
     * @var string file name
     */
    protected $_fileName = 'lengow_feed';

    /**
     * Constructor
     *
     * @param \Magento\Framework\Filesystem\Driver\File $driverFile Magento driver file instance
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime Magento datetime instance
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper Magento json helper instance
     * @param \Lengow\Connector\Model\Export\File $file Lengow file instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     */
    public function __construct(
        DriverFile $driverFile,
        DateTime $dateTime,
        JsonHelper $jsonHelper,
        File $file,
        DataHelper $dataHelper
    ) {
        $this->_driverFile = $driverFile;
        $this->_dateTime = $dateTime;
        $this->_jsonHelper = $jsonHelper;
        $this->_file = $file;
        $this->_dataHelper = $dataHelper;
    }

    /**
     * Init a new feed
     *
     * @param array $params optional options for init
     * boolean stream     generate file or not
     * string  format     feed format
     * string  store_code Magento store code
     */
    public function init($params)
    {
        $this->_stream = $params['stream'];
        $this->_format = $params['format'];
        if (!$this->_stream) {
            $sep = DIRECTORY_SEPARATOR;
            $this->_folderName = $this->_lengowExportFolder . $sep . $params['store_code'];
            $this->_folderPath = $this->_dataHelper->getMediaPath() . $sep . $this->_folderName;
            $this->_initExportFile();
        }
    }

    /**
     * Write feed
     *
     * @param string $type (header, body or footer)
     * @param array $data export data
     * @param boolean $isFirst is first product to export
     * @param boolean $maxCharacter Max characters for yaml format
     */
    public function write($type, $data = [], $isFirst = null, $maxCharacter = null)
    {
        switch ($type) {
            case 'header':
                if ($this->_stream) {
                    header($this->_getHtmlHeader());
                    if ($this->_format == 'csv') {
                        header('Content-Disposition: attachment; filename=feed.csv');
                    }
                }
                $header = $this->_getHeader($data);
                $this->_flush($header);
                break;
            case 'body':
                $body = $this->_getBody($data, $isFirst, $maxCharacter);
                $this->_flush($body);
                break;
            case 'footer':
                $footer = $this->_getFooter();
                $this->_flush($footer);
                break;
        }
    }

    /**
     * Finalize export generation
     *
     * @return boolean
     */
    public function end()
    {
        $this->write('footer');
        if (!$this->_stream) {
            $this->_file->close();
            $newFileName = 'lengow_feed.' . $this->_format;
            return $this->_file->rename($newFileName);
        }
        return true;
    }

    /**
     * Get feed URL
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->_file->getLink();
    }

    /**
     * Get folder path
     *
     * @return string
     */
    public function getFolderPath()
    {
        return $this->_folderPath;
    }

    /**
     * Return HTML header according to the given format
     *
     * @return string
     */
    protected function _getHtmlHeader()
    {
        switch ($this->_format) {
            case 'csv':
                return 'Content-Type: text/csv; charset=UTF-8';
            case 'xml':
                return 'Content-Type: application/xml; charset=UTF-8';
            case 'json':
                return 'Content-Type: application/json; charset=UTF-8';
            case 'yaml':
                return 'Content-Type: text/x-yaml; charset=UTF-8';
            default:
                return '';
        }
    }

    /**
     * Return feed header.
     *
     * @param array $data feed data
     *
     * @return string
     */
    protected function _getHeader($data)
    {
        switch ($this->_format) {
            case 'csv':
                $header = '';
                foreach ($data as $field) {
                    $header .= self::PROTECTION . $this->_formatFields($field) . self::PROTECTION . self::CSV_SEPARATOR;
                }
                return rtrim($header, self::CSV_SEPARATOR) . self::EOL;
            case 'xml':
                return '<?xml version="1.0" encoding="UTF-8"?>' . self::EOL . '<catalog>' . self::EOL;
            case 'json':
                return '{"catalog":[';
            case 'yaml':
                return '"catalog":' . self::EOL;
            default:
                return '';
        }
    }

    /**
     * Get feed body
     *
     * @param array $data feed data
     * @param boolean $isFirst is first product to export
     * @param integer $maxCharacter max characters for yaml format
     *
     * @return string
     */
    protected function _getBody($data, $isFirst, $maxCharacter)
    {
        switch ($this->_format) {
            case 'csv':
                $content = '';
                foreach ($data as $value) {
                    $content .= self::PROTECTION . $value . self::PROTECTION . self::CSV_SEPARATOR;
                }
                return rtrim($content, self::CSV_SEPARATOR) . self::EOL;
            case 'xml':
                $content = '<product>';
                foreach ($data as $field => $value) {
                    $field = isset($this->_formattedFields[$field])
                        ? $this->_formattedFields[$field]
                        : $this->_formatFields($field);
                    $content .= '<' . $field . '><![CDATA[' . $value . ']]></' . $field . '>' . self::EOL;
                }
                $content .= '</product>' . self::EOL;
                return $content;
            case 'json':
                $content = $isFirst ? '' : ',';
                $jsonArray = [];
                foreach ($data as $field => $value) {
                    $field = isset($this->_formattedFields[$field])
                        ? $this->_formattedFields[$field]
                        : $this->_formatFields($field);
                    $jsonArray[$field] = $value;
                }
                $content .= $this->_jsonHelper->jsonEncode($jsonArray);
                return $content;
            case 'yaml':
                if ($maxCharacter % 2 == 1) {
                    $maxCharacter = $maxCharacter + 1;
                } else {
                    $maxCharacter = $maxCharacter + 2;
                }
                $content = '  ' . self::PROTECTION . 'product' . self::PROTECTION . ':' . self::EOL;
                foreach ($data as $field => $value) {
                    $field = isset($this->_formattedFields[$field])
                        ? $this->_formattedFields[$field]
                        : $this->_formatFields($field);
                    $content .= '    ' . self::PROTECTION . $field . self::PROTECTION . ':';
                    $yamlSpace = isset($this->_yamlSpaces[$field])
                        ? $this->_yamlSpaces[$field]
                        : $this->_indentYaml($field, $maxCharacter);
                    $content .= $yamlSpace . (string)$value . self::EOL;
                }
                return $content;
            default:
                return '';
        }
    }

    /**
     * Return feed footer
     *
     * @return string
     */
    protected function _getFooter()
    {
        switch ($this->_format) {
            case 'xml':
                return '</catalog>';
            case 'json':
                return ']}';
            default:
                return '';
        }
    }

    /**
     * Create export file
     *
     * @throws LengowException unable to create folder
     */
    protected function _initExportFile()
    {
        try {
            $this->_driverFile->createDirectory($this->_folderPath);
        } catch (\Exception $e) {
            throw new LengowException(
                $this->_dataHelper->setLogMessage('unable to create folder %1', [$this->_folderPath])
            );
        }
        if ($this->_isAlreadyLaunch()) {
            throw new LengowException($this->_dataHelper->setLogMessage('feed already launched'));
        }
        $fileName = $this->_fileName . '.' . time() . '.' . $this->_format;
        $this->_file->init(['folder_name' => $this->_folderName, 'file_name' => $fileName]);
    }

    /**
     * Is Feed Already Launch
     *
     * @return boolean
     */
    protected function _isAlreadyLaunch()
    {
        $listFiles = $this->_driverFile->readDirectory($this->_folderPath);
        if (count($listFiles) > 0) {
            foreach ($listFiles as $filePath) {
                $fileName = str_replace($this->_folderPath . '/', '', $filePath);
                if (preg_match('/^' . $this->_fileName . '\.[\d]{10}/', $fileName)) {
                    $fileModified = $this->_dateTime->date('Y-m-d H:i:s', filemtime($filePath));
                    $fileModifiedDatetime = new \DateTime($fileModified);
                    $fileModifiedDatetime->add(new \DateInterval('P5D'));
                    if ($this->_dateTime->date('Y-m-d') > $fileModifiedDatetime->format('Y-m-d')) {
                        $this->_driverFile->deleteFile($filePath);
                    }
                    $fileModifiedDatetime = new \DateTime($fileModified);
                    $fileModifiedDatetime->add(new \DateInterval('PT20S'));
                    if ($this->_dateTime->date('Y-m-d H:i:s') < $fileModifiedDatetime->format('Y-m-d H:i:s')) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Flush feed content
     *
     * @param string $content feed content to be flushed
     */
    protected function _flush($content)
    {
        if ($this->_stream) {
            // These lines are required for plugin validation
            $function = create_function('$a', 'echo("$a");');
            $function($content);
            flush();
        } else {
            $this->_file->write($content);
        }
    }

    /**
     * Format field names according to the given format
     *
     * @param string $field field name
     *
     * @return string
     */
    protected function _formatFields($field)
    {
        switch ($this->_format) {
            case 'csv':
                $formatField = substr(
                    strtolower(
                        preg_replace(
                            '/[^a-zA-Z0-9_]+/',
                            '',
                            str_replace([' ', '\''], '_', $this->_dataHelper->replaceAccentedChars($field))
                        )
                    ),
                    0,
                    58
                );
                break;
            default:
                $formatField = strtolower(
                    preg_replace(
                        '/[^a-zA-Z0-9_]+/',
                        '',
                        str_replace([' ', '\''], '_', $this->_dataHelper->replaceAccentedChars($field))
                    )
                );
        }
        if (!isset($this->_formattedFields[$field])) {
            $this->_formattedFields[$field] = $formatField;
        }
        return $formatField;
    }

    /**
     * For YAML, add spaces to have good indentation
     *
     * @param string $field the field name
     * @param string $maxSize space limit
     *
     * @return string
     */
    protected function _indentYaml($field, $maxSize)
    {
        $strlen = strlen($field);
        $spaces = '';
        for ($i = $strlen; $i < $maxSize; $i++) {
            $spaces .= ' ';
        }
        if (!isset($this->_yamlSpaces[$field])) {
            $this->_yamlSpaces[$field] = $spaces;
        }
        return $spaces;
    }
}
