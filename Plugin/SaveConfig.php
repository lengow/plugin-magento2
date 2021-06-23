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

use Closure;
use Magento\Config\Model\Config;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;

class SaveConfig
{
    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $dataHelper;

    /**
     * @var array path for Lengow options
     */
    protected $_lengowOptions = [
        'lengow_global_options',
        'lengow_export_options',
        'lengow_import_options',
    ];

    /**
     * Constructor
     *
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     */
    public function __construct(DataHelper $dataHelper, ConfigHelper $configHelper)
    {
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * Check and log changes on lengow data configuration
     *
     * @param Config $subject Magento Config instance
     * @param Closure $proceed
     */
    public function aroundSave(Config $subject, Closure $proceed)
    {
        $sectionId = $subject->getSection();
        $groups = $subject->getGroups();
        if (!empty($groups) && in_array($sectionId, $this->_lengowOptions, true)) {
            $oldConfig = $subject->load();
            $storeId = $subject->getScopeId() !== 0 ? $subject->getScopeId() : false;
            foreach ($groups as $groupId => $group) {
                foreach ($group['fields'] as $fieldId => $value) {
                    $keyParams = ConfigHelper::$lengowSettings[$fieldId];
                    if (!isset($value['value'])
                        || (isset($keyParams[ConfigHelper::PARAM_LOG]) && !$keyParams[ConfigHelper::PARAM_LOG])
                    ) {
                        continue;
                    }
                    $path = $sectionId . '/' . $groupId . '/' . $fieldId;
                    $value = is_array($value['value']) ? implode(',', $value['value']) : $value['value'];
                    $oldValue = isset($oldConfig[$path]) ? (string) $oldConfig[$path] : '';
                    if ($value !== $oldValue) {
                        if (isset($keyParams[ConfigHelper::PARAM_SECRET]) && $keyParams[ConfigHelper::PARAM_SECRET]) {
                            $value = preg_replace("/[a-zA-Z0-9]/", '*', $value);
                            $oldValue = preg_replace("/[a-zA-Z0-9]/", '*', $oldValue);
                        }
                        $genericParamKey = ConfigHelper::$genericParamKeys[$fieldId];
                        if ($storeId) {
                            $message = '%1 - old value %2 replaced with %3 for store %4';
                            $params = [$genericParamKey, $oldValue, $value, $storeId];
                        } else {
                            $message = '%1 - old value %2 replaced with %3';
                            $params = [$genericParamKey, $oldValue, $value];
                        }
                        $this->dataHelper->log(
                            DataHelper::CODE_SETTING,
                            $this->dataHelper->setLogMessage($message, $params)
                        );
                        // save last update date for a specific settings (change synchronisation interval time)
                        if (isset($keyParams[ConfigHelper::PARAM_UPDATE]) && $keyParams[ConfigHelper::PARAM_UPDATE]) {
                            $this->configHelper->set(ConfigHelper::LAST_UPDATE_SETTING, time());
                        }
                    }
                }
            }
        }
        $proceed();
    }
}
