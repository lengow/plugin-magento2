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

namespace Lengow\Connector\Model;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\ResourceModel\Log as LengowLogResource;
use Lengow\Connector\Model\ResourceModel\Log\CollectionFactory as LengowLogCollectionFactory;

class Log extends AbstractModel
{
    /**
     * @var string Lengow log table name
     */
    public const TABLE_LOG = 'lengow_log';

    /* Action fields */
    public const FIELD_ID = 'id';
    public const FIELD_DATE = 'date';
    public const FIELD_CATEGORY = 'category';
    public const FIELD_MESSAGE = 'message';

    /* Log params for export */
    public const LOG_DATE = 'date';
    public const LOG_LINK = 'link';

    /**
     * @var integer life of log files in days
     */
    public const LOG_LIFE = 20;

    /**
     * @var array field list for the table lengow_log
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    private $fieldList = [
        self::FIELD_MESSAGE => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_CATEGORY => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
    ];

    /**
     * @var DateTime Magento datetime instance
     */
    private $dateTime;

    /**
     * @var ResourceConnection Magento resource connection instance
     */
    private $resourceConnection;

    /**
     * @var LengowLogCollectionFactory Lengow log collection factory
     */
    private $lengowLogCollection;

    /**
     * Constructor
     *
     * @param Context $context Magento context instance
     * @param Registry $registry Magento registry instance
     * @param DateTime $dateTime Magento datetime instance
     * @param ResourceConnection $resourceConnection Magento resource connection instance
     * @param LengowLogCollectionFactory $lengowLogCollection Lengow log collection factory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DateTime $dateTime,
        ResourceConnection $resourceConnection,
        LengowLogCollectionFactory $lengowLogCollection
    ) {
        $this->dateTime = $dateTime;
        $this->resourceConnection = $resourceConnection;
        $this->lengowLogCollection = $lengowLogCollection;
        parent::__construct($context, $registry);
    }

    /**
     * Initialize logs model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(LengowLogResource::class);
    }

    /**
     * Create Lengow log
     *
     * @param array $params
     *
     * @return Log|boolean
     */
    public function createLog(array $params = [])
    {
        foreach ($this->fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value[DataHelper::FIELD_REQUIRED]) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        $this->setData(self::FIELD_DATE, $this->dateTime->gmtDate(DataHelper::DATE_FULL));
        try {
            return $this->save();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Find logs by date
     *
     * @param string $date log date
     *
     * @return array
     */
    public function getLogsByDate(string $date): array
    {
        $collection = $this->lengowLogCollection->create()
            ->addFieldToFilter(
                self::FIELD_DATE,
                [
                    'from' => date('Y-m-d 00:00:00', strtotime($date)),
                    'to' => date('Y-m-d 23:59:59', strtotime($date)),
                    'datetime' => true,
                ]
            );
        return $collection->getData();
    }

    /**
     * Check if log date is available
     *
     * @param string $date log date
     *
     * @return boolean
     */
    public function logDateIsAvailable(string $date): bool
    {
        $table = $this->resourceConnection->getTableName(self::TABLE_LOG);
        $query = 'SELECT COUNT(*) FROM ' . $table . '
            WHERE `date` >= "' . date('Y-m-d 00:00:00', strtotime($date)) . '"
            AND `date` <= "' . date('Y-m-d 23:59:59', strtotime($date)) . '"';
        $result = (int) $this->resourceConnection->getConnection()->fetchOne($query);
        return $result > 0;
    }

    /**
     * Get all available log dates
     *
     * @return array
     */
    public function getAvailableLogDates(): array
    {
        $logDates = [];
        for ($i = 0; $i <= self::LOG_LIFE; $i++) {
            $date = new \DateTime();
            $logDate = $date->modify('-' . $i . ' day')->format(DataHelper::DATE_DAY);
            if ($this->logDateIsAvailable($logDate)) {
                $logDates[] = $logDate;
            }
        }
        return $logDates;
    }

    /**
     * Download log file individually or globally
     *
     * @param string|null $date date for a specific log file
     */
    public function download(string $date = null): void
    {
        $contents = '';
        if ($date && preg_match('/^(\d{4}-\d{2}-\d{2})$/', $date)) {
            $fileName = $date . '.txt';
            $logs = $this->getLogsByDate($date);
            foreach ($logs as $log) {
                $contents .= $log[self::FIELD_DATE] . ' - ' . $log[self::FIELD_MESSAGE] . "\r\n";
            }
        } else {
            $fileName = 'logs.txt';
            $logDates = array_reverse($this->getAvailableLogDates());
            foreach ($logDates as $logDate) {
                $logs = $this->getLogsByDate($logDate);
                foreach ($logs as $log) {
                    $contents .= $log[self::FIELD_DATE] . ' - ' . $log[self::FIELD_MESSAGE] . "\r\n";
                }
            }
        }
        header('Content-type: text/plain');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        print_r($contents);
    }
}
