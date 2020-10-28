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
 * @subpackage  UI
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Lengow\Connector\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Lengow\Connector\Model\Import\Action as LengowAction;

class ActionParameters extends Column
{
    /**
     * Constructor
     *
     * @param ContextInterface $context Magento ui context instance
     * @param UiComponentFactory $uiComponentFactory Magento ui factory instance
     * @param array $components component data
     * @param array $data additional params
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource row data source
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $dataSource = parent::prepareDataSource($dataSource);
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if ($item['parameters']) {
                    $return = '';
                    $parameters = json_decode($item['parameters'], true);
                    foreach ($parameters as $key => $value) {
                        if ($key === LengowAction::ARG_LINE || $key === LengowAction::ARG_ACTION_TYPE) {
                            continue;
                        } elseif ($key === LengowAction::ARG_TRACKING_NUMBER) {
                            $key = 'tracking';
                        } elseif ($key === 'marketplace_order_id') {
                            $key = 'marketplace sku';
                        }
                        $return .= strlen($return) === 0
                            ? ucfirst($key) . ': ' . $value . ' '
                            : '- ' . ucfirst($key) . ': ' . $value . ' ';
                    }
                    $item['parameters'] = $return;
                }
            }
        }
        return $dataSource;
    }
}
