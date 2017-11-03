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

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Catalog\Model\Product\Attribute\Repository as ProductAttribute;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Tax\Model\TaxCalculation;
use Magento\Tax\Model\Calculation;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\Group;
use Lengow\Connector\Model\Import\Quote\Item as QuoteItem;
use Lengow\Connector\Model\ResourceModel\Log as ResourceLog;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Model\Exception as LengowException;

class Quote extends \Magento\Quote\Model\Quote
{
    /**
     * @var \Magento\Customer\Model\Group Magento group customer
     */
    protected $_groupCustomer;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory Magento product collection
     */
    protected $_productCollection;

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Repository Magento product attribute
     */
    protected $_productAttribute;

    /**
     * @var \Magento\Catalog\Model\ProductFactory Magento product factory
     */
    protected $_productFactory;

    /**
     * @var \Lengow\Connector\Helper\Data Lengow data helper instance
     */
    protected $_dataHelper;

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
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param \Magento\Quote\Model\QuoteValidator $quoteValidator
     * @param \Magento\Catalog\Helper\Product $catalogProduct
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollectionFactory
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
     * @param \Magento\Framework\Message\Factory $messageFactory
     * @param \Magento\Sales\Model\Status\ListFactory $statusListFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Quote\Model\Quote\PaymentFactory $quotePaymentFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote\Payment\CollectionFactory $quotePaymentCollectionFactory
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\Quote\Model\Quote\Item\Processor $itemProcessor
     * @param \Magento\Framework\DataObject\Factory $objectFactory
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param \Magento\Quote\Model\Cart\CurrencyFactory $currencyFactory
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector
     * @param \Magento\Quote\Model\Quote\TotalsReader $totalsReader
     * @param \Magento\Quote\Model\ShippingFactory $shippingFactory
     * @param \Magento\Quote\Model\ShippingAssignmentFactory $shippingAssignmentFactory
     * @param \Magento\Tax\Model\TaxCalculation $taxCalculation tax calculation interface
     * @param \Magento\Tax\Model\Calculation $calculation calculation
     * @param \Magento\Catalog\Model\ProductFactory $productFactory Magento product factory
     * @param \Magento\Catalog\Model\Product\Attribute\Repository $productAttribute Magento product attribute
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection Magento product collection
     * @param \Magento\Customer\Model\Group $groupCustomer Magento group customer
     * @param \Lengow\Connector\Model\Import\Quote\Item $quoteItem Lengow quote item instance
     * @param \Lengow\Connector\Helper\Data $dataHelper Lengow data helper instance
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        \Magento\Catalog\Helper\Product $catalogProduct,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollectionFactory,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Magento\Framework\Message\Factory $messageFactory,
        \Magento\Sales\Model\Status\ListFactory $statusListFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Quote\Model\Quote\PaymentFactory $quotePaymentFactory,
        \Magento\Quote\Model\ResourceModel\Quote\Payment\CollectionFactory $quotePaymentCollectionFactory,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Quote\Model\Quote\Item\Processor $itemProcessor,
        \Magento\Framework\DataObject\Factory $objectFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Magento\Quote\Model\Cart\CurrencyFactory $currencyFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Model\Quote\TotalsReader $totalsReader,
        \Magento\Quote\Model\ShippingFactory $shippingFactory,
        \Magento\Quote\Model\ShippingAssignmentFactory $shippingAssignmentFactory,
        TaxCalculation $taxCalculation,
        Calculation $calculation,
        ProductFactory $productFactory,
        ProductAttribute $productAttribute,
        ProductCollectionFactory $productCollection,
        Group $groupCustomer,
        QuoteItem $quoteItem,
        DataHelper $dataHelper
    )
    {
        $this->_taxCalculation = $taxCalculation;
        $this->_calculation = $calculation;
        $this->_productFactory = $productFactory;
        $this->_productAttribute = $productAttribute;
        $this->_productCollection = $productCollection;
        $this->_groupCustomer = $groupCustomer;
        $this->_quoteItem = $quoteItem;
        $this->_dataHelper = $dataHelper;
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $quoteValidator,
            $catalogProduct, $scopeConfig, $storeManager, $config, $quoteAddressFactory, $customerFactory,
            $groupRepository, $quoteItemCollectionFactory, $quoteItemFactory, $messageFactory, $statusListFactory,
            $productRepository, $quotePaymentFactory, $quotePaymentCollectionFactory, $objectCopyService,
            $stockRegistry, $itemProcessor, $objectFactory, $addressRepository, $criteriaBuilder, $filterBuilder,
            $addressDataFactory, $customerDataFactory, $customerRepository, $dataObjectHelper, $extensibleDataObjectConverter,
            $currencyFactory, $extensionAttributesJoinProcessor, $totalsCollector, $totalsReader, $shippingFactory,
            $shippingAssignmentFactory);
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
                if (!$priceIncludeTax) {
                    $basedOn = $this->_scopeConfig->getValue(
                        \Magento\Tax\Model\Config::CONFIG_XML_PATH_BASED_ON,
                        'store',
                        $this->getStore()
                    );
                    $countryId = ($basedOn == 'shipping')
                        ? $this->getShippingAddress()->getCountryId()
                        : $this->getBillingAddress()->getCountryId();
                    $taxRequest = new \Magento\Framework\DataObject();
                    $groupCustomer = $this->_groupCustomer->load($this->getCustomer()->getGroupId());
                    $taxRequest->setCountryId($countryId)
                        ->setCustomerClassId($groupCustomer->getCustomerGroupCode())
                        ->setProductClassId($magentoProduct->getTaxClassId());
                    $taxRate = (float)$this->_calculation->getRate($taxRequest);
                    $tax = (float)$this->_calculation->calcTaxAmount($price, $taxRate, true);
                    $price = $price - $tax;
                }
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

        return $this;
    }

    /**
     * Find product in Magento based on API data
     *
     * @param mixed $products all product datas
     * @param Marketplace $marketplace Lengow marketplace instance
     * @param string $marketplaceSku marketplace sku
     * @param boolean $logOutput see log or not
     *
     * @throws LengowException product not be found / product is a parent
     *
     * @return array
     */
    protected function _getProducts($products, $marketplace, $marketplaceSku, $logOutput)
    {
        $lengowProducts = [];
        foreach ($products as $product) {
            $found = false;
            $magentoProduct = false;
            $orderLineId = (string)$product->marketplace_order_line_id;
            // check whether the product is canceled
            if ($product->marketplace_status != null) {
                $stateProduct = $marketplace->getStateLengow((string)$product->marketplace_status);
                if ($stateProduct == 'canceled' || $stateProduct == 'refused') {
                    $productId = (!is_null($product->merchant_product_id->id)
                        ? (string)$product->merchant_product_id->id
                        : (string)$product->marketplace_product_id
                    );
                    $this->_dataHelper->log(
                        'Import',
                        $this->_dataHelper->setLogMessage(
                            'product %1 could not be added to cart - status: %2',
                            [$productId, $stateProduct]
                        ),
                        $logOutput,
                        $marketplaceSku
                    );
                    continue;
                }
            }
            $productIds = [
                'merchant_product_id' => $product->merchant_product_id->id,
                'marketplace_product_id' => $product->marketplace_product_id
            ];
            $productField = $product->merchant_product_id->field != null
                ? strtolower((string)$product->merchant_product_id->field)
                : false;
            // search product foreach value
            foreach ($productIds as $attributeName => $attributeValue) {
                // remove _FBA from product id
                $attributeValue = preg_replace('/_FBA$/', '', $attributeValue);
                if (empty($attributeValue)) {
                    continue;
                }
                // search by field if exists
                if ($productField) {
                    $attributeModel = $this->_productAttribute->get($productField);
                    if ($attributeModel->getAttributeId()) {
                        $collection = $this->_productCollection->create()
                            ->setStoreId($this->getStore()->getStoreId())
                            ->addAttributeToSelect($productField)
                            ->addAttributeToFilter($productField, $attributeValue)
                            ->setPage(1, 1)
                            ->getData();
                        if (is_array($collection) && count($collection) > 0) {
                            $magentoProduct = $this->_productFactory->create()->load($collection[0]['entity_id']);
                        }
                    }
                }
                // search by id or sku
                if (!$magentoProduct || !$magentoProduct->getId()) {
                    if (preg_match('/^[0-9]*$/', $attributeValue)) {
                        $magentoProduct = $this->_productFactory->create()->load((integer)$attributeValue);
                    }
                    if (!$magentoProduct || !$magentoProduct->getId()) {
                        $attributeValue = str_replace('\_', '_', $attributeValue);
                        $magentoProduct = $this->_productFactory->create()->load(
                            $this->_productFactory->create()->getIdBySku($attributeValue)
                        );
                    }
                }
                if ($magentoProduct && $magentoProduct->getId()) {
                    $magentoProductId = $magentoProduct->getId();
                    // save total row Lengow for each product
                    if (array_key_exists($magentoProductId, $lengowProducts)) {
                        $lengowProducts[$magentoProductId]['quantity'] += (int)$product->quantity;
                        $lengowProducts[$magentoProductId]['amount'] += (float)$product->amount;
                        $lengowProducts[$magentoProductId]['order_line_ids'][] = $orderLineId;
                    } else {
                        $lengowProducts[$magentoProductId] = [
                            'magento_product' => $magentoProduct,
                            'sku' => (string)$magentoProduct->getSku(),
                            'title' => (string)$product->title,
                            'amount' => (float)$product->amount,
                            'price_unit' => (float)($product->amount / $product->quantity),
                            'quantity' => (int)$product->quantity,
                            'order_line_ids' => [$orderLineId],
                        ];
                    }
                    $this->_dataHelper->log(
                        'Import',
                        $this->_dataHelper->setLogMessage(
                            'product_be_found: "product id %1 found with field %2 (%3)"',
                            [
                                'product_id' => $magentoProduct->getId(),
                                'attribute_name' => $attributeName,
                                'attribute_value' => $attributeValue
                            ]
                        ),
                        $logOutput,
                        $marketplaceSku
                    );
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $productId = (!is_null($product->merchant_product_id->id)
                    ? (string)$product->merchant_product_id->id
                    : (string)$product->marketplace_product_id
                );
                throw new LengowException(
                    $this->_dataHelper->setLogMessage(
                        'product %1 could not be found',
                        [$productId]
                    )
                );
            } elseif ($magentoProduct->getTypeId() == Configurable::TYPE_CODE) {
                throw new LengowException(
                    $this->_dataHelper->setLogMessage(
                        'product %1 is a parent ID. Product variation is needed',
                        [$magentoProduct->getId()]
                    )
                );
            }
        }
        return $lengowProducts;
    }

    /**
     * Get Lengow Products
     *
     * @param string $productId Magento product id
     *
     * @return array
     */
    public function getLengowProducts($productId = null)
    {
        if (is_null($productId)) {
            return $this->_lengowProducts;
        } else {
            return $this->_lengowProducts[$productId];
        }
    }
}
