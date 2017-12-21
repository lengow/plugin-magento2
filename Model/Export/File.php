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
use Lengow\Connector\Model\Exception as LengowException;
use Lengow\Connector\Helper\Data as DataHelper;

/**
 * Lengow export file
 */
class File
{
    /**
     * @var \Magento\Framework\Filesystem\Driver\File Magento driver file instance
     */
    protected $_driverFile;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var resource file
     */
    protected $_fileInstance;

    /**
     * @var string file name
     */
    protected $_fileName;

    /**
     * @var string folder name that contains the file
     */
    protected $_folderName;

    /**
     * @var string file link
     */
    protected $_link;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Filesystem\Driver\File $driverFile Magento driver file instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     */
    public function __construct(
        DriverFile $driverFile,
        DataHelper $dataHelper
    ) {
        $this->_driverFile = $driverFile;
        $this->_dataHelper = $dataHelper;
    }

    /**
     * Init a new file
     *
     * @param array $params optional options for init
     * string folder_name Lengow export folder name
     * string file_name   Lengow export file name
     *
     * @throws \Exception
     */
    public function init($params)
    {
        $this->_folderName = $params['folder_name'];
        $this->_fileName = $params['file_name'];
        $this->_fileInstance = $this->_getFileResource($this->getPath());
    }

    /**
     * Write content in file
     *
     * @param string $data data to be written
     *
     * @throws \Exception
     */
    public function write($data)
    {
        if (is_resource($this->_fileInstance)) {
            $this->_driverFile->fileLock($this->_fileInstance);
            $this->_driverFile->fileWrite($this->_fileInstance, $data);
            $this->_driverFile->fileUnlock($this->_fileInstance);
        }
    }

    /**
     * Write content in file
     *
     * @throws \Exception
     */
    public function close()
    {
        if (is_resource($this->_fileInstance)) {
            $this->_driverFile->fileClose($this->_fileInstance);
        }
    }

    /**
     * Rename file
     *
     * @param string $newFileName new file name
     *
     * @throws \Exception
     *
     * @return boolean
     */
    public function rename($newFileName)
    {
        $sep = DIRECTORY_SEPARATOR;
        $oldPath = $this->getPath();
        $newPath = $this->_dataHelper->getMediaPath() . $sep . $this->_folderName . $sep . $newFileName;
        if ($this->_fileExists($newPath)) {
            $this->_driverFile->deleteFile($newPath);
        }
        $success = $this->_driverFile->rename($oldPath, $newPath);
        if ($success) {
            $this->_fileName = $newFileName;
            return true;
        }
        return false;
    }

    /**
     * Get file link
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getLink()
    {
        if (empty($this->_link) && $this->_fileExists()) {
            $sep = DIRECTORY_SEPARATOR;
            $this->_link = $this->_dataHelper->getMediaUrl() . $this->_folderName . $sep . $this->_fileName;
        }
        return $this->_link;
    }

    /**
     * Get file path
     *
     * @return string
     */
    public function getPath()
    {
        $sep = DIRECTORY_SEPARATOR;
        return $this->_dataHelper->getMediaPath() . $sep . $this->_folderName . $sep . $this->_fileName;
    }

    /**
     * Get resource of a given stream
     *
     * @param string $path file path
     *
     * @throws \Exception
     *
     * @return resource
     */
    protected function _getFileResource($path)
    {
        return $this->_driverFile->fileOpen($path, 'a+');
    }

    /**
     * Check if current file exists
     *
     * @param string $filePath file path
     *
     * @throws \Exception
     *
     * @return boolean
     */
    protected function _fileExists($filePath = null)
    {
        $filePath = !is_null($filePath) ? $filePath : $this->getPath();
        return $this->_driverFile->isExists($filePath);
    }

}
