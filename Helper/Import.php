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

use Exception;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Sync as SyncHelper;
use Lengow\Connector\Model\Import as LengowImport;
use Lengow\Connector\Model\Import\Marketplace as LengowMarketplace;
use Lengow\Connector\Model\Import\MarketplaceFactory as LengowMarketplaceFactory;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;
use Lengow\Connector\Model\Import\Ordererror as LengowOrderError;
use Lengow\Connector\Model\Import\OrdererrorFactory as LengowOrderErrorFactory;
use Lengow\Connector\Model\Exception as LengowException;

class Import extends AbstractHelper
{
    /**
     * @var integer interval of minutes for cron synchronisation
     */
    public const MINUTE_INTERVAL_TIME = 1;

    /**
     * @var array marketplaces collection
     */
    public static $marketplaces = [];

    /**
     * @var UrlInterface Backend url interface
     */
    private $urlBackend;

    /**
     * @var DateTime Magento datetime instance
     */
    private $dateTime;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var syncHelper Lengow sync helper instance
     */
    private $syncHelper;

    /**
     * @var LengowMarketplaceFactory Lengow marketplace factory instance
     */
    private $marketplaceFactory;

    /**
     * @var LengowOrderFactory Lengow import order factory instance
     */
    private $orderFactory;

    /**
     * @var LengowOrderErrorFactory Lengow order error factory instance
     */
    private $orderErrorFactory;

    /**
     * @var array valid states lengow to create a Lengow order
     */
    private $lengowStates = [
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
     * @param SyncHelper $syncHelper Lengow sync helper instance
     * @param LengowOrderErrorFactory $orderErrorFactory Lengow order error factory instance
     * @param DateTime $dateTime Magento datetime instance
     * @param LengowMarketplaceFactory $marketplaceFactory Lengow marketplace factory instance
     * @param LengowOrderFactory $lengowOrder Lengow import order factory instance
     */
    public function __construct(
        UrlInterface $urlBackend,
        Context $context,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        SyncHelper $syncHelper,
        LengowOrderErrorFactory $orderErrorFactory,
        DateTime $dateTime,
        LengowMarketplaceFactory $marketplaceFactory,
        LengowOrderFactory $lengowOrder
    ) {
        $this->urlBackend = $urlBackend;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->syncHelper = $syncHelper;
        $this->dateTime = $dateTime;
        $this->orderErrorFactory = $orderErrorFactory;
        $this->marketplaceFactory = $marketplaceFactory;
        $this->orderFactory = $lengowOrder;
        parent::__construct($context);
    }

    /**
     * Check if import is already in process
     *
     * @return boolean
     */
    public function isInProcess(): bool
    {
        $timestamp = $this->configHelper->get(ConfigHelper::SYNCHRONIZATION_IN_PROGRESS);
        if ($timestamp > 0) {
            // security check : if last import is more than 60 seconds old => authorize new import to be launched
            if (($timestamp + (60 * self::MINUTE_INTERVAL_TIME)) < time()) {
                $this->setImportEnd();
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Get Rest time to make re-import order
     *
     * @return int
     */
    public function restTimeToImport(): int
    {
        $timestamp = $this->configHelper->get(ConfigHelper::SYNCHRONIZATION_IN_PROGRESS);
        if ($timestamp > 0) {
            return $timestamp + (60 * self::MINUTE_INTERVAL_TIME) - time();
        }
        return 0;
    }

    /**
     * Set import to "in process" state
     */
    public function setImportInProcess(): void
    {
        $this->configHelper->set(ConfigHelper::SYNCHRONIZATION_IN_PROGRESS, time());
    }

    /**
     * Set import to finished
     */
    public function setImportEnd(): void
    {
        $this->configHelper->set(ConfigHelper::SYNCHRONIZATION_IN_PROGRESS, -1);
    }

    /**
     * Record the date of the last import
     *
     * @param string $type last import type (cron or manual)
     *
     * @return boolean
     */
    public function updateDateImport(string $type): bool
    {
        if ($type === LengowImport::TYPE_CRON || $type === LengowImport::TYPE_MAGENTO_CRON) {
            $this->configHelper->set(
                ConfigHelper::LAST_UPDATE_CRON_SYNCHRONIZATION,
                $this->dateTime->gmtTimestamp()
            );
        } else {
            $this->configHelper->set(
                ConfigHelper::LAST_UPDATE_MANUAL_SYNCHRONIZATION,
                $this->dateTime->gmtTimestamp()
            );
        }
        return true;
    }

    /**
     * Get last import (type and timestamp)
     *
     * @return array
     */
    public function getLastImport(): array
    {
        $timestampCron = $this->configHelper->get(ConfigHelper::LAST_UPDATE_CRON_SYNCHRONIZATION);
        $timestampManual = $this->configHelper->get(ConfigHelper::LAST_UPDATE_MANUAL_SYNCHRONIZATION);
        if ($timestampCron && $timestampManual) {
            if ((int) $timestampCron > (int) $timestampManual) {
                return ['type' => LengowImport::TYPE_CRON, 'timestamp' => (int) $timestampCron];
            }
            return ['type' => LengowImport::TYPE_MANUAL, 'timestamp' => (int) $timestampManual];

        }
        if ($timestampCron && !$timestampManual) {
            return ['type' => LengowImport::TYPE_CRON, 'timestamp' => (int) $timestampCron];
        }
        if ($timestampManual && !$timestampCron) {
            return ['type' => LengowImport::TYPE_MANUAL, 'timestamp' => (int) $timestampManual];
        }
        return ['type' => 'none', 'timestamp' => 'none'];
    }

    /**
     * Get last import to print
     *
     * @return string
     */
    public function getLastImportDatePrint(): string
    {
        $lastImport = $this->getLastImport();
        if ($lastImport['type'] !== 'none') {
            $lastImportDate = $this->dataHelper->getDateInCorrectFormat($lastImport['timestamp']);
            return $this->dataHelper->decodeLogMessage(
                $this->dataHelper->setLogMessage('Last synchronisation : %1', ['<b>' . $lastImportDate . '</b>'])
            );
        }
        return $this->dataHelper->decodeLogMessage($this->dataHelper->setLogMessage('No synchronisation for now'));
    }

    /**
     * Get number orders in error to print
     *
     * @return string
     */
    public function getOrderWithErrorPrint(): string
    {
        return $this->dataHelper->decodeLogMessage(
            $this->dataHelper->setLogMessage(
                'You have %1 order(s) with errors',
                [$this->orderFactory->create()->countOrderWithError()]
            )
        );
    }

    /**
     * Get number orders sending to print
     *
     * @return string
     */
    public function getOrderToBeSentPrint(): string
    {
        return $this->dataHelper->decodeLogMessage(
            $this->dataHelper->setLogMessage(
                'You have %1 order(s) waiting to be sent',
                [$this->orderFactory->create()->countOrderToBeSent()]
            )
        );
    }

    /**
     * Get report email to print
     *
     * @return string
     */
    public function getReportMailPrint(): string
    {
        $reportMailPrint = '';
        $reportMailActive = (bool) $this->configHelper->get(ConfigHelper::REPORT_MAIL_ENABLED);
        $reportMailLink = $this->urlBackend->getUrl('adminhtml/system_config/edit/section/lengow_import_options/');
        $reportMails = $this->configHelper->getReportEmailAddress();
        if ($reportMailActive) {
            $reportMailPrint .= $this->dataHelper->decodeLogMessage(
                $this->dataHelper->setLogMessage('All order issue reports will be sent by mail to')
            );
            $reportMailPrint .= ' ' . implode(', ', $reportMails);
        } else {
            $reportMailPrint .= $this->dataHelper->decodeLogMessage(
                $this->dataHelper->setLogMessage('No order issue reports will be sent by mail')
            );
        }
        $reportMailPrint .= ' (<a href="' . $reportMailLink . '">';
        $reportMailPrint .= $this->dataHelper->decodeLogMessage($this->dataHelper->setLogMessage('Change this?'));
        $reportMailPrint .= '</a>)';
        return $reportMailPrint;
    }

    /**
     * Get Marketplace singleton
     *
     * @param string $name marketplace name
     *
     * @return LengowMarketplace
     *
     * @throws LengowException
     */
    public function getMarketplaceSingleton(string $name): LengowMarketplace
    {
        if (!array_key_exists($name, self::$marketplaces)) {
            $marketplace = $this->marketplaceFactory->create();
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
    public function checkState(string $orderStateMarketplace, LengowMarketplace $marketplace): bool
    {
        if (empty($orderStateMarketplace)) {
            return false;
        }
        if (!in_array($marketplace->getStateLengow($orderStateMarketplace), $this->lengowStates, true)) {
            return false;
        }
        return true;
    }

    /**
     * Check logs table and send mail for order not imported correctly
     *
     * @param boolean $logOutput see log or not
     */
    public function sendMailAlert(bool $logOutput = false): void
    {
        // recovery of all errors not yet sent by email
        $errors = $this->orderErrorFactory->create()->getOrderErrorsNotSent();
        if ($errors) {
            // construction of the report e-mail
            $subject = $this->dataHelper->decodeLogMessage('Lengow imports errors');
            $pluginLinks = $this->syncHelper->getPluginLinks();
            $support = $this->dataHelper->decodeLogMessage(
                'no error message, contact support via %1',
                true,
                [$pluginLinks[SyncHelper::LINK_TYPE_SUPPORT]]
            );
            $mailBody = '<h2>' . $subject . '</h2><p><ul>';
            foreach ($errors as $error) {
                $order = $this->dataHelper->decodeLogMessage(
                    'Order %1',
                    true,
                    [$error[LengowOrder::FIELD_MARKETPLACE_SKU]]
                );
                $message = $error[LengowOrderError::FIELD_MESSAGE] !== ''
                    ? $this->dataHelper->decodeLogMessage($error[LengowOrderError::FIELD_MESSAGE])
                    : $support;
                $mailBody .= '<li>' . $order . ' - ' . $message . '</li>';
                $orderError = $this->orderErrorFactory->create()->load((int) $error[LengowOrderError::FIELD_ID]);
                $orderError->updateOrderError([LengowOrderError::FIELD_MAIL => 1]);
                unset($orderError, $order, $message);
            }
            $mailBody .= '</ul></p>';
            // send an email foreach email address
            $emails = $this->configHelper->getReportEmailAddress();
            foreach ($emails as $email) {
                if ($email !== '') {
                    try {
                        if (class_exists('\Zend_Mail')) {
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
                            $this->dataHelper->log(
                                DataHelper::CODE_MAIL_REPORT,
                                $this->dataHelper->setLogMessage('report email sent to %1', [$email]),
                                $logOutput
                            );
                        } else {
                            $mail = new \Laminas\Mail\Message();
                            $mail->setSubject($subject);
                            $htmlPart = new \Laminas\Mime\Part($mailBody);
                            $htmlPart->type = "text/html";
                            $body = new \Laminas\Mime\Message();
                            $body->setParts([$htmlPart]);
                            $mail->setBody($body);
                            $mail->setFrom(
                                $this->scopeConfig->getValue(
                                    'trans_email/ident_general/email',
                                    ScopeInterface::SCOPE_STORE
                                ),
                                'Lengow'
                            );
                            $mail->addTo($email);
                            $transport = new \Laminas\Mail\Transport\Sendmail();
                            $transport->send($mail);
                            $this->dataHelper->log(
                                DataHelper::CODE_MAIL_REPORT,
                                $this->dataHelper->setLogMessage('report email sent to %1', [$email]),
                                $logOutput
                            );
                        }

                    } catch (Exception $e) {
                        $this->dataHelper->log(
                            DataHelper::CODE_MAIL_REPORT,
                            $this->dataHelper->setLogMessage('unable to send report email to %1', [$email]),
                            $logOutput
                        );
                    }
                    unset($mail, $email);
                }
            }
        }
    }
}
