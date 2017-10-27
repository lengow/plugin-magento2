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

namespace Lengow\Connector\Plugin;

use Magento\Config\Model\Config;
use Lengow\Connector\Helper\Data as DataHelper;

class SaveConfig
{
    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var array path for Lengow options
     */
    protected $_lengowOptions = [
        'lengow_global_options',
        'lengow_export_options',
        'lengow_import_options'
    ];

    /**
     * Constructor
     *
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     */
    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->_dataHelper = $dataHelper;
    }

    /**
     * Check and log changes on lengow data configuration
     *
     * @param \Magento\Config\Model\Config $subject Magento Config instance
     * @param \Closure $proceed
     */
    public function aroundSave(Config $subject, \Closure $proceed)
    {
        $sectionId = $subject->getSection();
        $groups = $subject->getGroups();
        if (in_array($sectionId, $this->_lengowOptions) && !empty($groups)) {
            $oldConfig = $subject->load();
            $storeId = $subject->getScopeId() != 0 ? (int)$subject->getScopeId() : false;
            foreach ($groups as $groupId => $group) {
                foreach ($group['fields'] as $fieldId => $value) {
                    if (!isset($value['value'])) {
                        continue;
                    }
                    $path = $sectionId . '/' . $groupId . '/' . $fieldId;
                    $value = is_array($value['value']) ? join(',', $value['value']) : $value['value'];
                    $oldValue = array_key_exists($path, $oldConfig) ? (string)$oldConfig[$path] : '';
                    if ($value != $oldValue) {
                        if ($fieldId == 'global_access_token' || $fieldId == 'global_secret_token') {
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
                        $this->_dataHelper->log('Config', $this->_dataHelper->setLogMessage($message, $params));
                    }
                }
            }
        }
        $proceed();
    }
}
