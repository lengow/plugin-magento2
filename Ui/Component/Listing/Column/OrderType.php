<?php
/**
 * Copyright 2020 Lengow SAS
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
 * @subpackage  UI
 * @author      Team module <team-module@lengow.com>
 * @copyright   2020 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Lengow\Connector\Model\Import\Order as LengowOrder;

class OrderType extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource row data source
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        $dataSource = parent::prepareDataSource($dataSource);
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if ($item[LengowOrder::FIELD_ORDER_TYPES] !== null) {
                    $return = '<div>';
                    $orderTypes = (string) $item[LengowOrder::FIELD_ORDER_TYPES];
                    $orderTypes = $orderTypes !== '' ? json_decode($orderTypes, true) : [];
                    if (isset($orderTypes[LengowOrder::TYPE_EXPRESS]) || isset($orderTypes[LengowOrder::TYPE_PRIME])) {
                        $iconLabel = $orderTypes[LengowOrder::TYPE_PRIME] ?? $orderTypes[LengowOrder::TYPE_EXPRESS];
                        $return .= $this->_generateOrderTypeIcon($iconLabel, 'orange-light', 'mod-chrono');
                    }
                    if (isset($orderTypes[LengowOrder::TYPE_DELIVERED_BY_MARKETPLACE])
                        || (bool) $item[LengowOrder::FIELD_SENT_MARKETPLACE]
                    ) {
                        $iconLabel = $orderTypes[LengowOrder::TYPE_DELIVERED_BY_MARKETPLACE]
                            ?? LengowOrder::LABEL_FULFILLMENT;
                        $return .= $this->_generateOrderTypeIcon($iconLabel, 'green-light', 'mod-delivery');
                    }
                    if (isset($orderTypes[LengowOrder::TYPE_BUSINESS])) {
                        $iconLabel = $orderTypes[LengowOrder::TYPE_BUSINESS];
                        $return .= $this->_generateOrderTypeIcon($iconLabel, 'blue-light', 'mod-pro');
                    }
                    $return .= '</div>';
                    $item[LengowOrder::FIELD_ORDER_TYPES] = $return;
                }
            }
        }
        return $dataSource;
    }

    /**
     * Generate order type icon
     *
     * @param string $iconLabel icon label for tooltip
     * @param string $iconColor icon background color
     * @param string $iconMod icon mod for image
     *
     * @return string
     */
    private function _generateOrderTypeIcon(string $iconLabel, string $iconColor, string $iconMod): string
    {
        return '
            <div class="lgw-label ' . $iconColor . ' icon-solo lengow_tooltip">
                <a class="lgw-icon ' . $iconMod . '">
                    <span class="lengow_order_types">' . $iconLabel . '</span>
                </a>
            </div>
        ';
    }
}
