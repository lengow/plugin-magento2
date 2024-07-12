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

use Exception;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject\Copy;
use Magento\Framework\DataObject\Factory as DataObjectFactory;
use Magento\Framework\Message\Factory as MessageFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Interceptor as ProductInterceptor;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\CustomerFactory as MagentoCustomerFactory;
use Magento\Quote\Model\Cart\CurrencyFactory;
use Magento\Quote\Model\Quote as MagentoQuote;
use Magento\Quote\Model\QuoteValidator;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Quote\Model\Quote\Item\Processor as ItemProcessor;
use Magento\Quote\Model\Quote\PaymentFactory;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Quote\Model\Quote\TotalsReader;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory as ItemCollectionFactory;
use Magento\Quote\Model\ResourceModel\Quote\Payment\CollectionFactory as PaymentCollectionFactory;
use Magento\Quote\Model\ShippingAssignmentFactory;
use Magento\Quote\Model\ShippingFactory;
use Magento\Sales\Model\Status\ListFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\TaxCalculation;
use Lengow\Connector\Helper\Data as DataHelper;
use Lengow\Connector\Helper\Security as SecurityHelper;
use Lengow\Connector\Model\Exception as LengowException;

class Quote extends MagentoQuote
{
    /**
     * @var TaxCalculation Magento tax calculation instance
     */
    private $taxCalculation;

    /**
     * @var Calculation Magento calculation instance
     */
    private $calculation;

    /**
     * @var DataHelper Lengow data helper instance
     */
    private $dataHelper;

    /**
     * @var SecurityHelper Lengow security helper instance
     */
    private $securityHelper;

    /**
     * Constructor
     *
     * @param Context $context Magento context instance
     * @param Registry $registry Magento registry instance
     * @param ExtensionAttributesFactory $extensionFactory Magento extension attribute factory instance
     * @param AttributeValueFactory $customAttributeFactory Magento attribute value factory instance
     * @param QuoteValidator $quoteValidator Magento quote validator instance
     * @param Product $catalogProduct Magento product instance
     * @param ScopeConfigInterface $scopeConfig Magento scope config instance
     * @param StoreManagerInterface $storeManager Magento scope manager instance
     * @param ScopeConfigInterface $config Magento scope config instance
     * @param AddressFactory $quoteAddressFactory Magento address factory instance
     * @param MagentoCustomerFactory $customerFactory Magento customer factory instance
     * @param GroupRepositoryInterface $groupRepository Magento group repository instance
     * @param ItemCollectionFactory $quoteItemCollectionFactory Magento item collection factory instance
     * @param ItemFactory $quoteItemFactory Magento item factory instance
     * @param MessageFactory $messageFactory Magento message factory instance
     * @param ListFactory $statusListFactory Magento list factory instance
     * @param ProductRepositoryInterface $productRepository Magento product repository instance
     * @param PaymentFactory $quotePaymentFactory Magento payment factory instance
     * @param PaymentCollectionFactory $quotePaymentCollectionFactory Magento payment collection factory instance
     * @param Copy $objectCopyService Magento copy instance
     * @param StockRegistryInterface $stockRegistry Magento stock registry instance
     * @param ItemProcessor $itemProcessor Magento item processor instance
     * @param DataObjectFactory $objectFactory Magento context instance
     * @param AddressRepositoryInterface $addressRepository Magento address repository instance
     * @param SearchCriteriaBuilder $criteriaBuilder Magento search criteria builder instance
     * @param FilterBuilder $filterBuilder Magento filter builder instance
     * @param AddressInterfaceFactory $addressDataFactory Magento address factory instance
     * @param CustomerInterfaceFactory $customerDataFactory Magento customer factory instance
     * @param CustomerRepositoryInterface $customerRepository Magento customer repository instance
     * @param DataObjectHelper $dataObjectHelper Magento data object helper instance
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter Magento extensible data object instance
     * @param CurrencyFactory $currencyFactory Magento currency factory instance
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor Magento join processor instance
     * @param TotalsCollector $totalsCollector Magento total collector instance
     * @param TotalsReader $totalsReader Magento total reader instance
     * @param ShippingFactory $shippingFactory Magento shipping factory instance
     * @param ShippingAssignmentFactory $shippingAssignmentFactory Magento shipping assignment factory instance
     * @param TaxCalculation $taxCalculation Magento tax calculation instance
     * @param Calculation $calculation Magento calculation instance
     * @param DataHelper $dataHelper Lengow data helper instance
     * @param SecurityHelper $securityHelper Lengow security helper instance
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
        MagentoCustomerFactory $customerFactory,
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
        ItemProcessor $itemProcessor,
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
        DataHelper $dataHelper,
        SecurityHelper $securityHelper
    ) {
        $this->taxCalculation = $taxCalculation;
        $this->calculation = $calculation;
        $this->dataHelper = $dataHelper;
        $this->securityHelper = $securityHelper;
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
     * @param boolean $priceIncludeTax price include tax
     *
     * @return Quote
     *
     * @throws Exception|LengowException
     */
    public function addLengowProducts($products, bool $priceIncludeTax = true): Quote
    {
        foreach ($products as $product) {
            /** @var \Magento\Catalog\Model\Product $magentoProduct **/
            $magentoProduct = $product['magento_product'];
            if ($magentoProduct->getId()) {
                // check if the product is disabled
                $this->checkProductStatus($magentoProduct);
                // check if the product has enough stock
                $this->checkProductQuantity($magentoProduct, $product['quantity']);
                // get product prices
                $priceProduct = $product['price_unit'] ?? 0.0;
                $tax   = $product['tax_unit'] ?? 0.0;
                if (!$priceIncludeTax) {
                    $taxRate = $this->taxCalculation->getCalculatedRate(
                        $magentoProduct->getTaxClassId(),
                        $this->getCustomer()->getId(),
                        $this->getStore()
                    );
                    $tax = $this->calculation->calcTaxAmount($priceProduct, $taxRate, true);
                }

                $priceProduct -= $tax;
                $magentoProduct->setPrice(round($priceProduct, 3));
                $magentoProduct->setSpecialPrice(round($priceProduct, 3));
                $magentoProduct->setFinalPrice(round($priceProduct + $tax, 3));
                $magentoProduct->setIsSuperMode(true);
                // Warning Deprecated after magento 2.4.xx !
                $magentoProduct->setPriceCalculation(false);
                // option "import with product's title from Lengow"
                $magentoProduct->setName($product['title']);
                // add item to quote

                $priceItem = $magentoProduct->getPrice();
                if ($priceIncludeTax) {
                    $priceItem = $magentoProduct->getFinalPrice();
                }

                if ($magentoProduct->getTypeId() === 'bundle') {

                    $bundleOptions = $this->getBundleOptions($magentoProduct);

                    $params = [
                        'product' => $magentoProduct->getId(),
                        'bundle_option' => $bundleOptions,
                        'qty' => $product['quantity'],
                    ];
                    $requestAddBundle = new \Magento\Framework\DataObject($params);
                    $this->addProduct($magentoProduct, $requestAddBundle);


                } else {
                    $quoteItem = $this->_quoteItemFactory->create()
                    ->setProduct($magentoProduct)
                    ->setQty($product['quantity'])
                    ->setCustomPrice($priceItem)
                    ->setOriginalCustomPrice($priceItem);
                    $this->addItem($quoteItem);

                }

            }
        }

        return $this;
    }

    /**
     * Check if the product is disabled
     *
     * @param ProductInterceptor $product
     *
     * @throws LengowException
     */
    public function checkProductStatus(ProductInterceptor $product): void
    {
        if ((int) $product->getStatus() === Status::STATUS_DISABLED
            && version_compare($this->securityHelper->getMagentoVersion(), '2.2.0', '>=')
        ) {
            throw new LengowException(
                $this->dataHelper->setLogMessage(
                    'product id %1 can not be added to the quote because it is disabled',
                    [$product->getId()]
                )
            );
        }
    }

    /**
     * Check if the product has enough stock
     *
     * @param ProductInterceptor $product
     * @param integer $quantity
     *
     * @throws LengowException
     */
    public function checkProductQuantity(ProductInterceptor $product, int $quantity): void
    {
        $stockItem = $product->getExtensionAttributes()->getStockItem();
        if ($stockItem && $stockItem->getManageStock()) {
            // get salable quantity
            $stockStatus = $this->stockRegistry->getStockStatus(
                $product->getId(),
                $product->getStore()->getWebsiteId()
            );
            if ($stockStatus && $quantity > (float) $stockStatus->getQty()) {
                throw new LengowException(
                    $this->dataHelper->setLogMessage(
                        'product id %1 can not be added to the quote because the stock is insufficient',
                        [$product->getId()]
                    )
                );
            }
        }
    }
    /**
     * get all the selection products used in bundle product
     * @param $product
     * @return mixed
     */
    private function getBundleOptions($product)
    {

        $bundleOptions = [];
        $optionIds = [];
        $selectionCollection = $product->getTypeInstance()
            ->getSelectionsCollection(
                $product->getTypeInstance()->getOptionsIds($product),
                $product
            );

        foreach ($selectionCollection as $selection) {
            if (in_array($selection->getOptionId(), $optionIds)) {
                continue;
            }
            $optionIds[] = $selection->getOptionId();
        }
        // default prodcut selection in many options
        if (count($optionIds) > 1) {
            foreach ($selectionCollection as $selection) {
                if (!$selection->getIsDefault()){
                    continue;
                }
                $bundleOptions[$selection->getOptionId()][] = $selection->getSelectionId();
            }

        } else {
            // all selection in one option
            foreach ($selectionCollection as $selection) {
                $bundleOptions[$selection->getOptionId()][] = $selection->getSelectionId();
            }
        }
        // first product selection in many options
        if (empty($bundleOptions)) {
            foreach ($selectionCollection as $selection) {
                if (isset($bundleOptions[$selection->getOptionId()])){
                    continue;
                }
                $bundleOptions[$selection->getOptionId()][] = $selection->getSelectionId();
            }
        }

        return $bundleOptions;
    }
}

