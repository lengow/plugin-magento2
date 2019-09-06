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
use Lengow\Connector\Helper\Data as DataHelper;

class OrderStatus extends Column
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface Magento order repository instance
     */
    protected $_orderRepository;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * Constructor
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository Magento order repository instance
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context Magento ui context instance
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory Magento ui factory instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     * @param array $components component data
     * @param array $data additional params
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        DataHelper $dataHelper,
        array $components = [],
        array $data = []
    ) {
        $this->_orderRepository = $orderRepository;
        $this->_dataHelper = $dataHelper;
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
                if ((bool)$item['sent_marketplace']) {
                    $item['order_status'] = '<span class="lgw-label">'
                        . $this->_dataHelper->decodeLogMessage('Shipped by Marketplace') . '</span>';
                } elseif (!is_null($item['order_id'])) {
                    $item['order_status'] = $this->_orderRepository->get($item['order_id'])->getStatus();
                }
            }
        }
        return $dataSource;
    }
}
