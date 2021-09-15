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

use Magento\Directory\Model\CountryFactory;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Ui\Component\Listing\Columns\Column;
use Lengow\Connector\Model\Import\Order as LengowOrder;

class Country extends Column
{
    /**
     * @var Repository Magento asset repository instance
     */
    protected $_assetRepo;

    /**
     * @var CountryFactory Magento country factory instance
     */
    protected $_countryFactory;

    /**
     * Constructor
     *
     * @param CountryFactory $countryFactory Magento country factory instance
     * @param Repository $assetRepo Magento asset repository instance
     * @param ContextInterface $context Magento ui context instance
     * @param UiComponentFactory $uiComponentFactory Magento ui factory instance
     * @param array $components component data
     * @param array $data additional params
     */
    public function __construct(
        CountryFactory $countryFactory,
        Repository $assetRepo,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->_assetRepo = $assetRepo;
        $this->_countryFactory = $countryFactory;
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
                if ($item[LengowOrder::FIELD_DELIVERY_COUNTRY_ISO] !== null
                    && strlen($item[LengowOrder::FIELD_DELIVERY_COUNTRY_ISO]) === 2
                ) {
                    $filename = $this->_assetRepo->getUrl('Lengow_Connector/images/flag')
                        . DIRECTORY_SEPARATOR . strtoupper($item[LengowOrder::FIELD_DELIVERY_COUNTRY_ISO]) . '.png';
                    $countryName = $this->_countryFactory->create()
                        ->loadByCode($item[LengowOrder::FIELD_DELIVERY_COUNTRY_ISO])
                        ->getName();
                    $item[LengowOrder::FIELD_DELIVERY_COUNTRY_ISO] = '<a class="lengow_tooltip" href="#">
                        <img src="' . $filename . '" />
                        <span class="lengow_order_country">' . $countryName . '</span></a>';
                }
            }
        }
        return $dataSource;
    }
}
