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
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Import as LengowImport;
use Lengow\Connector\Model\Import\Marketplace as LengowMarketplace;
use Lengow\Connector\Model\Import\MarketplaceFactory as LengowMarketplaceFactory;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;
use Lengow\Connector\Model\Import\OrdererrorFactory as LengowOrderErrorFactory;
use Lengow\Connector\Model\Exception as LengowException;

class Import extends AbstractHelper
{
    /**
     * @var array marketplaces collection
     */
    public static $marketplaces = [];

    /**
     * @var UrlInterface Backend url interface
     */
    protected $_urlBackend;

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
     * @var LengowMarketplaceFactory Lengow marketplace factory instance
     */
    protected $_marketplaceFactory;

    /**
     * @var LengowOrderFactory Lengow import order factory instance
     */
    protected $_orderFactory;

    /**
     * @var LengowOrderErrorFactory Lengow order error factory instance
     */
    protected $_orderErrorFactory;

    /**
     * @var array valid states lengow to create a Lengow order
     */
    protected $_lengowStates = [
        LengowOrder::STATE_WAITING_SHIPMENT,
        LengowOrder::STATE_SHIPPED,
        LengowOrder::STATE_CLOSED,
    ];

    /**
     * Constructor
     *
     * @param UrlInterface $urlBackend Backend url interface
     * @param Context $context Magento context instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param DateTime $dateTime Magento datetime instance
     * @param LengowOrderErrorFactory $orderErrorFactory Lengow order error factory instance
     * @param LengowMarketplaceFactory $marketplaceFactory Lengow marketplace factory instance
     * @param LengowOrderFactory $lengowOrder Lengow import order factory instance
     */
    public function __construct(
        UrlInterface $urlBackend,
        Context $context,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        LengowOrderErrorFactory $orderErrorFactory,
        DateTime $dateTime,
        LengowMarketplaceFactory $marketplaceFactory,
        LengowOrderFactory $lengowOrder
    ) {
        $this->_urlBackend = $urlBackend;
        $this->_configHelper = $configHelper;
        $this->_dataHelper = $dataHelper;
        $this->_dateTime = $dateTime;
        $this->_orderErrorFactory = $orderErrorFactory;
        $this->_marketplaceFactory = $marketplaceFactory;
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
     */
    public function setImportInProcess()
    {
        $this->_configHelper->set('import_in_progress', time());
    }

    /**
     * Set import to finished
     */
    public function setImportEnd()
    {
        $this->_configHelper->set('import_in_progress', -1);
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
        if ($type === LengowImport::TYPE_CRON || $type === LengowImport::TYPE_MAGENTO_CRON) {
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
                return ['type' => LengowImport::TYPE_CRON, 'timestamp' => (int)$timestampCron];
            } else {
                return ['type' => LengowImport::TYPE_MANUAL, 'timestamp' => (int)$timestampManual];
            }
        } elseif ($timestampCron && !$timestampManual) {
            return ['type' => LengowImport::TYPE_CRON, 'timestamp' => (int)$timestampCron];
        } elseif ($timestampManual && !$timestampCron) {
            return ['type' => LengowImport::TYPE_MANUAL, 'timestamp' => (int)$timestampManual];
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
        if ($lastImport['type'] !== 'none') {
            $lastImportDate = $this->_dataHelper->getDateInCorrectFormat($lastImport['timestamp']);
            return $this->_dataHelper->decodeLogMessage(
                $this->_dataHelper->setLogMessage('Last synchronisation : %1', ['<b>' . $lastImportDate . '</b>'])
            );
        } else {
            return $this->_dataHelper->decodeLogMessage(
                $this->_dataHelper->setLogMessage('No synchronisation for now')
            );
        }
    }

    /**
     * Get number orders in error to print
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
     * Get number orders to sent to print
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
            $reportMailPrint .= $this->_dataHelper->decodeLogMessage(
                $this->_dataHelper->setLogMessage('All order issue reports will be sent by mail to')
            );
            $reportMailPrint .= ' ' . implode(', ', $reportMails);
        } else {
            $reportMailPrint .= $this->_dataHelper->decodeLogMessage(
                $this->_dataHelper->setLogMessage('No order issue reports will be sent by mail')
            );
        }
        $reportMailPrint .= ' (<a href="' . $reportMailLink . '">';
        $reportMailPrint .= $this->_dataHelper->decodeLogMessage($this->_dataHelper->setLogMessage('Change this?'));
        $reportMailPrint .= '</a>)';
        return $reportMailPrint;
    }

    /**
     * Get Marketplace singleton
     *
     * @param string $name marketplace name
     *
     * @throws LengowException
     *
     * @return LengowMarketplace
     */
    public function getMarketplaceSingleton($name)
    {
        if (!array_key_exists($name, self::$marketplaces)) {
            $marketplace = $this->_marketplaceFactory->create();
            $marketplace->init(['name' => $name]);
            self::$marketplaces[$name] = $marketplace;
        }
        return self::$marketplaces[$name];
    }

    /**
     * Check if order status is valid for import
     *
     * @param string $orderStateMarketplace order state
     * @param LengowMarketplace $marketplace order marketplace
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
        // recovery of all errors not yet sent by email
        $errors = $this->_orderErrorFactory->create()->getOrderErrorsNotSent();
        if ($errors) {
            // construction of the report e-mail
            $subject = $this->_dataHelper->decodeLogMessage('Lengow imports errors');
            $support = $this->_dataHelper->decodeLogMessage(
                'no error message, contact support via https://supportlengow.zendesk.com/agent/'
            );
            $mailBody = '<h2>' . $subject . '</h2><p><ul>';
            foreach ($errors as $error) {
                $order = $this->_dataHelper->decodeLogMessage('Order %1', true, [$error['marketplace_sku']]);
                $message = $error['message'] !== ''
                    ? $this->_dataHelper->decodeLogMessage($error['message'])
                    : $support;
                $mailBody .= '<li>' . $order . ' - ' . $message . '</li>';
                $orderError = $this->_orderErrorFactory->create()->load((int)$error['id']);
                $orderError->updateOrderError(['mail' => 1]);
                unset($orderError, $order, $message);
            }
            $mailBody .= '</ul></p>';
            // send an email foreach email address
            $emails = $this->_configHelper->getReportEmailAddress();
            foreach ($emails as $email) {
                if (strlen($email) > 0) {
                    try {
                        $mail = new \Zend_Mail();
                        $mail->setSubject($subject);
                        $mail->setBodyHtml($mailBody);
                        $mail->setFrom(
                            $this->scopeConfig->getValue(
                                'trans_email/ident_general/email',
                                ScopeInterface::SCOPE_STORE
                            ),
                            'Lengow'
                        );
                        $mail->addTo($email);
                        $mail->send();
                        $this->_dataHelper->log(
                            DataHelper::CODE_MAIL_REPORT,
                            $this->_dataHelper->setLogMessage('report email sent to %1', [$email]),
                            $logOutput
                        );
                    } catch (\Exception $e) {
                        $this->_dataHelper->log(
                            DataHelper::CODE_MAIL_REPORT,
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
