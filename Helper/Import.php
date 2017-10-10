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
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\ObjectManager;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Import\Ordererror;

class Import extends AbstractHelper
{
    /**
     * @var ConfigHelper Lengow config helper instance
     */
    protected $_configHelper = null;

    /**
     * @var array marketplaces collection
     */
    public static $marketplaces = [];

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var \Magento\Framework\App\ObjectManager Magento object manager instance
     */
    protected $_objectManager;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Model\Import\Ordererror Lengow ordererror instance
     */
    protected $_orderError;

    /**
     * @var array valid states lengow to create a Lengow order
     */
    protected $_lengowStates = [
        'accepted',
        'waiting_shipment',
        'shipped',
        'closed'
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context Magento context instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime Magento datetime instance
     * @param \Lengow\Connector\Model\Import\Ordererror $orderError Lengow orderError instance
     * @param \Magento\Framework\App\ObjectManager $objectManager Magento object manager
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        Ordererror $orderError,
        DateTime $dateTime,
        ObjectManager $objectManager
    ) {
        $this->_configHelper = $configHelper;
        $this->_dataHelper = $dataHelper;
        $this->_dateTime = $dateTime;
        $this->_orderError = $orderError;
        $this->_objectManager = $objectManager;
        parent::__construct($context);
    }

//    /**
//     * Get Marketplace singleton
//     *
//     * @param string  $name    markeplace name
//     * @param integer $storeId Magento store Id
//     *
//     * @return array Lengow marketplace
//     */
//    public static function getMarketplaceSingleton($name, $storeId = null)
//    {
//        if (!array_key_exists($name, self::$marketplaces)) {
//            self::$marketplaces[$name] = Mage::getModel(
//                'lengow/import_marketplace',
//                [
//                    'name'     => $name,
//                    'store_id' => $storeId
//                ]
//            );
//        }
//        return self::$marketplaces[$name];
//    }

    /**
     * Check if import is already in process
     *
     * @return boolean
     */
    public function importIsInProcess()
    {
        $timestamp = $this->_configHelper->get('import_in_progress');
        if ($timestamp > 0) {
            // security check : if last import is more than 60 seconds old => authorize new import to be launched
            if (($timestamp + (60 * 1)) < time()) {
                $this->setImportEnd();
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Get Rest time to make re import order
     *
     * @return boolean
     */
    public function restTimeToImport()
    {
        $timestamp = $this->_configHelper->get('import_in_progress');
        if ($timestamp > 0) {
            return $timestamp + (60 * 1) - time();
        }
        return false;
    }

    /**
     * Set import to "in process" state
     *
     * @return boolean
     */
    public function setImportInProcess()
    {
        return $this->_configHelper->set('import_in_progress', time());
    }

    /**
     * Set import to finished
     *
     * @return boolean
     */
    public function setImportEnd()
    {
        return $this->_configHelper->set('import_in_progress', -1);
    }
//
//    /**
//     * Check if order status is valid for import
//     *
//     * @param string                                    $orderStateMarketplace order state
//     * @param Lengow_Connector_Model_Import_Marketplace $marketplace           order marketplace
//     *
//     * @return boolean
//     */
//    public function checkState($orderStateMarketplace, $marketplace)
//    {
//        if (empty($orderStateMarketplace)) {
//            return false;
//        }
//        if (!in_array($marketplace->getStateLengow($orderStateMarketplace), $this->_lengowStates)) {
//            return false;
//        }
//        return true;
//    }

    /**
     * Record the date of the last import
     *
     * @param string $type last import type (cron or manual)
     *
     * @return boolean
     */
    public function updateDateImport($type)
    {
        if ($type === 'cron') {
            $this->_configHelper->set('last_import_cron', $this->_dateTime->gmtTimestamp());
        } else {
            $this->_configHelper->set('last_import_manual', $this->_dateTime->gmtTimestamp());
        }
        return true;
    }

    /**
     * Get last import (type and timestamp)
     *
     * @return array
     */
    public function getLastImport()
    {
        $timestampCron = $this->_configHelper->get('last_import_cron');
        $timestampManual = $this->_configHelper->get('last_import_manual');

        if ($timestampCron && $timestampManual) {
            if ((int)$timestampCron > (int) $timestampManual) {
                return ['type' => 'cron', 'timestamp' => (int)$timestampCron];
            } else {
                return ['type' => 'manual', 'timestamp' => (int)$timestampManual];
            }
        } elseif ($timestampCron && !$timestampManual) {
            return ['type' => 'cron', 'timestamp' => (int)$timestampCron];
        } elseif ($timestampManual && !$timestampCron) {
            return ['type' => 'manual', 'timestamp' => (int)$timestampManual];
        }

        return ['type' => 'none', 'timestamp' => 'none'];
    }

    /**
     * Check logs table and send mail for order not imported correctly
     *
     * @param  boolean $logOutput see log or not
     */
    public function sendMailAlert($logOutput = false)
    {
        $subject = '<h2>'.$this->_dataHelper->decodeLogMessage('lengow_log.mail_report.subject_report_mail').'</h2>';
        $mailBody = '<p><ul>';
        $errors = $this->_orderError->getImportErrors();
        if ($errors) {
            foreach ($errors as $error) {
                $mailBody.= '<li>'.$this->_dataHelper->decodeLogMessage(
                        'lengow_log.mail_report.order',
                        null,
                        [
                            'marketplace_sku' => $error['marketplace_sku']
                        ]
                    );
                if ($error['message'] != '') {
                    $mailBody.= ' - '.$this->_dataHelper->decodeLogMessage($error['message']);
                } else {
                    $mailBody.= ' - '.$this->_dataHelper->decodeLogMessage('lengow_log.mail_report.no_error_in_report_mail');
                }
                $mailBody.= '</li>';
                $orderError = $this->_orderError->load($error['id']);
                $orderError->updateOrderError(['mail' => 1]);
                unset($orderError);
            }
            $mailBody .=  '</ul></p>';
            $emails = $this->_configHelper->getReportEmailAddress();
            foreach ($emails as $email) {
                if (strlen($email) > 0) {
                    $mail = $this->_objectManager->create('core/email');
                    $mail->setToEmail($email);
                    $mail->setBody($mailBody);
                    $mail->setSubject($subject);
                    $mail->setFromEmail($this->scopeConfig->getValue('trans_email/ident_general/email'));
                    $mail->setFromName("Lengow");
                    $mail->setType('html');
                    try {
                        $mail->send();
                        $this->_dataHelper->log(
                            'MailReport',
                            $this->_dataHelper->setLogMessage('log.mail_report.send_mail_to', ['email' => $email]),
                            $logOutput
                        );
                    } catch (\Exception $e) {
                        $this->_dataHelper->log(
                            'MailReport',
                            $this->_dataHelper->setLogMessage('log.mail_report.unable_send_mail_to', ['email' => $email]),
                            $logOutput
                        );
                    }
                    unset($mail);
                }
            }
        }
    }
}
