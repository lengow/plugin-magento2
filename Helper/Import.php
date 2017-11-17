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

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Import\OrdererrorFactory;
use Lengow\Connector\Model\Import\Marketplace;
use Lengow\Connector\Model\Import\OrderFactory;

class Import extends AbstractHelper
{
    /**
     * @var \Magento\Backend\Model\UrlInterface Backend url interface
     */
    protected $_urlBackend;

    /**
     * @var \Lengow\Connector\Helper\Config Lengow config helper instance
     */
    protected $_configHelper;

    /**
     * @var array marketplaces collection
     */
    public static $marketplaces = [];

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime Magento datetime instance
     */
    protected $_dateTime;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Model\Import\OrdererrorFactory Lengow order error factory instance
     */
    protected $_orderErrorFactory;

    /**
     * @var \Lengow\Connector\Model\Import\Marketplace Lengow marketplace instance
     */
    protected $_marketplace;

    /**
     * @var \Lengow\Connector\Model\Import\OrderFactory Lengow import order factory instance
     */
    protected $_orderFactory;

    /**
     * @var array valid states lengow to create a Lengow order
     */
    protected $_lengowStates = [
        'waiting_shipment',
        'shipped',
        'closed'
    ];

    /**
     * Constructor
     *
     * @param \Magento\Backend\Model\UrlInterface $urlBackend Backend url interface
     * @param \Magento\Framework\App\Helper\Context $context Magento context instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Helper\Config $configHelper Lengow config helper instance
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime Magento datetime instance
     * @param \Lengow\Connector\Model\Import\OrdererrorFactory $ordererrorFactory Lengow order error factory instance
     * @param \Lengow\Connector\Model\Import\Marketplace $marketplace Lengow marketplace instance
     * @param \Lengow\Connector\Model\Import\OrderFactory $lengowOrder Lengow import order factory instance
     */
    public function __construct(
        UrlInterface $urlBackend,
        Context $context,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        OrdererrorFactory $ordererrorFactory,
        DateTime $dateTime,
        Marketplace $marketplace,
        OrderFactory $lengowOrder
    )
    {
        $this->_urlBackend = $urlBackend;
        $this->_configHelper = $configHelper;
        $this->_dataHelper = $dataHelper;
        $this->_dateTime = $dateTime;
        $this->_orderErrorFactory = $ordererrorFactory;
        $this->_marketplace = $marketplace;
        $this->_orderFactory = $lengowOrder;
        parent::__construct($context);
    }

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

    /**
     * Record the date of the last import
     *
     * @param string $type last import type (cron or manual)
     *
     * @return boolean
     */
    public function updateDateImport($type)
    {
        if ($type === 'cron' || $type === 'magento cron') {
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
            if ((int)$timestampCron > (int)$timestampManual) {
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
     * Get last import to print
     *
     * @return string
     */
    public function getLastImportDatePrint()
    {
        $lastImport = $this->getLastImport();
        $lastImportDate = $this->_dataHelper->getDateInCorrectFormat(time());
        if ($lastImport['type'] != 'none') {
            return $this->_dataHelper->decodeLogMessage(
                $this->_dataHelper->setLogMessage(
                    'Last synchronisation : %1',
                    ['<b>' . $lastImportDate . '</b>']
                )
            );
        } else {
            return $this->_dataHelper->decodeLogMessage(
                $this->_dataHelper->setLogMessage('No synchronisation for now')
            );
        }
    }

    /**
     * Get number order in error to print
     *
     * @return string
     */
    public function getOrderWithErrorPrint()
    {
        return $this->_dataHelper->decodeLogMessage(
            $this->_dataHelper->setLogMessage(
                'You have %1 order(s) with errors',
                [$this->_orderFactory->create()->countOrderWithError()]
            )
        );
    }

    /**
     * Get number order to sent to print
     *
     * @return string
     */
    public function getOrderToBeSentPrint()
    {
        return $this->_dataHelper->decodeLogMessage(
            $this->_dataHelper->setLogMessage(
                'You have %1 order(s) waiting to be sent',
                [$this->_orderFactory->create()->countOrderToBeSent()]
            )
        );
    }

    /**
     * Get report email to print
     *
     * @return string
     */
    public function getReportMailPrint()
    {
        $reportMailPrint = '';
        $reportMailActive = (bool)$this->_configHelper->get('report_mail_enable');
        $reportMailLink = $this->_urlBackend->getUrl('adminhtml/system_config/edit/section/lengow_import_options/');
        $reportMails = $this->_configHelper->getReportEmailAddress();

        if ($reportMailActive) {
            $reportMailPrint .= $this->_dataHelper->setLogMessage('All order issue reports will be sent by mail to') . ' ';
            $reportMailPrint .= implode(', ', $reportMails) . ' ';
        } else {
            $reportMailPrint .= $this->_dataHelper->setLogMessage('No order issue reports will be sent by mail') . ' ';
        }
        $reportMailPrint .= '(<a href="' . $reportMailLink . '">' .
            $this->_dataHelper->setLogMessage('Change this?') . '</a>)';

        return $reportMailPrint;
    }

    /**
     * Get Marketplace singleton
     *
     * @param string $name marketplace name
     *
     * @return \Lengow\Connector\Model\Import\Marketplace
     */
    public function getMarketplaceSingleton($name)
    {
        if (!array_key_exists($name, self::$marketplaces)) {
            $this->_marketplace->init(['name' => $name]);
            self::$marketplaces[$name] = $this->_marketplace;
        }
        return self::$marketplaces[$name];
    }

    /**
     * Check if order status is valid for import
     *
     * @param string $orderStateMarketplace order state
     * @param Marketplace $marketplace order marketplace
     *
     * @return boolean
     */
    public function checkState($orderStateMarketplace, $marketplace)
    {
        if (empty($orderStateMarketplace)) {
            return false;
        }
        if (!in_array($marketplace->getStateLengow($orderStateMarketplace), $this->_lengowStates)) {
            return false;
        }
        return true;
    }

    /**
     * Check logs table and send mail for order not imported correctly
     *
     * @param boolean $logOutput see log or not
     */
    public function sendMailAlert($logOutput = false)
    {
        $errors = $this->_orderErrorFactory->create()->getImportErrors();
        if ($errors) {
            $subject = $this->_dataHelper->decodeLogMessage('Lengow imports errors');
            $support = $this->_dataHelper->decodeLogMessage(
                'no error message, contact support via https://supportlengow.zendesk.com/agent/'
            );
            $mailBody = '<h2>' . $subject . '</h2><p><ul>';
            foreach ($errors as $error) {
                $order = $this->_dataHelper->decodeLogMessage('Order %1', true, [$error['marketplace_sku']]);
                $message = $error['message'] != '' ? $this->_dataHelper->decodeLogMessage($error['message']) : $support;
                $mailBody .= '<li>' . $order . ' - ' . $message . '</li>';
                $orderError = $this->_orderErrorFactory->create()->load((int)$error['id']);
                $orderError->updateOrderError(['mail' => 1]);
                unset($orderError, $order, $message);
            }
            $mailBody .= '</ul></p>';
            $emails = $this->_configHelper->getReportEmailAddress();
            foreach ($emails as $email) {
                if (strlen($email) > 0) {
                    $mail = new \Zend_Mail();
                    $mail->setSubject($subject);
                    $mail->setBodyHtml($mailBody);
                    $mail->setFrom(
                        $this->scopeConfig->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE),
                        'Lengow'
                    );
                    $mail->addTo($email);
                    try {
                        $mail->send();
                        $this->_dataHelper->log(
                            'MailReport',
                            $this->_dataHelper->setLogMessage('report email sent to %1', [$email]),
                            $logOutput
                        );
                    } catch (\Exception $e) {
                        $this->_dataHelper->log(
                            'MailReport',
                            $this->_dataHelper->setLogMessage('unable to send report email to %1', [$email]),
                            $logOutput
                        );
                    }
                    unset($mail, $email);
                }
            }
        }
    }
}
