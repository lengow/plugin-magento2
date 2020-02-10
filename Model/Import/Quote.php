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

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Quote\Model\QuoteValidator;
use Magento\Catalog\Helper\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\Quote\AddressFactory ;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory as ItemCollectionFactory;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Framework\Message\Factory as MessageFactory;
use Magento\Sales\Model\Status\ListFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\Quote\PaymentFactory;
use Magento\Quote\Model\ResourceModel\Quote\Payment\CollectionFactory as PaymentCollectionFactory;
use Magento\Framework\DataObject\Copy;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Quote\Model\Quote\Item\Processor;
use Magento\Framework\DataObject\Factory as DataObjectFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Quote\Model\Cart\CurrencyFactory;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Quote\Model\Quote\TotalsReader;
use Magento\Quote\Model\ShippingFactory;
use Magento\Quote\Model\ShippingAssignmentFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Catalog\Model\Product\Attribute\Repository as ProductAttribute;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Tax\Model\TaxCalculation;
use Magento\Tax\Model\Calculation;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\Group;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Lengow\Connector\Model\Import\Quote\Item as QuoteItem;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Model\Import\Order as LengowOrder;
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
     * @var \Lengow\Connector\Helper\Security Lengow security helper instance
     */
    protected $_securityHelper;

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
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
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
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor
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
     * @param \Lengow\Connector\Helper\Security $securityHelper Lengow security helper instance
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        QuoteValidator $quoteValidator,
        Product $catalogProduct,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $config,
        AddressFactory $quoteAddressFactory,
        CustomerFactory $customerFactory,
        GroupRepositoryInterface $groupRepository,
        ItemCollectionFactory $quoteItemCollectionFactory,
        ItemFactory $quoteItemFactory,
        MessageFactory $messageFactory,
        ListFactory $statusListFactory,
        ProductRepositoryInterface $productRepository,
        PaymentFactory $quotePaymentFactory,
        PaymentCollectionFactory $quotePaymentCollectionFactory,
        Copy $objectCopyService,
        StockRegistryInterface $stockRegistry,
        Processor $itemProcessor,
        DataObjectFactory $objectFactory,
        AddressRepositoryInterface $addressRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        FilterBuilder $filterBuilder,
        AddressInterfaceFactory $addressDataFactory,
        CustomerInterfaceFactory $customerDataFactory,
        CustomerRepositoryInterface $customerRepository,
        DataObjectHelper $dataObjectHelper,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        CurrencyFactory $currencyFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        TotalsCollector $totalsCollector,
        TotalsReader $totalsReader,
        ShippingFactory $shippingFactory,
        ShippingAssignmentFactory $shippingAssignmentFactory,
        TaxCalculation $taxCalculation,
        Calculation $calculation,
        ProductFactory $productFactory,
        ProductAttribute $productAttribute,
        ProductCollectionFactory $productCollection,
        Group $groupCustomer,
        QuoteItem $quoteItem,
        DataHelper $dataHelper,
        SecurityHelper $securityHelper
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
        $this->_securityHelper = $securityHelper;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $quoteValidator,
            $catalogProduct,
            $scopeConfig,
            $storeManager,
            $config,
            $quoteAddressFactory,
            $customerFactory,
            $groupRepository,
            $quoteItemCollectionFactory,
            $quoteItemFactory,
            $messageFactory,
            $statusListFactory,
            $productRepository,
            $quotePaymentFactory,
            $quotePaymentCollectionFactory,
            $objectCopyService,
            $stockRegistry,
            $itemProcessor,
            $objectFactory,
            $addressRepository,
            $criteriaBuilder,
            $filterBuilder,
            $addressDataFactory,
            $customerDataFactory,
            $customerRepository,
            $dataObjectHelper,
            $extensibleDataObjectConverter,
            $currencyFactory,
            $extensionAttributesJoinProcessor,
            $totalsCollector,
            $totalsReader,
            $shippingFactory,
            $shippingAssignmentFactory
        );
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
     * @throws \Exception|LengowException product not be found / product is a parent
     *
     * @return Quote
     */
    public function addLengowProducts($products, $marketplace, $marketplaceSku, $logOutput, $priceIncludeTax = true)
    {
        $this->_lengowProducts = $this->_getProducts($products, $marketplace, $marketplaceSku, $logOutput);
        foreach ($this->_lengowProducts as $lengowProduct) {
            $magentoProduct = $lengowProduct['magento_product'];
            if ($magentoProduct->getId()) {
                // check if the product is disabled
                $this->checkProductStatus($magentoProduct);
                // check if the product has enough stock
                $this->checkProductQuantity($magentoProduct, $lengowProduct['quantity']);
                // get product prices
                $price = $lengowProduct['price_unit'];
                if (!$priceIncludeTax) {
                    $taxRate = $this->_taxCalculation->getCalculatedRate(
                        $magentoProduct->getTaxClassId(),
                        $this->getCustomer()->getId(),
                        $this->getStore()
                    );
                    $tax = $this->_calculation->calcTaxAmount($price, $taxRate, true);
                    $price = $price - $tax;
                }
                $magentoProduct->setPrice($price);
                $magentoProduct->setSpecialPrice($price);
                $magentoProduct->setFinalPrice($price);
                // option "import with product's title from Lengow"
                $magentoProduct->setName($lengowProduct['title']);
                // add item to quote
                $quoteItem = $this->_quoteItemFactory->create()
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
                if ($stateProduct === LengowOrder::STATE_CANCELED || $stateProduct === LengowOrder::STATE_REFUSED) {
                    $productId = !is_null($product->merchant_product_id->id)
                        ? (string)$product->merchant_product_id->id
                        : (string)$product->marketplace_product_id;
                    $this->_dataHelper->log(
                        DataHelper::CODE_IMPORT,
                        $this->_dataHelper->setLogMessage(
                            'product %1 could not be added to cart - status: %2',
                            [
                                $productId,
                                $stateProduct,
                            ]
                        ),
                        $logOutput,
                        $marketplaceSku
                    );
                    continue;
                }
            }
            $productIds = [
                'merchant_product_id' => $product->merchant_product_id->id,
                'marketplace_product_id' => $product->marketplace_product_id,
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
                    try {
                        $attributeModel = $this->_productAttribute->get($productField);
                    } catch (\Exception $e) {
                        $attributeModel = false;
                    }
                    if ($attributeModel) {
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
                        DataHelper::CODE_IMPORT,
                        $this->_dataHelper->setLogMessage(
                            'product id %1 found with field %2 (%3)',
                            [
                                $magentoProduct->getId(),
                                $attributeName,
                                $attributeValue,
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
                $productId = !is_null($product->merchant_product_id->id)
                    ? (string)$product->merchant_product_id->id
                    : (string)$product->marketplace_product_id;
                throw new LengowException(
                    $this->_dataHelper->setLogMessage(
                        'product %1 could not be found',
                        [$productId]
                    )
                );
            } elseif ($magentoProduct->getTypeId() === Configurable::TYPE_CODE) {
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
     * Check if the product is disabled
     *
     * @param \Magento\Catalog\Model\Product\Interceptor $product
     *
     * @throws LengowException product is disabled
     */
    public function checkProductStatus($product)
    {
        if (version_compare($this->_securityHelper->getMagentoVersion(), '2.2.0', '>=')
            && (int)$product->getStatus() === Status::STATUS_DISABLED
        ) {
            throw new LengowException(
                $this->_dataHelper->setLogMessage(
                    'product id %1 can not be added to the quote because it is disabled',
                    [$product->getId()]
                )
            );
        }
    }

    /**
     * Check if the product has enough stock
     *
     * @param \Magento\Catalog\Model\Product\Interceptor $product
     * @param integer $quantity
     *
     * @throws LengowException stock is insufficient
     */
    public function checkProductQuantity($product , $quantity)
    {
        $stockItem = $product->getExtensionAttributes()->getStockItem();
        if ($stockItem->getManageStock()) {
            // get salable quantity
            $stockStatus = $this->stockRegistry->getStockStatus(
                $product->getId(),
                $product->getStore()->getWebsiteId()
            );
            if ($stockStatus && $quantity > (float)$stockStatus->getQty()) {
                throw new LengowException(
                    $this->_dataHelper->setLogMessage(
                        'product id %1 can not be added to the quote because the stock is insufficient',
                        [$product->getId()]
                    )
                );
            }
        }
    }

    /**
     * Get Lengow Products
     *
     * @param string|null $productId Magento product id
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
