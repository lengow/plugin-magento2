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

use Exception;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Exception as LengowException;
use Lengow\Connector\Model\Export\File as LengowFile;
use Lengow\Connector\Model\Export\FileFactory as LengowFileFactory;

/**
 * Lengow export feed
 */
class Feed
{
    /* Feed formats */
    public const FORMAT_CSV = 'csv';
    public const FORMAT_YAML = 'yaml';
    public const FORMAT_XML = 'xml';
    public const FORMAT_JSON = 'json';

    /* Content types */
    public const HEADER = 'header';
    public const BODY = 'body';
    public const FOOTER = 'footer';

    /**
     * @var string  CSV protection
     */
    public const PROTECTION = '"';

    /**
     * @var string CSV separator
     */
    public const CSV_SEPARATOR = '|';

    /**
     * @var string end of line
     */
    public const EOL = "\r\n";

    /**
     * @var DriverFile Magento driver file instance
     */
    private $driverFile;

    /**
     * @var DateTime Magento datetime instance
     */
    private $dateTime;

    /**
     * @var JsonHelper Magento json helper instance
     */
    private $jsonHelper;

    /**
     * @var LengowFileFactory Lengow file factory instance
     */
    private $fileFactory;

    /**
     * @var LengowFile Lengow file instance
     */
    private $file;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var string feed format
     */
    private $format;

    /**
     * @var boolean generate file or not
     */
    private $stream;

    /**
     * @var array formatted fields cache for header field
     */
    private $formattedFields = [];

    /**
     * @var array yaml space cache for header field
     */
    private $yamlSpaces = [];

    /**
     * @var string folder name that contains the file
     */
    private $folderName;

    /**
     * @var string folder path that contains the file
     */
    private $folderPath;

    /**
     * @var string file name
     */
    private $fileName = 'lengow_feed';

    /**
     * Constructor
     *
     * @param DriverFile $driverFile Magento driver file instance
     * @param DateTime $dateTime Magento datetime instance
     * @param JsonHelper $jsonHelper Magento json helper instance
     * @param LengowFileFactory $fileFactory Lengow file factory instance
     * @param DataHelper $dataHelper Lengow data helper instance
     */
    public function __construct(
        DriverFile $driverFile,
        DateTime $dateTime,
        JsonHelper $jsonHelper,
        LengowFileFactory $fileFactory,
        DataHelper $dataHelper
    ) {
        $this->driverFile = $driverFile;
        $this->dateTime = $dateTime;
        $this->jsonHelper = $jsonHelper;
        $this->fileFactory = $fileFactory;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Init a new feed
     *
     * @param array $params optional options for init
     * boolean stream     generate file or not
     * string  format     feed format
     * string  store_code Magento store code
     *
     * @throws Exception|LengowException
     */
    public function init(array $params): void
    {
        $this->stream = $params['stream'];
        $this->format = $params['format'];
        $this->file = $this->fileFactory->create();
        if (!$this->stream) {
            $sep = DIRECTORY_SEPARATOR;
            $this->folderName = DataHelper::LENGOW_FOLDER . $sep . $params['store_code'];
            $this->folderPath = $this->dataHelper->getMediaPath() . $sep . $this->folderName;
            $this->initExportFile();
        }
    }

    /**
     * Write feed
     *
     * @param string $type (header, body or footer)
     * @param array $data export data
     * @param boolean|null $isFirst is first product to export
     * @param boolean|null $maxCharacter Max characters for yaml format
     *
     * @throws Exception
     */
    public function write(string $type, array $data = [], bool $isFirst = null, bool $maxCharacter = null): void
    {
        switch ($type) {
            case self::HEADER:
                if ($this->stream) {
                    header($this->getHtmlHeader());
                    if ($this->format === self::FORMAT_CSV) {
                        header('Content-Disposition: attachment; filename=feed.csv');
                    }
                }
                $header = $this->getHeader($data);
                $this->flush($header);
                break;
            case self::BODY:
                $body = $this->getBody($data, $isFirst, $maxCharacter);
                $this->flush($body);
                break;
            case self::FOOTER:
                $footer = $this->getFooter();
                $this->flush($footer);
                break;
        }
    }

    /**
     * Finalize export generation
     *
     * @throws Exception
     *
     * @return boolean
     */
    public function end(): bool
    {
        $this->write(self::FOOTER);
        if (!$this->stream) {
            $this->file->close();
            $newFileName = 'lengow_feed.' . $this->format;
            return $this->file->rename($newFileName);
        }
        return true;
    }

    /**
     * Get feed URL
     *
     * @throws Exception
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->file->getLink();
    }

    /**
     * Get folder path
     *
     * @return string
     */
    public function getFolderPath(): string
    {
        return $this->folderPath;
    }

    /**
     * Return HTML header according to the given format
     *
     * @return string
     */
    private function getHtmlHeader(): string
    {
        switch ($this->format) {
            case self::FORMAT_CSV:
            default:
                return 'Content-Type: text/csv; charset=UTF-8';
            case self::FORMAT_XML:
                return 'Content-Type: application/xml; charset=UTF-8';
            case self::FORMAT_JSON:
                return 'Content-Type: application/json; charset=UTF-8';
            case self::FORMAT_YAML:
                return 'Content-Type: text/x-yaml; charset=UTF-8';
        }
    }

    /**
     * Return feed header.
     *
     * @param array $data feed data
     *
     * @return string
     */
    private function getHeader(array $data): string
    {
        switch ($this->format) {
            case self::FORMAT_CSV:
            default:
                $header = '';
                foreach ($data as $field) {
                    $header .= self::PROTECTION . $this->formatFields($field) . self::PROTECTION . self::CSV_SEPARATOR;
                }
                return rtrim($header, self::CSV_SEPARATOR) . self::EOL;
            case self::FORMAT_XML:
                return '<?xml version="1.0" encoding="UTF-8"?>' . self::EOL . '<catalog>' . self::EOL;
            case self::FORMAT_JSON:
                return '{"catalog":[';
            case self::FORMAT_YAML:
                return '"catalog":' . self::EOL;
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
    private function getBody(array $data, bool $isFirst, int $maxCharacter): string
    {
        switch ($this->format) {
            case self::FORMAT_CSV:
            default:
                $content = '';
                foreach ($data as $value) {
                    $content .= self::PROTECTION . $value . self::PROTECTION . self::CSV_SEPARATOR;
                }
                return rtrim($content, self::CSV_SEPARATOR) . self::EOL;
            case self::FORMAT_XML:
                $content = '<product>';
                foreach ($data as $field => $value) {
                    $field = $this->formattedFields[$field] ?? $this->formatFields($field);
                    $content .= '<' . $field . '><![CDATA[' . $value . ']]></' . $field . '>' . self::EOL;
                }
                $content .= '</product>' . self::EOL;
                return $content;
            case self::FORMAT_JSON:
                $content = $isFirst ? '' : ',';
                $jsonArray = [];
                foreach ($data as $field => $value) {
                    $field = $this->formattedFields[$field] ?? $this->formatFields($field);
                    $jsonArray[$field] = $value;
                }
                $content .= $this->jsonHelper->jsonEncode($jsonArray);
                return $content;
            case self::FORMAT_YAML:
                $maxCharacter += ($maxCharacter % 2 === 1 ? 1 : 2);
                $content = '  ' . self::PROTECTION . 'product' . self::PROTECTION . ':' . self::EOL;
                foreach ($data as $field => $value) {
                    $field = $this->formattedFields[$field] ?? $this->formatFields($field);
                    $content .= '    ' . self::PROTECTION . $field . self::PROTECTION . ':';
                    $yamlSpace = $this->yamlSpaces[$field] ?? $this->indentYaml($field, $maxCharacter);
                    $content .= $yamlSpace . $value . self::EOL;
                }
                return $content;
        }
    }

    /**
     * Return feed footer
     *
     * @return string
     */
    private function getFooter(): string
    {
        switch ($this->format) {
            case self::FORMAT_XML:
                return '</catalog>';
            case self::FORMAT_JSON:
                return ']}';
            default:
                return '';
        }
    }

    /**
     * Create export file
     *
     * @throws Exception|LengowException
     */
    private function initExportFile(): void
    {
        try {
            $this->driverFile->createDirectory($this->folderPath);
        } catch (Exception $e) {
            throw new LengowException(
                $this->dataHelper->setLogMessage('unable to create folder %1', [$this->folderPath])
            );
        }
        if ($this->isAlreadyLaunch()) {
            throw new LengowException($this->dataHelper->setLogMessage('feed already launched'));
        }
        $fileName = $this->fileName . '.' . time() . '.' . $this->format;
        $this->file->init(['folder_name' => $this->folderName, 'file_name' => $fileName]);
    }

    /**
     * Is Feed Already Launch
     *
     * @throws Exception
     *
     * @return boolean
     */
    private function isAlreadyLaunch(): bool
    {
        $listFiles = $this->driverFile->readDirectory($this->folderPath);
        if (!empty($listFiles)) {
            foreach ($listFiles as $filePath) {
                $fileName = str_replace($this->folderPath . '/', '', $filePath);
                if (preg_match('/^' . $this->fileName . '\.[\d]{10}/', $fileName)) {
                    $fileModified = $this->dateTime->gmtDate(DataHelper::DATE_FULL, filemtime($filePath));
                    $fileModifiedDatetime = new \DateTime($fileModified);
                    $fileModifiedDatetime->add(new \DateInterval('P5D'));
                    $fileModifiedDateDay = $fileModifiedDatetime->format(DataHelper::DATE_DAY);
                    if ($this->dateTime->gmtDate(DataHelper::DATE_DAY) > $fileModifiedDateDay) {
                        $this->driverFile->deleteFile($filePath);
                    }
                    $fileModifiedDatetime = new \DateTime($fileModified);
                    $fileModifiedDatetime->add(new \DateInterval('PT20S'));
                    $fileModifiedDateFull = $fileModifiedDatetime->format(DataHelper::DATE_FULL);
                    if ($this->dateTime->gmtDate(DataHelper::DATE_FULL) < $fileModifiedDateFull) {
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
     *
     * @throws Exception
     */
    private function flush(string $content): void
    {
        if ($this->stream) {
            print_r($content);
            flush();
        } else {
            $this->file->write($content);
        }
    }

    /**
     * Format field names according to the given format
     *
     * @param string $field field name
     *
     * @return string
     */
    private function formatFields(string $field): string
    {
        if ($this->format === self::FORMAT_CSV) {
            $formatField = strtolower(
                substr(
                    preg_replace(
                        '/[^a-zA-Z0-9_]+/',
                        '',
                        str_replace([' ', '\''], '_', $this->dataHelper->replaceAccentedChars($field))
                    ),
                    0,
                    58
                )
            );
        } else {
            $formatField = strtolower(
                preg_replace(
                    '/[^a-zA-Z0-9_]+/',
                    '',
                    str_replace([' ', '\''], '_', $this->dataHelper->replaceAccentedChars($field))
                )
            );
        }
        if (!isset($this->formattedFields[$field])) {
            $this->formattedFields[$field] = $formatField;
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
    private function indentYaml(string $field, string $maxSize): string
    {
        $strlen = strlen($field);
        $spaces = '';
        for ($i = $strlen; $i < $maxSize; $i++) {
            $spaces .= ' ';
        }
        if (!isset($this->yamlSpaces[$field])) {
            $this->yamlSpaces[$field] = $spaces;
        }
        return $spaces;
    }
}
