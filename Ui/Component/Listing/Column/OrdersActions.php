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

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\UrlInterface;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Import\OrdererrorFactory;
use Lengow\Connector\Model\Import\Action as ImportAction;

class OrdersActions extends Column
{

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface Magento order repository instance
     */
    protected $_orderRepository;

    /**
     * @var \Magento\Framework\UrlInterface Magento url interface
     */
    protected $urlBuilder;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var \Lengow\Connector\Model\Import\OrdererrorFactory Lengow order error factory instance
     */
    protected $_orderErrorFactory;

    /**
     * @var \Lengow\Connector\Model\Import\Action Lengow action instance
     */
    protected $_action;

    /**
     * Constructor
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository Magento order repository instance
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context Magento ui context instance
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory Magento ui factory instance
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param array $components component data
     * @param array $data additional params
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param \Lengow\Connector\Model\Import\OrdererrorFactory $orderErrorFactory Lengow order factory instance
     * @param \Lengow\Connector\Model\Import\Action $action Lengow action instance
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        DataHelper $dataHelper,
        OrdererrorFactory $orderErrorFactory,
        ImportAction $action,
        array $components = [],
        array $data = []
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->_orderRepository = $orderRepository;
        $this->_dataHelper = $dataHelper;
        $this->_orderErrorFactory = $orderErrorFactory;
        $this->_action = $action;
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
                if ($item['is_in_error'] == 1 && $item['order_process_state'] != 2) {
                    $orderLengowId = $item['id'];
                    $errorType = $item['order_process_state'] == 0 ? 'import' : 'send';
                    $url = $this->urlBuilder->getUrl('lengow/order/index') . '?isAjax=true';
                    $errorOrders = $this->_orderErrorFactory->create()->getOrderErrors($orderLengowId, $errorType, false);
                    $errorMessages = [];
                    if ($errorOrders) {
                        foreach ($errorOrders as $errorOrder) {
                            if ($errorOrder['message'] != '') {
                                $errorMessages[] = $this->_dataHelper->cleanData(
                                    $this->_dataHelper->decodeLogMessage($errorOrder['message'])
                                );
                            } else {
                                $errorMessages[] = $this->_dataHelper->decodeLogMessage(
                                    "Unidentified error, please contact Lengow's support team for more information"
                                );
                            }
                        }
                    }
                    if ($errorType == 'import') {
                        $tootlip = $this->_dataHelper->decodeLogMessage("Order hasn't been imported into Magento")
                            . '<br/>' . join('<br/>', $errorMessages);
                        $item['is_in_error'] = '<a class="lengow_action lengow_tooltip lgw-btn lgw-btn-white lgw_order_action_grid-js"
                        data-href="' . $url . '" data-lgwAction="re_import" data-lgwOrderId="' . $orderLengowId . '">'
                            . $this->_dataHelper->decodeLogMessage('not imported')
                            . '<span class="lengow_order_action">' . $tootlip . '</span>&nbsp<i class="fa fa-refresh"></i></a>';
                    } else {
                        $tootlip = $this->_dataHelper->decodeLogMessage("Action sent to the marketplace didn't work")
                            . '<br/>' . join('<br/>', $errorMessages);
                        $item['is_in_error'] = '<a class="lengow_action lengow_tooltip lgw-btn lgw-btn-white lgw_order_action_grid-js"
                        data-href="' . $url . '" data-lgwAction="re_send" data-lgwOrderId="' . $orderLengowId . '">'
                            . $this->_dataHelper->decodeLogMessage('not sent')
                            . '<span class="lengow_order_action">' . $tootlip . '</span>&nbsp<i class="fa fa-refresh"></i></a>';
                    }
                } else {
                    //check if order actions in progress
                    if (!is_null($item['order_id']) && $item['order_process_state'] == 1) {
                        $lastActionType = $this->_action->getLastOrderActionType($item['order_id']);
                        if ($lastActionType) {
                            $item['is_in_error'] = '<a class="lengow_action lengow_tooltip lgw-btn lgw-btn-white">'
                                . $this->_dataHelper->decodeLogMessage(
                                    'action %1 sent',
                                    null,
                                    [$lastActionType]
                                )
                                . '<span class="lengow_order_action">'
                                . $this->_dataHelper->decodeLogMessage('Action sent, waiting for response')
                                . '</span></a>';
                        } else {
                            $item['is_in_error'] = '';
                        }
                    } else {
                        $item['is_in_error'] = '';
                    }
                }
            }
        }

        return $dataSource;
    }
}
