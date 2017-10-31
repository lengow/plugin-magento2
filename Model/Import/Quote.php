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

use Magento\Tax\Model\TaxCalculation;
use Magento\Tax\Model\Calculation;
use Lengow\Connector\Model\Import\Quote\Item as QuoteItem;
use Lengow\Connector\Model\ResourceModel\Log as ResourceLog;
use Magento\Tax\Model\Config;
use Magento\Tax\Model\TaxConfigProvider;

class Quote extends \Magento\Quote\Model\Quote
{

    /**
     * @var \Lengow\Connector\Model\Import\Quote\Item Lengow quote item instance
     */
    protected $_quoteItem;

    /**
     * @var \Magento\Tax\Model\TaxCalculation tax calculation interface
     */
    protected $_taxCalculation;

    /**
     * @var \Magento\Tax\Model\Calculation calculation
     */
    protected $_calculation;

    /**
     * @var array row total Lengow
     */
    protected $_lengowProducts = [];

    /**
     * Constructor
     *
     * @param \Magento\Tax\Model\TaxCalculation $taxCalculation tax calculation interface
     * @param \Magento\Tax\Model\Calculation $calculation calculation
     * @param \Lengow\Connector\Model\Import\Quote\Item $quoteItem Lengow quote item instance
     */
    public function __construct(
        TaxCalculation $taxCalculation,
        Calculation $calculation,
        QuoteItem $quoteItem
    )
    {
        $this->_taxCalculation = $taxCalculation;
        $this->_calculation = $calculation;
        $this->_quoteItem = $quoteItem;
        parent::__construct();
    }

    /**
     * Add products from API to current quote
     *
     * @param mixed $products Lengow products list
     * @param Marketplace $marketplace Lengow marketplace instance
     * @param string $marketplaceSku marketplace sku
     * @param boolean $logOutput see log or not
     * @param boolean $priceIncludeTax price include tax
     *
     * @return Quote
     */
    public function addLengowProducts($products, $marketplace, $marketplaceSku, $logOutput, $priceIncludeTax = true)
    {
        $this->_lengowProducts = $this->_getProducts($products, $marketplace, $marketplaceSku, $logOutput);
        foreach ($this->_lengowProducts as $lengowProduct) {
            $magentoProduct = $lengowProduct['magento_product'];
            if ($magentoProduct->getId()) {
                $price = $lengowProduct['price_unit'];
                //TODO
                // if price not include tax -> get shipping cost without tax
//                if (!$priceIncludeTax) {
//                    $basedOn = Mage::getStoreConfig(
//                        Mage_Tax_Model_Config::CONFIG_XML_PATH_BASED_ON,
//                        $this->getStore()
//                    );
//                    $countryId = ($basedOn == 'shipping')
//                        ? $this->getShippingAddress()->getCountryId()
//                        : $this->getBillingAddress()->getCountryId();
//                    $taxCalculator = Mage::getModel('tax/calculation');
//                    $taxRequest = new Varien_Object();
//                    $taxRequest->setCountryId($countryId)
//                        ->setCustomerClassId($this->getCustomer()->getTaxClassId())
//                        ->setProductClassId($magentoProduct->getTaxClassId());
//                    $taxRate = (float)$taxCalculator->getRate($taxRequest);
//                    $tax = (float)$taxCalculator->calcTaxAmount($price, $taxRate, true);
//                    $price = $price - $tax;
//                }
                $magentoProduct->setPrice($price);
                $magentoProduct->setSpecialPrice($price);
                $magentoProduct->setFinalPrice($price);
                // option "import with product's title from Lengow"
                $magentoProduct->setName($lengowProduct['title']);
                // add item to quote
                $quoteItem = $this->_quoteItem
                    ->setProduct($magentoProduct)
                    ->setQty($lengowProduct['quantity'])
                    ->setConvertedPrice($price);
                $this->addItem($quoteItem);
            }
        }
    }
}
