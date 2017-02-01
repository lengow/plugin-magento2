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
 * @subpackage  Helper
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    /**
     * Set message with parameters for translation
     *
     * @param string $key    log key
     * @param array  $params log parameters
     *
     * @return string
     */
    public function setLogMessage($key, $params = null)
    {
        if (is_null($params) || (is_array($params) && count($params) == 0)) {
            return $key;
        }
        $allParams = [];
        foreach ($params as $value) {
            $value = str_replace('|', '', $value);
            $allParams[] = $value;
        }
        $message = $key.'['.join('|', $allParams).']';
        return $message;
    }

    /**
     * Decode message with params for translation
     *
     * @param string $message log message
     * @param array  $params  log parameters
     *
     * @return string
     */
    public function decodeLogMessage($message, $params = null)
    {
        if (preg_match('/^([^\[\]]*)(\[(.*)\]|)$/', $message, $result)) {
            if (isset($result[1])) {
                $key = $result[1];
                if (isset($result[3]) && is_null($params)) {
                    $strParam = $result[3];
                    $params = explode('|', $strParam);
                }
                $phrase = __($key, $params);
                $message = $phrase->__toString();
            }
        }
        return $message;
    }
}

