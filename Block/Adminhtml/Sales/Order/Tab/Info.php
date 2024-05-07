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
 * @subpackage  Block
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Block\Adminhtml\Sales\Order\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Config as ConfigHelper;
use Lengow\Connector\Model\Import\Action as LengowAction;
use Lengow\Connector\Model\Import\Order as LengowOrder;
use Lengow\Connector\Model\Import\OrderFactory as LengowOrderFactory;

class Info extends Template implements TabInterface
{
    /**
     * @var string Lengow template path
     */
    protected $_template = 'sales/order/tab/info.phtml';

    /**
     * @var Registry Magento Registry instance
     */
    private $coreRegistry;

    /**
     * @var MagentoOrder Magento order instance
     */
    private $order;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var ConfigHelper Lengow config helper instance
     */
    private $configHelper;

    /**
     * @var LengowAction Lengow action instance
     */
    private $lengowAction;

    /**
     * @var ?LengowOrder Lengow order instance
     */
    private $lengowOrder;

    /**
     * @var LengowOrderFactory Lengow order factory instance
     */
    private $lengowOrderFactory;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezone;

    /**
     * Construct
     *
     * @param Context $context Magento Context instance
     * @param Registry $coreRegistry Magento Registry instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param ConfigHelper $configHelper Lengow config helper instance
     * @param LengowOrderFactory $lengowOrderFactory Lengow order factory instance
     * @param LengowAction $lengowAction Lengow action instance
     * @param TimezoneInterface $timezone
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        LengowOrderFactory $lengowOrderFactory,
        LengowAction $lengowAction,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->lengowOrderFactory = $lengowOrderFactory;
        $this->lengowAction = $lengowAction;
        $this->order = $this->getOrder();
        $this->lengowOrder = $this->getLengowOrder();
        $this->timezone = $timezone;
        parent::__construct($context, $data);
    }

    /**
     * Get tab label
     *
     * @return string
     */
    public function getTabLabel(): string
    {
        return __('Lengow');
    }

    /**
     * Get tab title
     *
     * @return string
     */
    public function getTabTitle(): string
    {
        return __('Lengow');
    }

    /**
     * Can show tab
     *
     * @return boolean
     */
    public function canShowTab(): bool
    {
        return true;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden(): bool
    {
        return false;
    }

    /**
     * Retrieve order model instance
     *
     * @return MagentoOrder
     */
    public function getOrder(): MagentoOrder
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * Get Lengow order by Magento order id
     *
     * @return LengowOrder|null
     */
    public function getLengowOrder(): ?LengowOrder
    {
        $lengowOrderId = $this->lengowOrderFactory->create()->getLengowOrderIdByOrderId($this->getOrderId());
        if ($lengowOrderId) {
            return $this->lengowOrderFactory->create()->load($lengowOrderId);
        }
        return null;
    }

    /**
     * Get Magento order id
     *
     * @return integer
     */
    public function getOrderId(): int
    {
        return (int) $this->order->getId();
    }

    /**
     * Get Magento order status
     *
     * @return string
     */
    public function getOrderStatus(): string
    {
        return $this->order->getStatus();
    }

    /**
     * Get import date of the order in Magento
     *
     * @return string
     */
    public function getOrderImportedDate(): string
    {
        return $this->order->getStatusHistoryCollection()->getFirstItem()->getCreatedAt();
    }

    /**
     * Get Lengow order id if exist
     *
     * @return integer|null
     */
    public function getLengowOrderId(): ?int
    {
        return $this->lengowOrder ? (int) $this->lengowOrder->getId() : null;
    }

    /**
     * Debug Mode is enabled
     *
     * @return boolean
     */
    public function debugModeIsEnabled(): bool
    {
        return $this->configHelper->debugModeIsActive();
    }

    /**
     * Check if is an order imported by Lengow
     *
     * @return boolean
     */
    public function isOrderImportedByLengow(): bool
    {
        return (bool) $this->order->getData('from_lengow');
    }

    /**
     * Check if is an order with a Lengow order record
     *
     * @return boolean
     */
    public function isOrderFollowedByLengow(): bool
    {
        return (bool) $this->lengowOrder;
    }

    /**
     * Check if you can resend action order
     *
     * @return boolean
     */
    public function canReSendAction(): bool
    {
        if (!$this->lengowAction->getActionsByOrderId($this->getOrderId(), true)) {
            $orderStatus = $this->getOrderStatus();
            if (($orderStatus === MagentoOrder::STATE_COMPLETE || $orderStatus === MagentoOrder::STATE_CANCELED)
                && $this->lengowOrder
            ) {
                $finishProcessState = $this->lengowOrder->getOrderProcessState(LengowOrder::STATE_CLOSED);
                $lengowOrderProcessState = (int) $this->lengowOrder->getData(LengowOrder::FIELD_ORDER_PROCESS_STATE);
                if ($lengowOrderProcessState !== $finishProcessState) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get all Lengow order data
     *
     * @return array
     */
    public function getFields(): array
    {
        $fields = [];
        if ($this->lengowOrder) {
            $fields[] = [
                'label' => __('Marketplace SKU'),
                'value' => $this->lengowOrder->getData(LengowOrder::FIELD_MARKETPLACE_SKU),
            ];
            $fields[] = [
                'label' => __('Marketplace'),
                'value' => $this->lengowOrder->getData(LengowOrder::FIELD_MARKETPLACE_LABEL),
            ];
            $fields[] = [
                'label' => __('Delivery Address ID'),
                'value' => $this->lengowOrder->getData(LengowOrder::FIELD_DELIVERY_ADDRESS_ID),
            ];
            $fields[] = [
                'label' => __('Currency'),
                'value' => $this->lengowOrder->getData(LengowOrder::FIELD_CURRENCY),
            ];
            $fields[] = [
                'label' => __('Total Paid'),
                'value' => $this->lengowOrder->getData(LengowOrder::FIELD_TOTAL_PAID),
            ];
            $fields[] = [
                'label' => __('Commission'),
                'value' => $this->lengowOrder->getData(LengowOrder::FIELD_COMMISSION),
            ];
            $fields[] = [
                'label' => __('Customer name'),
                'value' => $this->lengowOrder->getData(LengowOrder::FIELD_CUSTOMER_NAME),
            ];
            $fields[] = [
                'label' => __('Customer email'),
                'value' => $this->lengowOrder->getData(LengowOrder::FIELD_CUSTOMER_EMAIL),
            ];

            try {
                $jsonArray = json_decode(
                    $this->lengowOrder->getData(LengowOrder::FIELD_EXTRA),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            } catch (\JsonException $e) {
                $jsonArray = [];
            }

            $shippingPhone = $this->getShippingPhone($jsonArray);
            if (!empty($shippingPhone)) {
                $fields[] = [
                    'label' => __('Customer shipping phone'),
                    'value' => $shippingPhone,
                ];
            }

            $billingPhone = $this->getBillingPhone($jsonArray);
            if (!empty($billingPhone) && $billingPhone !== $shippingPhone) {
                $fields[] = [
                    'label' => __('Customer billing phone'),
                    'value' => $billingPhone,
                ];
            }

            $fields[] = [
                'label' => __('Carrier from marketplace'),
                'value' => $this->lengowOrder->getData(LengowOrder::FIELD_CARRIER),
            ];
            $fields[] = [
                'label' => __('Shipping method from marketplace'),
                'value' => $this->lengowOrder->getData(LengowOrder::FIELD_CARRIER_METHOD),
            ];

            $maxShippingDate = $this->getMaxShippingDate($jsonArray);
            if (!empty($maxShippingDate)) {
                $customDate = $this->timezone->formatDate(
                    new \DateTime($maxShippingDate),
                    \IntlDateFormatter::FULL
                );

                $fields[] = [
                    'label' => __('Max shipping date'),
                    'value' => $customDate,
                ];
            }

            $fields[] = [
                'label' => __('Tracking number'),
                'value' => $this->lengowOrder->getData(LengowOrder::FIELD_CARRIER_TRACKING),
            ];
            $fields[] = [
                'label' => __('ID relay'),
                'value' => $this->lengowOrder->getData(LengowOrder::FIELD_CARRIER_RELAY_ID),
            ];
            $fields[] = [
                'label' => __('Express delivery'),
                'value' => $this->lengowOrder->isExpress() ? __('Yes') : __('No'),
            ];
            $fields[] = [
                'label' => __('Shipped by Marketplace'),
                'value' => $this->lengowOrder->isDeliveredByMarketplace() ? __('Yes') : __('No'),
            ];
            $fields[] = [
                'label' => __('Business'),
                'value' => $this->lengowOrder->isBusiness() ? __('Yes') : __('No'),
            ];
            $fields[] = [
                'label' => __('Message'),
                'value' => $this->lengowOrder->getData(LengowOrder::FIELD_MESSAGE),
            ];
            $fields[] = [
                'label' => ('Vat number'),
                'value' => $this->lengowOrder->getData(LengowOrder::FIELD_CUSTOMER_VAT_NUMBER),
            ];
            $fields[] = [
                'label' => __('Imported at'),
                'value' => $this->dataHelper->getDateInCorrectFormat(strtotime($this->getOrderImportedDate())),
            ];
            $fields[] = [
                'label' => __('JSON format'),
                'value' => '<textarea disabled="disabled">'
                    . $this->lengowOrder->getData(LengowOrder::FIELD_EXTRA) . '</textarea>',
            ];
        }
        return $fields;
    }

    /**
     * @param array $jsonArray
     * @return string|null
     */
    protected function getShippingPhone(array $jsonArray): ?string
    {
        return $jsonArray['packages'][0]['delivery']['phone_mobile']
            ?? $jsonArray['packages'][0]['delivery']['phone_home']
            ?? $jsonArray['packages'][0]['delivery']['phone_office']
            ?? null;
    }

    /**
     * @param array $jsonArray
     * @return string|null
     */
    protected function getBillingPhone(array $jsonArray): ?string
    {
        return $jsonArray['billing_address']['phone_mobile']
            ?? $jsonArray['billing_address']['phone_home']
            ?? $jsonArray['billing_address']['phone_office']
            ?? null;
    }

    /**
     * @param array $jsonArray
     * @return string|null
     */
    protected function getMaxShippingDate(array $jsonArray): ?string
    {
        $items = $jsonArray['packages'][0]['cart'] ?? [];
        $maxShippingDate = null;
        foreach ($items as $item) {
            if (empty($item['max_shipping_date'])) {
                continue;
            }

            if (!isset($maxShippingDate)) {
                $maxShippingDate = $item['max_shipping_date'];
            } elseif ($maxShippingDate > $item['max_shipping_date']) {
                $maxShippingDate = $item['max_shipping_date'];
            }
        }

        return $maxShippingDate;
    }
}
