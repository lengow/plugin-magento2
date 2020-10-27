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
 * @subpackage  Plugin
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Plugin;

use Magento\Config\Model\Config;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;

class SaveConfig
{
    /**
     * @var DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var array path for Lengow options
     */
    protected $_lengowOptions = [
        'lengow_global_options',
        'lengow_export_options',
        'lengow_import_options',
    ];

    /**
     * @var array Secret settings list to hide
     */
    protected $_secretSettings = [
        'global_access_token',
        'global_secret_token',
    ];

    /**
     * @var array list of settings for the date of the last update
     */
    protected $_updatedSettings = [
        'global_catalog_id',
        'import_days',
    ];

    /**
     * Constructor
     *
     * @param DateTime $dateTime Magento datetime instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     */
    public function __construct(
        DateTime $dateTime,
        DataHelper $dataHelper,
        ConfigHelper $configHelper
    ) {
        $this->_dateTime = $dateTime;
        $this->_dataHelper = $dataHelper;
        $this->_configHelper = $configHelper;
    }

    /**
     * Check and log changes on lengow data configuration
     *
     * @param Config $subject Magento Config instance
     * @param \Closure $proceed
     */
    public function aroundSave(Config $subject, \Closure $proceed)
    {
        $sectionId = $subject->getSection();
        $groups = $subject->getGroups();
        if (in_array($sectionId, $this->_lengowOptions) && !empty($groups)) {
            $oldConfig = $subject->load();
            $storeId = (int)$subject->getScopeId() !== 0 ? (int)$subject->getScopeId() : false;
            foreach ($groups as $groupId => $group) {
                foreach ($group['fields'] as $fieldId => $value) {
                    if (!isset($value['value'])) {
                        continue;
                    }
                    $path = $sectionId . '/' . $groupId . '/' . $fieldId;
                    $value = is_array($value['value']) ? join(',', $value['value']) : $value['value'];
                    $oldValue = array_key_exists($path, $oldConfig) ? (string)$oldConfig[$path] : '';
                    if ($value != $oldValue) {
                        if (in_array($fieldId, $this->_secretSettings)) {
                            $value = preg_replace("/[a-zA-Z0-9]/", '*', $value);
                            $oldValue = preg_replace("/[a-zA-Z0-9]/", '*', $oldValue);
                        }
                        if ($storeId) {
                            $message = '%1 - old value %2 replaced with %3 for store %4';
                            $params = [$path, $oldValue, $value, $storeId];
                        } else {
                            $message = '%1 - old value %2 replaced with %3';
                            $params = [$path, $oldValue, $value];
                        }
                        $this->_dataHelper->log(
                            DataHelper::CODE_SETTING,
                            $this->_dataHelper->setLogMessage($message, $params)
                        );
                        // save last update date for a specific settings (change synchronisation interval time)
                        if (in_array($fieldId, $this->_updatedSettings)) {
                            $this->_configHelper->set('last_setting_update', time());
                        }
                    }
                }
            }
        }
        $proceed();
    }
}
