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
use Lengow\Connector\Helper\Data as DataHelper;

/**
 * Lengow export file
 */
class File
{
    /**
     * @var DriverFile Magento driver file instance
     */
    private $driverFile;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var resource file
     */
    private $fileInstance;

    /**
     * @var string file name
     */
    private $fileName;

    /**
     * @var string folder name that contains the file
     */
    private $folderName;

    /**
     * @var string file link
     */
    private $link;

    /**
     * Constructor
     *
     * @param DriverFile $driverFile Magento driver file instance
     * @param DataHelper $dataHelper Lengow data helper instance
     */
    public function __construct(
        DriverFile $driverFile,
        DataHelper $dataHelper
    ) {
        $this->driverFile = $driverFile;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Init a new file
     *
     * @param array $params optional options for init
     * string folder_name Lengow export folder name
     * string file_name   Lengow export file name
     *
     * @throws Exception
     */
    public function init(array $params): void
    {
        $this->folderName = $params['folder_name'];
        $this->fileName = $params['file_name'];
        $this->fileInstance = $this->getFileResource($this->getPath());
    }

    /**
     * Write content in file
     *
     * @param string $data data to be written
     *
     * @throws Exception
     */
    public function write(string $data): void
    {
        if (is_resource($this->fileInstance)) {
            $this->driverFile->fileLock($this->fileInstance);
            $this->driverFile->fileWrite($this->fileInstance, $data);
            $this->driverFile->fileUnlock($this->fileInstance);
        }
    }

    /**
     * Write content in file
     *
     * @throws Exception
     */
    public function close(): void
    {
        if (is_resource($this->fileInstance)) {
            $this->driverFile->fileClose($this->fileInstance);
        }
    }

    /**
     * Rename file
     *
     * @param string $newFileName new file name
     *
     * @return boolean
     *
     * @throws Exception
     */
    public function rename(string $newFileName): bool
    {
        $sep = DIRECTORY_SEPARATOR;
        $oldPath = $this->getPath();
        $newPath = $this->dataHelper->getMediaPath() . $sep . $this->folderName . $sep . $newFileName;
        if ($this->fileExists($newPath)) {
            $this->driverFile->deleteFile($newPath);
        }
        $success = $this->driverFile->rename($oldPath, $newPath);
        if ($success) {
            $this->fileName = $newFileName;
            return true;
        }
        return false;
    }

    /**
     * Get file link
     *
     * @return string
     *
     * @throws Exception
     */
    public function getLink(): string
    {
        if (empty($this->link) && $this->fileExists()) {
            $sep = DIRECTORY_SEPARATOR;
            $this->link = $this->dataHelper->getMediaUrl() . $this->folderName . $sep . $this->fileName;
        }
        return $this->link;
    }

    /**
     * Get file path
     *
     * @return string
     */
    public function getPath(): string
    {
        $sep = DIRECTORY_SEPARATOR;
        return $this->dataHelper->getMediaPath() . $sep . $this->folderName . $sep . $this->fileName;
    }

    /**
     * Get resource of a given stream
     *
     * @param string $path file path
     *
     * @return resource
     *
     * @throws Exception
     */
    private function getFileResource(string $path)
    {
        return $this->driverFile->fileOpen($path, 'a+');
    }

    /**
     * Check if current file exists
     *
     * @param string|null $filePath file path
     *
     * @return boolean
     *
     * @throws Exception
     */
    private function fileExists(string $filePath = null): bool
    {
        $filePath = $filePath ?? $this->getPath();
        return $this->driverFile->isExists($filePath);
    }
}
