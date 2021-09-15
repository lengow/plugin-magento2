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

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\ResourceModel\Orderline\CollectionFactory as LengowOrderLineCollectionFactory;
use Lengow\Connector\Model\ResourceModel\Orderline as LengowOrderLineResource;

/**
 * Model import order line
 */
class Orderline extends AbstractModel
{
    /**
     * @var string Lengow order line table name
     */
    const TABLE_ORDER_LINE = 'lengow_order_line';

    /* Order line fields */
    const FIELD_ID = 'id';
    const FIELD_ORDER_ID = 'order_id';
    const FIELD_PRODUCT_ID = 'product_id';
    const FIELD_ORDER_LINE_ID = 'order_line_id';

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var LengowOrderLineCollectionFactory Lengow order line collection factory instance
     */
    private $lengowOrderLineCollectionFactory;

    /**
     * @var array field list for the table lengow_order_line
     * required => Required fields when creating registration
     * update   => Fields allowed when updating registration
     */
    private $fieldList = [
        self::FIELD_ORDER_ID => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_PRODUCT_ID => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
        self::FIELD_ORDER_LINE_ID => [
            DataHelper::FIELD_REQUIRED => true,
            DataHelper::FIELD_CAN_BE_UPDATED => false,
        ],
    ];

    /**
     * Constructor
     *
     * @param Context $context Magento context instance
     * @param Registry $registry Magento registry instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param LengowOrderLineCollectionFactory $lengowOrderLineCollectionFactory Lengow order line collection instance
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DataHelper $dataHelper,
        LengowOrderLineCollectionFactory $lengowOrderLineCollectionFactory
    ) {
        parent::__construct($context, $registry);
        $this->dataHelper = $dataHelper;
        $this->lengowOrderLineCollectionFactory = $lengowOrderLineCollectionFactory;
    }

    /**
     * Initialize orderline model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(LengowOrderLineResource::class);
    }

    /**
     * Create Lengow order line
     *
     * @param array $params order line parameters
     *
     * @return Orderline|false
     */
    public function createOrderLine($params = [])
    {
        foreach ($this->fieldList as $key => $value) {
            if (!array_key_exists($key, $params) && $value[DataHelper::FIELD_REQUIRED]) {
                return false;
            }
        }
        foreach ($params as $key => $value) {
            $this->setData($key, $value);
        }
        try {
            return $this->save();
        } catch (\Exception $e) {
            $errorMessage = 'Orm error: "' . $e->getMessage() . '" ' . $e->getFile() . ' line ' . $e->getLine();
            $this->dataHelper->log(
                DataHelper::CODE_ORM,
                $this->dataHelper->setLogMessage('Error while inserting record in database - %1', [$errorMessage])
            );
            return false;
        }
    }

    /**
     * Get all order line id by order id
     *
     * @param integer $orderId Magento order id
     *
     * @return array|false
     */
    public function getOrderLineByOrderID($orderId)
    {
        $results = $this->lengowOrderLineCollectionFactory->create()
            ->addFieldToFilter(self::FIELD_ORDER_ID, $orderId)
            ->addFieldToSelect(self::FIELD_ORDER_LINE_ID)
            ->getData();
        if (!empty($results)) {
            return $results;
        }
        return false;
    }
}
