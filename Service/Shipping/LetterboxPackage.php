<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@totalinternetgroup.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\PostNL\Service\Shipping;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Store\Model\ScopeInterface;
use TIG\PostNL\Config\Provider\LetterBoxPackageConfiguration;

// @codingStandardsIgnoreFile
class LetterboxPackage
{
    public $totalVolume    = 0;
    public $totalWeight    = 0;
    public $hasMaximumQty  = true;
    public $maximumWeight  = 2;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LetterBoxPackageConfiguration
     */
    private $letterBoxPackageConfiguration;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * LetterboxPackage constructor.
     *
     * @param ScopeConfigInterface          $scopeConfig
     * @param LetterBoxPackageConfiguration $letterBoxPackageConfiguration
     * @param OrderRepositoryInterface      $orderRepository
     * @param CollectionFactory             $productCollectionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LetterBoxPackageConfiguration $letterBoxPackageConfiguration,
        OrderRepositoryInterface $orderRepository,
        CollectionFactory $productCollectionFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->letterBoxPackageConfiguration = $letterBoxPackageConfiguration;
        $this->orderRepository = $orderRepository;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @param QuoteItem[]|OrderItem[]|ShipmentItemInterface[] $products
     * @param $isPossibleLetterboxPackage
     *
     * @return bool
     */
    public function isLetterboxPackage($products, $isPossibleLetterboxPackage)
    {
        $this->totalVolume    = 0;
        $this->totalWeight    = 0;
        $this->hasMaximumQty  = true;

        $calculationMode = $this->letterBoxPackageConfiguration->getLetterBoxPackageCalculationMode();

        // If the order is not a letterbox package but it could be we want to return true so the shipment type comment is updated on the order grid.
        if ($calculationMode === 'manually' && !$isPossibleLetterboxPackage) {
            return false;
        }

        $productIds = [];
        foreach ($products as $product) {
            $productIds[$product->getProductId()] = $product->getQty();
        }

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addFieldToFilter('entity_id', ['in' => array_keys($productIds)]);
        $productCollection->addAttributeToSelect('postnl_max_qty_letterbox');

        foreach ($productCollection->getItems() as $product) {
            // $productIds[$product->getId()] contains the qty, seen in the previous foreach

            $this->fitsLetterboxPackage($product, $productIds[$product->getId()]);
        }

        // check if all products fit in a letterbox package and the weight is equal or lower than 2 kilograms.
        if ($this->totalVolume <= 1 && $this->totalWeight <= $this->maximumWeight && $this->hasMaximumQty == true) {
            return true;
        }

        return false;
    }

    /**
     * @param $product
     * @param $qty
     *
     * Based on the product attribute postnl_max_qty_letterbox, a percentage
     * is calculated for each product. If, for example, the attribute is set
     * to 4, each product will weight 25%. If the products in the cart
     * have a total weight of over 100%, the order will not fit as a letterbox.
     */
    public function fitsLetterboxPackage($product, $qty)
    {
        $maximumQtyLetterbox = floatval($product->getPostnlMaxQtyLetterbox());

        if ($maximumQtyLetterbox === 0.0) {
            $this->hasMaximumQty = false;
            return;
        }

        $this->totalVolume += 1 / $maximumQtyLetterbox * $qty;
        $this->getTotalWeight($product, $qty);
    }

    /**
     * @param $product
     * @param $orderedQty
     */
    public function getTotalWeight($product, $orderedQty)
    {
        $weightUnit = $this->scopeConfig->getValue(
            'general/locale/weight_unit',
            ScopeInterface::SCOPE_STORE
        );

        // maximum weight for a letterbox package is 4.4 in lbs
        if ($weightUnit === 'lbs') {
            $this->maximumWeight = 4.4;
        }

        $this->totalWeight += $product->getWeight() * $orderedQty;
    }

    /**
     * @param \TIG\PostNL\Model\Order $order
     *
     * @return bool
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isPossibleLetterboxPackage($order)
    {
        $magentoOrder = $this->orderRepository->get($order->getQuoteId());
        $products = $magentoOrder->getAllItems();
        $shippingAddress = $order->getShippingAddress();

        if ($order->getProductCode() == '3085' &&
            $this->isLetterboxPackage($products, true) &&
            $shippingAddress->getCountryId() == 'NL') {
            return true;
        }

        return false;
    }
}
