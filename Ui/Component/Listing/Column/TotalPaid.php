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

use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Import\Order as LengowOrder;

class TotalPaid extends Column
{
    /**
     * @var StoreManagerInterface Magento store manager instance
     */
    protected $_storeManager;

    /**
     * @var CurrencyFactory Magento currency factory instance
     */
    protected $_currencyFactory;

    /**
     * @var DataHelper Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * Constructor
     *
     * @param CurrencyFactory $currencyFactory Magento currency factory instance
     * @param StoreManagerInterface $storeManager Magento store manager instance
     * @param ContextInterface $context Magento ui context instance
     * @param UiComponentFactory $uiComponentFactory Magento ui factory instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param array $components component data
     * @param array $data additional params
     */
    public function __construct(
        CurrencyFactory $currencyFactory,
        StoreManagerInterface $storeManager,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        DataHelper $dataHelper,
        array $components = [],
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_currencyFactory = $currencyFactory;
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
                if ($item[LengowOrder::FIELD_TOTAL_PAID] !== null) {
                    $currencyFactory = $this->_currencyFactory->create()->load($item[LengowOrder::FIELD_CURRENCY]);
                    $currencySymbol = $currencyFactory->getCurrencySymbol();
                    $nbProduct = $this->_dataHelper->decodeLogMessage(
                        '%1 product(s)',
                        true,
                        [$item[LengowOrder::FIELD_ORDER_ITEM]]
                    );
                    $item[LengowOrder::FIELD_TOTAL_PAID] = '
                        <div class="lengow_tooltip">'
                            . $currencySymbol . $item[LengowOrder::FIELD_TOTAL_PAID] .
                            '<span class="lengow_order_amount">' . $nbProduct . '</span>
                        </div>
                    ';
                }
            }
        }
        return $dataSource;
    }
}
