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

namespace Lengow\Connector\Model\Import;

use Exception;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\OrdererrorFactory as LengowOrderErrorFactory;
use Lengow\Connector\Model\ResourceModel\Ordererror as LengowOrderErrorResource;
use Lengow\Connector\Model\ResourceModel\Ordererror\CollectionFactory as LengowOrderErrorCollectionFactory;
use Magento\Framework\App\ResourceConnection;

/**
 * Model import order error
 */
class Ordererror extends AbstractModel
{
    /**
     * @var string Lengow order error table name
     */
    public const TABLE_ORDER_ERROR = 'lengow_order_error';

    /* Order error fields */
    public const FIELD_ID = 'id';
    public const FIELD_ORDER_LENGOW_ID = 'order_lengow_id';
    public const FIELD_MESSAGE = 'message';
    public const FIELD_TYPE = 'type';
    public const FIELD_IS_FINISHED = 'is_finished';
    public const FIELD_MAIL = 'mail';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';

    /* Order error types */
    public const TYPE_ERROR_IMPORT = 1;
    public const TYPE_ERROR_SEND = 2;

    /**
     * @var DateTime Magento datetime instance
     */
    private $dateTime;

    /**
     * @var LengowOrderErrorCollectionFactory Lengow order error collection factory
     */
    private $lengowOrderErrorCollection;

    /**
     * @var LengowOrderErrorFactory Lengow order error factory
     */
    private $lengowOrderErrorFactory;

    /**
     *
     * @var ResourceConnection $resourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var array field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    private $fieldList = [
        self::FIELD_ORDER_LENGOW_ID => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_MESSAGE => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_TYPE => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_IS_FINISHED => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
        self::FIELD_MAIL => [
            DataHelper::FIELD_REQUIRED => false,
            DataHelper::FIELD_CAN_BE_UPDATED => true,
        ],
    ];

    /**
     * Constructor
     *
     * @param Context $context Magento context instance
     * @param Registry $registry Magento registry instance
     * @param DateTime $dateTime Magento datetime instance
     * @param LengowOrderErrorCollectionFactory $orderErrorCollection
     * @param LengowOrderErrorFactory $orderErrorFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DateTime $dateTime,
        LengowOrderErrorCollectionFactory $orderErrorCollection,
        LengowOrderErrorFactory $orderErrorFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->dateTime = $dateTime;
        $this->lengowOrderErrorCollection = $orderErrorCollection;
        $this->lengowOrderErrorFactory = $orderErrorFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $registry);
    }

    /**
     * Initialize order error model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(LengowOrderErrorResource::class);
    }

    /**
     * Create Lengow order error
     *
     * @param array $params order error parameters
     *
     * @return Ordererror|false
     */
    public function createOrderError(array $params = [])
    {
        foreach ($this->fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value[DataHelper::FIELD_REQUIRED]) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        $this->setData(self::FIELD_CREATED_AT, $this->dateTime->gmtDate(DataHelper::DATE_FULL));
        try {
            return $this->save();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Update Lengow order error
     *
     * @param array $params order error parameters
     *
     * @return Ordererror|false
     */
    public function updateOrderError(array $params = [])
    {
        if (!$this->getId()) {
            return false;
        }
        $updatedFields = $this->getUpdatedFields();
        foreach ($params as $key => $value) {
            if (in_array($key, $updatedFields, true)) {
                $this->setData($key, $value);
            }
        }
        $this->setData(self::FIELD_UPDATED_AT, $this->dateTime->gmtDate(DataHelper::DATE_FULL));
        try {
            return $this->save();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get updated fields
     *
     * @return array
     */
    public function getUpdatedFields(): array
    {
        $updatedFields = [];
        foreach ($this->fieldList as $key => $value) {
            if ($value[DataHelper::FIELD_CAN_BE_UPDATED]) {
                $updatedFields[] = $key;
            }
        }
        return $updatedFields;
    }

    /**
     * Removes all order error for one order lengow
     *
     * @param integer $orderLengowId Lengow order id
     * @param int $type order error type (import or send)
     *
     * @return boolean
     */
    public function finishOrderErrors(int $orderLengowId, int $type = self::TYPE_ERROR_IMPORT): bool
    {
        // get all order errors
        $results = $this->lengowOrderErrorCollection->create()->load()
            ->addFieldToFilter(self::FIELD_ORDER_LENGOW_ID, $orderLengowId)
            ->addFieldToFilter(self::FIELD_IS_FINISHED, 0)
            ->addFieldToFilter(self::FIELD_TYPE, $type)
            ->addFieldToSelect(self::FIELD_ID)
            ->getData();
        if (!empty($results)) {
            foreach ($results as $result) {
                $orderError = $this->lengowOrderErrorFactory->create()->load((int) $result[self::FIELD_ID]);
                $orderError->updateOrderError([self::FIELD_IS_FINISHED => 1]);
                unset($orderError);
            }
            return true;
        }
        return false;
    }

    /**
     * Get all order errors
     *
     * @param integer $orderLengowId Lengow order id
     * @param int|null $type order error type (import or send)
     * @param boolean|null $finished log finished
     *
     * @return array|false
     */
    public function getOrderErrors(int $orderLengowId, int $type = null, bool $finished = null)
    {
        $collection = $this->lengowOrderErrorCollection->create()->load()
            ->addFieldToFilter(self::FIELD_ORDER_LENGOW_ID, $orderLengowId);
        if ($type !== null) {
            $collection->addFieldToFilter(self::FIELD_TYPE, $type);
        }
        if ($finished !== null) {
            $errorFinished = $finished ? 1 : 0;
            $collection->addFieldToFilter(self::FIELD_IS_FINISHED, $errorFinished);
        }
        $results = $collection->getData();
        if (!empty($results)) {
            return $results;
        }
        return false;
    }

    /**
     * Get order errors never sent by mail
     *
     * @return array|false
     */
    public function getOrderErrorsNotSent()
    {
        $results = $this->lengowOrderErrorCollection->create()->load()
            ->join(
                LengowOrder::TABLE_ORDER,
                '`lengow_order`.id=main_table.order_lengow_id',
                [LengowOrder::FIELD_MARKETPLACE_SKU => LengowOrder::FIELD_MARKETPLACE_SKU]
            )
            ->addFieldToFilter(self::FIELD_MAIL, ['eq' => 0])
            ->addFieldToFilter(self::FIELD_IS_FINISHED, ['eq' => 0])
            ->addFieldToSelect(self::FIELD_MESSAGE)
            ->addFieldToSelect(self::FIELD_ID)
            ->getData();
        if (empty($results)) {
            return false;
        }
        return $results;
    }

    /**
     * Get Order to resend
     *
     * @param int $storeId
     *
     * @return array
     */
    public function getOrdersToResend(int $storeId)
    {
        $dateFrom = new \DateTime();
        $dateFrom->sub(new \DateInterval(('P7D')));
        $tableLengowOrder =  $this->resourceConnection->getConnection()
            ->getTableName(LengowOrder::TABLE_ORDER);
        $tableSalesOrder  =  $this->resourceConnection->getConnection()
            ->getTableName('sales_order');
        $collection = $this->lengowOrderErrorCollection->create()
            ->setCurPage(1)
            ->setPageSize(150)
            ->load()
            ->join(
                $tableLengowOrder,
                '`'.LengowOrder::TABLE_ORDER.'`.id=main_table.'.self::FIELD_ORDER_LENGOW_ID,
                [
                    LengowOrder::FIELD_MARKETPLACE_SKU =>  LengowOrder::FIELD_MARKETPLACE_SKU,
                    LengowOrder::FIELD_ORDER_ID        =>  LengowOrder::FIELD_ORDER_ID,
                    LengowOrder::FIELD_IS_IN_ERROR     =>  LengowOrder::FIELD_IS_IN_ERROR
                ]
            )
            ->join(
                $tableSalesOrder,
                '`'.LengowOrder::TABLE_ORDER.'`.order_id='.$tableSalesOrder.'.entity_id',
                []
            )
            ->addFieldToFilter(LengowOrder::TABLE_ORDER.'.store_id', ['eq' => $storeId])
            ->addFieldToFilter(LengowOrder::TABLE_ORDER.'.'.LengowOrder::FIELD_IS_IN_ERROR, 1)
            ->addFieldToFilter(self::FIELD_IS_FINISHED, ['eq' => 0])
            ->addFieldToFilter(self::FIELD_TYPE, ['eq' => self::TYPE_ERROR_SEND])
            ->addFieldToFilter('main_table.'.self::FIELD_CREATED_AT, ['gteq' => $dateFrom->format('Y-m-d H:i:s')])
            ->addFieldToFilter($tableSalesOrder.'.'.self::FIELD_UPDATED_AT, ['gteq' => $dateFrom->format('Y-m-d H:i:s')])
            ->setOrder(self::FIELD_ID, 'DESC');
        $results = $collection->getData();
        
        if (empty($results)) {
            return [];
        }

        return $results;
    }

    /**
     * Returns the number of send errors
     *
     * @param int  $lengowOrderId
     * @return int
     */
    public function getCountOrderSendErrors(int $lengowOrderId): int
    {

        $collection = $this->lengowOrderErrorCollection->create()
            ->addFieldToFilter(self::FIELD_TYPE, ['eq' => self::TYPE_ERROR_SEND])
            ->addFieldToFilter(self::FIELD_ORDER_LENGOW_ID, ['eq' => $lengowOrderId])
            ->load();

        return (int) $collection->getSize();
    }
}
