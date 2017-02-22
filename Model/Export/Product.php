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

namespace Lengow\Connector\Model\Export;

use Lengow\Connector\Helper\Data as DataHelper;

/**
 * Lengow export product
 */
class Product
{
    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

    /**
     * @var integer counter for simple product
     */
    protected $_simpleCounter = 0;

    /**
     * @var integer counter for simple product disabled
     */
    protected $_simpleDisabledCounter = 0;

    /**
     * @var integer counter for configurable product
     */
    protected $_configurableCounter = 0;

    /**
     * @var integer counter for grouped product
     */
    protected $_groupedCounter = 0;

    /**
     * @var integer counter for virtual product
     */
    protected $_virtualCounter = 0;

    /**
     * @var integer counter for downloadable product
     */
    protected $_downloadableCounter = 0;

    /**
     * Constructor
     *
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     */
    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->_dataHelper = $dataHelper;
    }

    /**
     * Init a new product
     *
     * @param array $params optional options for init
     */
    public function init($params)
    {
    }

    /**
     * Get data of current product
     *
     * @param string $field field to export
     *
     * @return string
     */
    public function getData($field)
    {
        switch ($field) {
            default:
                return '';
        }
    }

    /**
     * Clean data for next product
     */
    public function clean()
    {

    }

    /**
     * Get all counters for different product types
     *
     * @return array
     */
    public function getAllCounter()
    {
        $simpleTotal = $this->_simpleCounter - $this->_simpleDisabledCounter;
        $total = $simpleTotal + $this->_configurableCounter + $this->_groupedCounter
            + $this->_virtualCounter + $this->_downloadableCounter;
        $counters = [
            'total'           => $total,
            'simple'          => $this->_simpleCounter,
            'simple_enable'   => $simpleTotal,
            'simple_disabled' => $this->_simpleDisabledCounter,
            'configurable'    => $this->_configurableCounter,
            'grouped'         => $this->_groupedCounter,
            'virtual'         => $this->_virtualCounter,
            'downloadable'    => $this->_downloadableCounter
        ];
        return $counters;
    }
}
