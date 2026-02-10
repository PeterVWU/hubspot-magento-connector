<?php
/**
 * Makewebbetter
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement (EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://makewebbetter.com/license-agreement.txt
 *
 * @category    Makewebbetter
 * @package     Makewebbetter_HubIntegration
 * @author      Makewebbetter Core Team <connect@makewebbetter.com>
 * @copyright   Copyright Makewebbetter (http://makewebbetter.com/)
 * @license     https://makewebbetter.com/license-agreement.txt
 */

namespace Makewebbetter\HubIntegration\Model;

use Makewebbetter\HubIntegration\Helper\ConnectionManager;
use Makewebbetter\HubIntegration\Helper\Data as HubHelper;

class DataSet
{
    const PREFIX = 'HUB_';

    /**
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory
     */

    const CHUNK_SIZE = 50;

    public $subscriberCollection;

    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    public $productaction;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    public $productRepository;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    public $quoteRepository;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    public $quoteFactory;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    public $quoteRepo;


    /**
     * @var Properties
     */
    public $properties;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    public $sale;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    public $quote;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    public $product;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    public $customer;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    public $country;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory
     */
    public $shipment;

    /**
     * @var \Magento\Framework\Url $urlHelper
     */
    public $urlHelper;

    /**
     * @var ResourceModel\HubItem\CollectionFactory
     */
    public $hubItem;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $dateTime;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    public $groupRepository;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    public $subscriberFactory;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    public $imageBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $store;

    /**
     * @var \Magento\Directory\Model\CurrencyFactory
     */
    public $currencyFactory;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    public $categoryFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    public $orderItemCollectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    public $timeZone;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    public $regionFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    public $request;

    /**
     * @var array
     */
    public $categoryName = [];

    /**
     * @var array
     */
    public $productObject = [];

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    public $proFactory;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory
     */
    public $quoteItemCollectionFactory;

    /**
     * @var \Makewebbetter\HubIntegration\Helper\ConnectionManager
     */
    public $connectionManager;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resourceConnection;

    /**
     * @var HubHelper
     */
    public $hubHelper;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    public $customerModel;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerFactory
     */
    public $customerResourceModelFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    public $OrderInterface;



    /**
     * DataSync constructor.
     * @param \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quote
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $order
     * @param Properties $properties
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productFactory
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Url $urlHelper
     * @param ResourceModel\HubItem\CollectionFactory $hubItemFactory
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $store
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Catalog\Model\Product $proFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollectionFactory
     * @param \Makewebbetter\HubIntegration\Helper\ConnectionManager $connectionManager
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Makewebbetter\HubIntegration\Helper\Data $hubHelper
     */
    public function __construct(
        \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $subscriberCollection,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteRepository $quoteRepo,
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quote,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteItemCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $order,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $OrderInterface,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productFactory,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\Product $proFactory,
        \Magento\Catalog\Model\Product\Action $productaction,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceModelFactory,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Store\Model\StoreManagerInterface $store,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Url $urlHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Makewebbetter\HubIntegration\Model\Properties $properties,
        \Makewebbetter\HubIntegration\Model\ResourceModel\HubItem\CollectionFactory $hubItemFactory,
        \Makewebbetter\HubIntegration\Helper\ConnectionManager $connectionManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        HubHelper $hubHelper
    ) {
        $this->subscriberCollection = $subscriberCollection;
        $this->productaction = $productaction;
        $this->productRepository = $productRepository;
        $this->quoteRepository = $quoteRepository;
        $this->quoteFactory = $quoteFactory;
        $this->quoteRepo  = $quoteRepo;
        $this->customerModel = $customerModel;
        $this->customerResourceModelFactory = $customerResourceModelFactory;
        $this->OrderInterface = $OrderInterface;
        $this->shipment = $shipmentFactory;
        $this->properties = $properties;
        $this->sale = $order;
        $this->quote = $quote;
        $this->dateTime = $dateTime;
        $this->product = $productFactory;
        $this->customer = $customerFactory;
        $this->country = $countryFactory;
        $this->urlHelper = $urlHelper;
        $this->hubItem = $hubItemFactory;
        $this->groupRepository = $groupRepository;
        $this->subscriberFactory = $subscriberFactory;
        $this->imageBuilder = $imageBuilder;
        $this->store = $store;
        $this->currencyFactory = $currencyFactory;
        $this->categoryFactory = $categoryFactory;
        $this->timeZone = $timezone;
        $this->regionFactory = $regionFactory;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->request = $request;
        $this->proFactory = $proFactory;
        $this->quoteItemCollectionFactory = $quoteItemCollectionFactory;
        $this->connectionManager = $connectionManager;
        $this->resourceConnection = $resourceConnection;
        $this->hubHelper = $hubHelper;
    }

    /**
     * Prepare Image Url
     * @param $product
     * @return mixed
     */
    public function prepareImages($product)
    {
        $productImages = $product->getMediaGalleryImages();
        $mainImage = $product->getData('image');
        if (!empty($productImages)) {
            foreach ($productImages as $image) {
                if ($mainImage == $image->getFile()) {
                    return $image->getUrl();
                }
            }
        }
        return $mainImage;
    }

    /**
     * @param $category
     * @return mixed
     */
    public function getCategoryName($category)
    {
        if (isset($this->categoryName[$category])) {
            return $this->categoryName[$category];
        } else {
            $this->categoryName[$category] = $this->categoryFactory->create()->load($category)->getName();
            return $this->categoryName[$category];
        }
    }


    /**
     * Convert the date/date
     * @param $date
     * @return float|int
     */
    public function getDateStamp($date)
    {
        return strtotime(date("y-m-d", strtotime($date))) * 1000;
    }

    /**
     * @param $email
     * @return string
     */
    public function getNewsLetterSubscription($email)
    {
        $newsletterData = $this->subscriberCollection->create()
            ->addFieldToFilter('subscriber_email', $email)
            ->getFirstItem();
        $checkSubscriber = $newsletterData->getData('subscriber_status');
        if ($checkSubscriber == 1) {
            return "yes";
        } else {
            return "no";
        }
    }

    /**
     * @param $orderItem
     * @param $lastThreeProducts
     * @return mixed
     */
    public function getThreeLastProducts($orderItem, $lastThreeProducts)
    {
        if (empty($lastThreeProducts['last'])) {
            $lastThreeProducts['last'] = $orderItem->getData();
        } elseif ($lastThreeProducts['last']['created_at'] < $orderItem->getCreateddAt()) {
            $lastThreeProducts['third_last'] = $lastThreeProducts['second_last'];
            $lastThreeProducts['second_last'] = $lastThreeProducts['last'];
            $lastThreeProducts['last'] = $orderItem->getData();
        } elseif (empty($lastThreeProducts['second_last']) ||
            $lastThreeProducts['second_last'] ['created_at'] < $orderItem->getCreatedAt()) {
            $lastThreeProducts['third_last'] = $lastThreeProducts['second_last'];
            $lastThreeProducts['second_last'] = $orderItem->getData();
        } elseif (empty($lastThreeProducts['third_last']) ||
            $lastThreeProducts['third_last']['created_at'] < $orderItem->getCreatedAt()) {
            $lastThreeProducts['third_last'] = $orderItem->getData();
        }

        return $lastThreeProducts;
    }

    /**
     * @param $product
     * @param $orderItem
     * @param $key
     * @return mixed
     */
    public function getDataForProductHtml($product, $orderItem, $key, $last_order_products)
    {
        $imageUrl = $this->getImageUrl($product->getImage());
        if ($product->getData('visibility') ==1) {
            $productUrl="Not Visible Individually";
        } else {
            $productUrl = $product->getProductUrl();
        }
        $last_order_products[$key]["image"] = $imageUrl;
        $last_order_products[$key]["name"] = $orderItem->getData('name');
        $last_order_products[$key]["url"] = $productUrl;
        $last_order_products[$key]["price"] = $orderItem->getData('base_row_total_incl_tax');
        $last_order_products[$key]["qty"] = $orderItem->getData('qty_ordered');
        return $last_order_products;
    }

    /**
     * @param $allIds
     * @return array
     */
    public function getOrdersList($allIds)
    {
        $allOrders = $this->sale->create()->addAttributeToSelect('*')
            ->addAttributeToFilter('customer_id', ['in' => $allIds])
            ->setOrder('created_at', 'desc');

        $ordersList = [];
        foreach ($allOrders as $order) {
            $ordersList[$order->getCustomerId()][$order->getId()] = $order;
        }

        return $ordersList;
    }

    /**
     * @param $customerLastOrder
     * @return string
     */
    public function getRecencyDateDiff($customerLastOrder)
    {
        $lastOrderDate = date('Y-m-d', strtotime($customerLastOrder->getCreatedAt()));
        $currentTime = $this->timeZone->formatDateTime(date('Y-m-d H:i:s'));
        $time = date('Y-m-d', strtotime($currentTime));
        $recencyDateDiff = date_diff(date_create($time), date_create($lastOrderDate))
            ->format('%a');
        return $recencyDateDiff;
    }

    /**
     * @param $customerFirstOrder
     * @param $customerLastOrder
     * @param $totalOrders
     * @return float|int
     */
    public function getAvgDays($customerFirstOrder, $customerLastOrder, $totalOrders)
    {
        $firstOrderDate = date('Y-m-d', strtotime($customerFirstOrder->getCreatedAt()));
        $lastOrderDate = date('Y-m-d', strtotime($customerLastOrder->getCreatedAt()));
        $dateDiff = date_diff(date_create($lastOrderDate), date_create($firstOrderDate))->format('%a');
        $avgDays = $dateDiff / $totalOrders;
        return $avgDays;
    }

    /**
     * @param $addressLine1
     * @param $address
     * @return mixed
     */
    public function getAddressLine2($addressLine1, $address)
    {
        $addressLines = ($addressLine1 && $address) ? str_replace(
            $addressLine1,
            "",
            $address
        ) : "";
        $addressLine2 = str_replace("\n", " ", $addressLines);
        return $addressLine2;
    }

    /**
     * @param $image
     * @return string
     */
    public function getImageUrl($image)
    {
        $url = $this->store->getStore()->getBaseUrl();
        $imageUrl = $url . "pub/media/catalog/product" . $image;
        return $imageUrl;
    }

    /**
     * @param $keyword
     * @param $value
     * @return int
     */
    public function getRating($keyword, $value)
    {
        $data = $this->hubHelper->getConfigValue(HubHelper::SYSTEM_CONFIG_RFM_FIELDS);
        $rfmRating = json_decode($data, true);

        switch ($keyword) {
            case 'recency':
                if ($value <= $rfmRating['rfm_at_5'][$keyword]) {
                    return 5;
                } elseif ($value >= $rfmRating['from_rfm_4'][$keyword] && $value <= $rfmRating['to_rfm_4'][$keyword]) {
                    return 4;
                } elseif ($value >= $rfmRating['from_rfm_3'][$keyword] && $value <= $rfmRating['to_rfm_3'][$keyword]) {
                    return 3;
                } elseif ($value >= $rfmRating['from_rfm_2'][$keyword] && $value <= $rfmRating['to_rfm_2'][$keyword]) {
                    return 2;
                } else {
                    return 1;
                }
                break;

            case 'frequency':
                if ($value >= $rfmRating['rfm_at_5'][$keyword]) {
                    return 5;
                } elseif ($value >= $rfmRating['from_rfm_4'][$keyword] && $value <= $rfmRating['to_rfm_4'][$keyword]) {
                    return 4;
                } elseif ($value >= $rfmRating['from_rfm_3'][$keyword] && $value <= $rfmRating['to_rfm_3'][$keyword]) {
                    return 3;
                } elseif ($value >= $rfmRating['from_rfm_2'][$keyword] && $value <= $rfmRating['to_rfm_2'][$keyword]) {
                    return 2;
                } else {
                    return 1;
                }
                break;

            case 'monetary':
                if ($value >= $rfmRating['rfm_at_5'][$keyword]) {
                    return 5;
                } elseif ($value >= $rfmRating['from_rfm_4'][$keyword] && $value <= $rfmRating['to_rfm_4'][$keyword]) {
                    return 4;
                } elseif ($value >= $rfmRating['from_rfm_3'][$keyword] && $value <= $rfmRating['to_rfm_3'][$keyword]) {
                    return 3;
                } elseif ($value >= $rfmRating['from_rfm_2'][$keyword] && $value <= $rfmRating['to_rfm_2'][$keyword]) {
                    return 2;
                } else {
                    return 1;
                }
                break;

            default:
                return 0;
        }
    }

    /**
     * @param $productId
     * @return mixed
     */
    public function getProductData($productId)
    {
        if (isset($this->productObject[$productId])) {
            return $this->productObject[$productId];
        } else {
            $this->productObject[$productId] = $this->product->create()->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', $productId)
                ->getFirstItem();
            return $this->productObject[$productId];
        }
    }

    /**
     * @param $last_order_products
     * @param $currencyCode
     * @return string
     */
    public function lastOrderHtml($last_order_products, $currencyCode)
    {
        $currencySymbol = $this->currencyFactory->create()->load($currencyCode)->getCurrencySymbol();
        $products_html = '';
        if (!empty($last_order_products)) {
            $products_html = '<div class="" style="position: relative; left: 0px; top: 0px;" data-slot="separator"><hr>
                           </div><div data-slot="text">
                           <style>
                                table tr td,
                                table tr th {
                                    padding: 5px;
                                }
                            </style>
                           <table style="font-size: 16px; font-family:arial sans-serif; line-height:20px; text-align:left;">
                           <thead>
                                <tr>
                                    <th style="width: 20%;  word-wrap: unset; border: 1px solid #E4E4E4;">Image</th>
                                    <th style="width: 40%;  word-wrap: unset; border: 1px solid #E4E4E4;">Item</th>s
                                    <th style="width: 15%;  word-wrap: unset; border: 1px solid #E4E4E4;">Qty</th>
                                <th style="width: 25%;  word-wrap: unset; border: 1px solid #E4E4E4;">Cost</th></tr>
                           </thead>

            <tbody>';

            foreach ($last_order_products as $single_product) {
                $product_qty = (int)$single_product["qty"];
                $products_html .= '<tr>

                <td style="width: 110px;font-weight: normal;font-size: 10px;word-wrap: unset;border: 1px solid #E4E4E4; ">
                    <span  style="display: block;  ">
                        <img style="width:100%;height: auto;display: inline-block;vertical-align: middle;" src="' . $single_product["image"] . '" />
                    </span>
                </td>

                <td style="width: auto;   border: 1px solid #E4E4E4;">
                    <a href="' . $single_product["url"] . '"><strong>' . $single_product["name"] . '</strong></a>
                </td>

                <td style="width: 100px;   border: 1px solid #E4E4E4;">
                    <strong>' . $product_qty . '</strong>
                </td>

    		<td style="width: 100px; font-size: 14px;line-height: 21px;border: 1px solid #E4E4E4;">
			<span>
                        <bdi><span >'. $currencySymbol .'</span>'. number_format((float)$single_product["price"], 2, '.', '') . '</bdi>
                    </span>
                </td>

            </tr>';
            }

            $products_html .= '</tbody></table></div><div class="" style="position: relative; left: 0px; top: 0px;"
                                data-slot="separator"><hr></div>';
        }
        return $products_html;
    }

    /**
     * Generate SyncData For Product, Deal, Line_Item objects
     * @param $properties
     * @param $value
     * @param $objectId
     * @return array
     */
    public function attributes($properties, $value, $objectId)
    {
        $arr = [];
        foreach ($properties as $property) {
            if ($data = $value->getData($property)) {
                $arr[$property] = $data;
            }
        }
        $synData = [
            "action" => "UPSERT",
            "changedAt" => strtotime(date('Y-m-d H:i:s ', time())) . '000',
            "externalObjectId" => self::PREFIX . $objectId,
            "properties" => $arr
        ];

        return $synData;
    }

    /**
     * Compute Provided Customer Address object
     * @param $customer
     * @return bool|\Magento\Customer\Model\Address|\Magento\Framework\DataObject
     */
    public function address($customer)
    {
        $defaultAddress = false;
        /** @var \Magento\Customer\Model\Customer $customer */
        if ($customer->getDefaultBilling()) {
            $defaultAddress = $customer->getDefaultBillingAddress();
        } else {
            if ($customer->getDefaultShipping()) {
                $defaultAddress = $customer->getDefaultShippingAddress();
            } else {
                $address = $customer->getAddressCollection()
                    ->addFieldToFilter('parent_id', $customer->getId())
                    ->getLastItem();
                /** @var \Magento\Customer\Model\Address $address */
                if ($address && $address->getId()) {
                    $defaultAddress = $address;
                }
            }
        }
        return $defaultAddress;
    }

    /**
     * Compute Customer stage as Lead, Opportunity, Customer
     * @param $customerId
     * @return string
     */
    public function stage($customerId)
    {
        if ($this->sale->create()->addFieldToFilter('customer_id', $customerId)->getSize() > 0) {
            $stage = "customer";
        } elseif ($this->quote->create()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('items_count', ['gt' => 0])->getSize() > 0
        ) {
            $stage = "opportunity";
        } else {
            $stage = "lead";
        }
        return $stage;
    }

    /**
     * @return array|mixed
     */
    public function getDeletedItem()
    {
        $result = [];
        $quoteIds = [];
        if (!$this->isEnabled()) {
            return $result;
        }
        $collection = $this->hubItem->create();
        foreach ($collection as $item) {
            if ($quoteId = $item->getQuoteId()) {
                $quote = $this->quoteRepository->get($quoteId);
                if(($quote->getData('hub_deal_id')) != null){
                    $this->connectionManager->delQuotesProductsHubSpot('crm/v3/objects/deals/'.$quote->getData('hub_deal_id'));
                    $quote->setData('hub_deal_id', null);
                    $this->quoteRepository->save($quote);
                }
            }
        }
        $collection->walk('delete');
        return true;
    }


    /**
     * @return mixed
     */
    public function isEnabled()
    {
        return $this->hubHelper->isModuleEnable();
    }

    /**
     * @return mixed
     */
    public function getAbandonedCartTime()
    {
        return $this->hubHelper->getConfigValue(HubHelper::SYSTEM_CONFIG_ABANDONED_CART_TIME);
    }

    public function getProductIds()
    {
        $connection = $this->resourceConnection->getConnection();
        $productTableName = $this->resourceConnection->getTableName('catalog_product_entity');
        $productSelect = $connection->select()->from(
            $productTableName,
            ['entity_id']
        );

        $productResult = $connection->fetchAll($productSelect);
        $productIds = [];

        if (is_array($productResult) && count($productResult) > 0) {
            $productIds = array_values(array_column($productResult, 'entity_id'));
        }
        return $productIds;
    }

    public function getContactIds()
    {
        $connection = $this->resourceConnection->getConnection();
        $customerTableName = $this->resourceConnection->getTableName('customer_entity');
        $contactSelect = $connection->select()->from(
            $customerTableName,
            ['entity_id']
        );

        $customersResult = $connection->fetchAll($contactSelect);
        $customerIds = [];

        if (is_array($customersResult) && count($customersResult) > 0) {
            $customerIds = array_values(array_column($customersResult, 'entity_id'));
        }

        //  $customers = $this->customer->create()->getAllIds();

        $orderTableName = $this->resourceConnection->getTableName('sales_order');
        $orderSelect = $connection->select()->from(
            $orderTableName,
            ['customer_id']
        );

        $orderResult = $connection->fetchAll($orderSelect);

        $orderCustomerIds = [];

        if (is_array($orderResult) && count($orderResult) > 0) {
            $orderCustomerIds = array_values(array_column($orderResult, 'customer_id'));
        }
        //  $updatedOrders = $this->sale->create()->getColumnValues('customer_id');
        $allIds = array_unique(array_merge($customerIds, $orderCustomerIds));
        return $allIds;
    }

    public function getDealIds()
    {
        $connection = $this->resourceConnection->getConnection();
        $orderTableName = $this->resourceConnection->getTableName('sales_order');
        $orderSelect = $connection->select()->from(
            $orderTableName,
            ['entity_id']
        );

        $orderResult = $connection->fetchAll($orderSelect);

        $orderIds = [];
        $quoteIds = [];

        if (is_array($orderResult) && count($orderResult) > 0) {
            $orderIds = array_values(array_column($orderResult, 'entity_id'));
        }
        if ($this->connectionManager->getHubConfig(ConnectionManager::BULK_EXPORT_INCLUDE_QUOTE)) {
            $quoteTableName = $this->resourceConnection->getTableName('quote');

            $quoteSelect = $connection->select()->from(
                $quoteTableName,
                ['entity_id']
            )->where("`is_active` = 1 AND `items_count` > 0 AND `customer_id` IS NOT NULL ");

            $quoteResult = $connection->fetchAll($quoteSelect);

            if (is_array($quoteResult) && count($quoteResult)) {
                $quoteIds = array_values(array_column($quoteResult, 'entity_id'));
            }
        }

        return ['ORDER' => $orderIds, 'QUOTE' => $quoteIds, 'COUNT' => count($orderIds) + count($quoteIds)];
    }

    public function getLineItemIds(){
        $connection = $this->resourceConnection->getConnection();
        $quoteItemTableName = $this->resourceConnection->getTableName('quote_item');
        if ($this->connectionManager->getHubConfig(ConnectionManager::BULK_EXPORT_INCLUDE_QUOTE)) {
            $quoteItemSelect = $connection->select()->from(
                $quoteItemTableName,
                ['item_id']
            );
        } else {
            $quoteTableName = $this->resourceConnection->getTableName('quote');

            $quoteSelect = $connection->select()->from(
                $quoteTableName,
                ['entity_id']
            )->where("`is_active` = 1 AND `items_count` > 0 AND `customer_id` IS NOT NULL ");

            $quoteItemSelect = $connection->select()->from(
                $quoteItemTableName,
                ['item_id']
            )->where('`quote_id` NOT IN (' . $quoteSelect . ')');
        }
        $quoteItemResult = $connection->fetchAll($quoteItemSelect);
        $quoteItemIds = [];

        if (is_array($quoteItemResult) && count($quoteItemResult) > 0) {
            $quoteItemIds = array_values(array_column($quoteItemResult, 'item_id'));
        }
        return $quoteItemIds;
    }

    /**
     * @param $job
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function syncBulkData($job)
    {
        if (!empty($job) && isset($job['object_type'], $job['id_json']) && !empty($job['id_json'])) {
            $synData = [];
            $objectType = $job['object_type'];
            $ids = json_decode($job['id_json'], true);
            if ($objectType == 'PRODUCT') {
                $synData = $this->getProductDataToSync($ids);
            } elseif ($objectType == 'CONTACT') {
                $synData = $this->setContact($ids);
            } elseif ($objectType == 'DEAL') {
                if (isset($ids['ORDER'])) {
                    $synData =  $this->massExportToHubSpot("DEAL", $ids['ORDER']);
                } elseif ($this->connectionManager->getHubConfig(ConnectionManager::BULK_EXPORT_INCLUDE_QUOTE) && isset($ids['QUOTE'])) {
                    $synData = $this->massExportToHubSpot("QUOTE",$ids['QUOTE']);
                }
            }
        }
        return true;
    }

    public function exportOrderCustomerProductAndLineItem($orderIds = [])  {

        $connection = $this->resourceConnection->getConnection();
        /* Export Customer */
        $orderTable = $this->resourceConnection->getTableName('sales_order');
        $customerIdsSlectionQuery = $connection->select()->from(
            $orderTable,
            ['customer_id']
        )->where("`entity_id` IN (" . implode(', ', $orderIds) . ") AND `customer_id` IS NOT NULL");
        $result = $connection->fetchAll($customerIdsSlectionQuery);

        if (is_array($result) && count($result) > 0) {
            $customerIds = array_values(array_column($result, 'customer_id'));
            $this->massExportToHubSpot('CONTACT', $customerIds);
        }

        /* Export Product */
        $orderItemTable = $this->resourceConnection->getTableName('sales_order_item');
        $productIdsSelectionQuery = $connection->select()->from(
            $orderItemTable,
            ['product_id']
        )->where("`order_id` IN (" . implode(', ', $orderIds) . ")");

        $result = $connection->fetchAll($productIdsSelectionQuery);

        if (is_array($result) && count($result) > 0) {
            $productIds = array_values(array_column($result, 'product_id'));
            $this->massExportToHubSpot('PRODUCT', array_unique($productIds));
        }
        foreach($orderIds as $orderId)  {
            $this->exportDealLineItems('order', 'sales_order_item', [$orderId]);
        }
        return true;
    }

    public function getProductDataToSync($productIds = []){
        $updateProducts = [];
        $products = $this->product->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', ['in' => $productIds])
            ->addMediaGalleryData();

        foreach ($products as $product) {
            $product = $this->dataset->setProductPropertiesData($product);

            if($product->getData('hub_product_id')){
                $data['id'] = $product->getData('hub_product_id');
                $data["properties"] = $this->attributesNew("PRODUCT", $product);
                $updateProducts[] = $data;
            }else{
                $searchData =
                    ['filterGroups' =>[
                        ['filters' =>[
                            ['value' => $product->getSku(),
                                'propertyName' => 'hs_sku',
                                'operator' => 'EQ']
                        ]]
                    ]];
                $getProduct = $this->connectionManager->getObjectFromHubSpot(
                    "POST",
                    'crm/v3/objects/products/search',
                    $searchData
                );
                $response = json_decode($getProduct['response'], true);
                if(isset($response['results'][0]['id']) &&  $response['total'] == 1){
                    $hubProductId = $response['results'][0]['id'];
                    $hubProductProperties = $response['results'][0]['properties'];
                    $data['id'] = $hubProductId;
                    $data["properties"] = $this->attributesNew("PRODUCT", $product);
                    $updateProducts[] = $data;
                    $this->productaction->updateAttributes([$product->getId()], array('hub_product_id' => $hubProductId), 0);
                }else {
                    $createProducts = $this->connectionManager->exportObjectToHubSpot(
                        "POST",
                        0,
                        'crm/v3/objects/products',
                        ["properties" => $this->attributesNew("PRODUCT", $product)]
                    );
                    $response = json_decode($createProducts['response']);
                    if (!isset($response->status)) {
                        $this->productaction->updateAttributes([$product->getId()], array('hub_product_id' => $response->id), 0);
                    }
                }
            }
        }

        $this->updateObjectsToHubSpot('crm/v3/objects/products/batch/update', $updateProducts);
        return true;
    }

    public function updateObjectsToHubSpot($endpoint, $updateObjectData){
        if (!empty($updateObjectData)) {
            $batches = array_chunk($updateObjectData, self::CHUNK_SIZE);
            foreach ($batches as $batch) {
                $this->connectionManager->exportObjectToHubSpot( "POST", 1, $endpoint, $batch);
            }
        }
        return true;
    }

    /**
     * Generate Data For Product, Deal, Line_Item objects
     * @param $properties
     * @param $value
     * @param $objectId
     * @return array
     */

    public function attributesNew($objectType, $objectData){
        $properties = $this->properties->getGroupProperty($objectType);
        $mainArr = [];
        foreach ($properties as $property) {
            if ($data = $objectData->getData($property)) {
                $mainArr[$property] = $objectData->getData($property);
            }
        }
        return $mainArr;
    }

    /**
     * @param array $quoteIds
     * @return bool
     */
    public function exportCustomerProductAndLineItem($quoteIds = []) {
        $connection = $this->resourceConnection->getConnection();
        $quoteTable = $this->resourceConnection->getTableName('quote');
        $quoteItemTable = $this->resourceConnection->getTableName('quote_item');
        /* Export Customer */
        $customerIdsSlectionQuery = $connection->select()->from(
            $quoteTable,
            ['customer_id']
        )->where("`entity_id` IN (" . implode(', ', $quoteIds) . ") AND `customer_id` IS NOT NULL");
        $result = $connection->fetchAll($customerIdsSlectionQuery);
        if (is_array($result) && count($result) > 0) {
            $customerIds = array_values(array_column($result, 'customer_id'));
            $this->massExportToHubSpot('CONTACT', $customerIds);
        }

        /* Export Product */
        $productIdsSelectionQuery = $connection->select()->from(
            $quoteItemTable,
            ['product_id']
        )->where("`quote_id` IN (" . implode(', ', $quoteIds) . ")");
        $result = $connection->fetchAll($productIdsSelectionQuery);
        if (is_array($result) && count($result) > 0) {
            $productIds = array_values(array_column($result, 'product_id'));
            $this->massExportToHubSpot('PRODUCT', array_unique($productIds));
        }

        $quoteItemTableName = $this->resourceConnection->getTableName('quote_item');
        $quoteItemSelect = $connection->select()->from(
            $quoteItemTableName,
            ['item_id']
        )->where("`quote_id` IN (" . implode(', ', $quoteIds) . ")");
        $quoteItemResult = $connection->fetchAll($quoteItemSelect);
        if (is_array($quoteItemResult) && count($quoteItemResult) > 0) {
            $quoteItemIds = array_values(array_column($quoteItemResult, 'item_id'));
            $this->exportDealLineItems('quote', 'quote_item',$quoteItemIds);
        }
        return true;
    }

    public function exportDealLineItems($dealType, $dealItemtable, $quoteItemIds){
        $updateLineItems = [];
        $items = [];
        $quoteItemTableName = $this->resourceConnection->getTableName($dealItemtable);
        $connection = $this->resourceConnection->getConnection();
        if ($dealType == 'quote') {
            $items = $this->quoteItemCollectionFactory->create()->addFieldToFilter('item_id', ['in' => $quoteItemIds]);
        }elseif ($dealType == 'order') {
            $items = $this->OrderInterface->get($quoteItemIds[0])->getAllVisibleItems();
        }
        foreach ($items as $item) {
            if (!$item->isDeleted() && !$item->getParentItemId()) {
                /** Incase product is deleted  */
                try {
                    $prodHubId = $this->productRepository->getById($item->getProductId())->getHubProductId();
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e){
                    $prodHubId = 0;
                    $url = 'fetching product by productId';
                    $response = 'No such entity exist';
                    $param = 'product_id with entity_id ' .$item->getProductId().' , product_sku is '.$item->getSku();
                    $this->connectionManager->createLog('404', $url, $response, 'GET', $param);

                    /** Neglecting that deleted product_id  */
                    continue;
                }
                $item->addData(
                    [
                        'quantity' => $item->getQtyOrdered() ? (int)$item->getQtyOrdered() : (int)$item->getQty(),
                        'hs_sku' =>  $item->getSku(),
                        'hs_product_id' => $prodHubId
                    ]
                );

                if ($item->getData('hub_line_items_id')) {
                    $data['id'] = $item->getData('hub_line_items_id');
                    $data["properties"] = $this->attributesNew("LINE_ITEM", $item);
                    $updateLineItems[] = $data;
                }else{
                    $createLineItems = $this->connectionManager->exportObjectToHubSpot(
                        "POST",
                        0,
                        'crm/v3/objects/line_items',
                        ["properties" => $this->attributesNew("LINE_ITEM", $item)]
                    );
                    $response = json_decode($createLineItems['response']);
                    if (!isset($response->status)) {
                        $item->setHubLineItemsId($response->id)->save();
                    }
                }
            }
        }
        $this->updateObjectsToHubSpot('crm/v3/objects/line_items/batch/update', $updateLineItems);
        return true;
    }

    public function associateCustomerToDeal($deal) {
        if($deal->getCustomerId()){
            $customerHubID = $this->customerModel->load($deal->getCustomerId())->getData('hub_contact_id');
            $this->connectionManager->associateDealtoObject( 'contacts', $deal->getData("hub_deal_id"),$customerHubID, 3 );
        }
        return true;
    }

    /*
    * Get All the Deal Stages from configuration in an array form
    */

    public function getDealStages(){
        $dealStages = json_decode($this->connectionManager->getHubConfig('hub_ecomm_final_mapping'));
        $states = [];
        foreach($dealStages as $dealStage){
            $states[$dealStage->status] = $dealStage->deal_stage;
        }
        return $states;
    }

    /*
    * Get All the Line Items Associated with Deals and Line Items
    * Remove Line Items from Deals
    */

    public function removeLineItemFromQuotes($hub_deal_id){
        $getAssociatedLineItems = $this->connectionManager->qetAssocitedObjects($hub_deal_id, 'line_item');
        if (!empty($getAssociatedLineItems->results)) {
            $removeBatch = [];
            foreach ($getAssociatedLineItems->results as $key) {
                $data = [
                    "fromObjectId" => $hub_deal_id,
                    "toObjectId" =>  $key->id,
                    "category" => "HUBSPOT_DEFINED",
                    "definitionId" => 19
                ];
                $removeBatch[] = $data;
            }
            $this->connectionManager->removeQuotesLineItems($removeBatch);
        }return true;
    }

    /*
     * Get All the Contacts Associated with Deals and Line Items
     * Remove Contacts from Deals
    */

    public function removeContact($hub_deal_id){
        $getAssociatedContact = $this->connectionManager->qetAssocitedObjects($hub_deal_id, 'contact');
        if (!empty($getAssociatedContact->results)) {
            $removeBatch = [];
            foreach ($getAssociatedContact->results as $key) {
                $data = [
                    "fromObjectId" => $hub_deal_id,
                    "toObjectId" =>  $key->id,
                    "category" => "HUBSPOT_DEFINED",
                    "definitionId" => 15
                ];
                $removeBatch[] = $data;
            }
            $this->connectionManager->removeQuotesLineItems($removeBatch);
        }
        return true;
    }

    /*
     * Return Array
    */
    public function setAssociationLineItemToDeal($lineItem, $dealId){
        return [
            "from"  => ["id" => $lineItem],
            "to"    => ["id" => $dealId],
            "types" => [
                [
                    "associationCategory" => "HUBSPOT_DEFINED",
                    "associationTypeId" => 20
                ]
            ]
        ];
    }


    /** Dataset functions started here */

    /**
     * @param $allIds
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setContactPropertiesData($customer, $allIds){
        $country = $this->country->create();
        $ordersList = $this->getOrdersList($allIds);
        $properties = $this->properties->getGroupProperty('CONTACT');
        $addressProperty = ['company', 'phone', 'address', 'city', 'state', 'zip', 'country'];

        /** @var \Magento\Customer\Model\Customer $customer */
        $arr = [];
        $customerLastOrder = [];
        $customerFirstOrder = [];
        $productsBought = [];
        $totalProductsBought = 0;
        $totalProductTypesBought = [];
        $lastOrderStatus = "";
        $categoriesBought = [];
        $lastCategoriesBought = [];
        $skusBought = [];
        $lastSkusBought = [];
        $totalOrderValues = 0;
        $carrierCode = "";
        $trackingNumber = 0;
        $lastOrderShipDate = "";
        $shippingAddress = [];
        $billingAddress = [];
        $lastProductsBought = [];
        $lastTotalProductsBought = 0;
        $last_order_products = [];
        $key = 0;
        $lastThreeProducts['last'] = [];
        $lastThreeProducts['second_last'] = [];
        $lastThreeProducts['third_last'] = [];
        $customerEmail = $customer->getEmail();

        if (isset($ordersList[$customer->getId()])) {
            $totalOrders = count($ordersList[$customer->getId()]);
            $keys = array_keys($ordersList[$customer->getId()]);
            $lastOrderId = $keys[0];
            $customerLastOrder = $ordersList[$customer->getId()][$lastOrderId];
            $firstOrderId = end($keys);
            $customerFirstOrder = $ordersList[$customer->getId()][$firstOrderId];

            foreach ($ordersList[$customer->getId()] as $order) {
                foreach ($order->getAllVisibleItems() as $orderItem) {
                    array_push(
                        $productsBought,
                        $orderItem->getData('name') . "-" . $orderItem->getData('product_id')
                    );
                    $totalProductsBought += $orderItem->getData('qty_ordered');
                    $totalProductTypesBought[$orderItem->getData('product_type')]
                        = $orderItem->getData('product_type');

                    $lastThreeProducts = $this->getThreeLastProducts($orderItem, $lastThreeProducts);

                    $product[$orderItem->getData('product_id')] = $this->getProductData($orderItem
                        ->getData('product_id'));
                    $tempCat = $product[$orderItem->getData('product_id')]->getCategoryIds();
                    if (!empty($tempCat) && is_array($tempCat)) {
                        $categoryId = $tempCat[0];
                        $category = $this->getCategoryName($categoryId);

                        $categoriesBought[$category] = $category;
                    }

                    $sku = $orderItem->getData('sku');
                    $skusBought[$sku] = $sku;

                    if ($order->getId() == $lastOrderId) {
                        $lastTotalProductsBought += $orderItem->getData('qty_ordered');
                        array_push(
                            $lastProductsBought,
                            $orderItem->getData('name') . "-" . $orderItem->getData('product_id')
                        );

                        $productId = $orderItem->getData('product_id');
                        $product[$productId] = $this->getProductData($productId);
                        $tempCat = $product[$productId]->getCategoryIds();
                        if (!empty($tempCat) && is_array($tempCat)) {
                            $categoryId = $tempCat[0];
                            $category = $this->getCategoryName($categoryId);
                            $lastCategoriesBought[$category] = $category;
                        }

                        $sku = $orderItem->getData('sku');
                        $lastSkusBought[$sku] = $sku;
                        $last_order_products = $this->getDataForProductHtml(
                            $product[$productId],
                            $orderItem,
                            $key,
                            $last_order_products
                        );
                        $key++;
                    }
                }

                if ($order->getId() == $lastOrderId) {
                    $break = false;
                    $shipmentCollection = $customerLastOrder->getShipmentsCollection();
                    foreach ($shipmentCollection as $shipment) {
                        $lastOrderShipDate = $shipment->getData('created_at');
                        $tracks = $shipment->getAllTracks();
                        foreach ($tracks as $track) {
                            $trackingNumber = $track->getTrackNumber();
                            $carrierCode = $track->getCarrierCode();
                            $break = true;
                            break;
                        }
                        if ($break) {
                            break;
                        }
                    }
                    $lastOrderStatus = $order->getData('status');
                }
                $totalOrderValues += $order->getData('grand_total');
            }

            if (!empty($lastThreeProducts['last'])) {
                $lastProduct = $this->getProductData($lastThreeProducts['last']['product_id']);
            }
            if (!empty($lastThreeProducts['second_last'])) {
                $secondLastProduct = $this->getProductData($lastThreeProducts['second_last']['product_id']);
            }
            if (!empty($lastThreeProducts['third_last'])) {
                $thirdLastProduct = $this->getProductData($lastThreeProducts['third_last']['product_id']);
            }

            $shippingAddress = $order->getShippingAddress();
            $billingAddress = $order->getBillingAddress();
        }

        if (empty($shippingAddress)) {
            if ($customer->getDefaultShippingAddress()) {
                $shippingAddress = $customer->getDefaultShippingAddress();
            } elseif ($customer->getAddresses()) {
                foreach ($customer->getAddresses() as $address) {
                    $shippingAddress = $address;
                }
            }
        }

        if (empty($billingAddress)) {
            if ($customer->getDefaultBillingAddress()) {
                $billingAddress = $customer->getDefaultBillingAddress();
            } elseif ($customer->getAddresses()) {
                foreach ($customer->getAddresses() as $address) {
                    $billingAddress = $address;
                }
            }
        }
        $address = $this->address($customer);

        foreach ($properties as $propertyKey => $property) {
            if ($address && in_array($propertyKey, $addressProperty)) {
                if ($propertyKey == 'country' && !array_key_exists($property, $arr)) {
                    if ($country = $country->loadByCode($address->getCountryId())->getName()) {
                        $arr[$property] = $country;
                    }
                } else if($propertyKey == 'phone' && !array_key_exists($property, $arr)) {
                    if ($address->getData('telephone') != null) {
                        $arr[$property] = $address->getData('telephone');
                    }
                }else if($propertyKey == 'state' && !array_key_exists( $property, $arr)) {
                    if ($address->getData('region') != null) {
                        $arr[$property] = $address->getData('region');
                    }
                }else if($propertyKey == 'address' && !array_key_exists($property, $arr)) {
                    if ($address->getData('street') != null) {
                        $arr[$property] = $address->getData('street');
                    }
                }
                else if($propertyKey == 'zip' && !array_key_exists($property, $arr)) {
                    if ($address->getData('postcode') != null) {
                        $arr[$property] = $address->getData('postcode');
                    }
                }else {
                    if ($address->getData($propertyKey) && !array_key_exists($property, $arr)) {
                        $arr[$property] = $address->getData($propertyKey);
                    }
                }
            } elseif ($propertyKey == 'lifecyclestage' && !array_key_exists($property, $arr)) {
                $arr[$property] = $this->stage($customer->getId());
            } elseif ($propertyKey == 'customer_group' && !array_key_exists($property, $arr)) {
                if (!empty($customer['group_id'])) {
                    $arr[$property] = $this->groupRepository->getById($customer->getGroupId())->getCode();
                }
            } elseif ($propertyKey == 'email' && !array_key_exists($property, $arr)) {
                if (!empty($customerEmail)) {
                    $arr[$property] = $customerEmail;
                }
            } elseif ($propertyKey == 'newsletter_subscription' && !array_key_exists($property, $arr)) {
                if (!empty($customerEmail)) {
                    $arr[$property] = $this->getNewsLetterSubscription($customerEmail);
                }
            } elseif ($propertyKey == 'shopping_cart_customer_id' && !array_key_exists($property, $arr)) {
                $arr[$property] = (int)$customer->getId();
            } elseif ($propertyKey == 'shipping_address_line_1' && !array_key_exists($property, $arr)) {
                if (!empty($shippingAddress) && isset($shippingAddress->getStreet()[0])) {
                    $arr[$property] = $shippingAddress->getStreet()[0];
                }
            } elseif ($propertyKey == 'shipping_address_line_2' && !array_key_exists($property, $arr)) {
                if (!empty($shippingAddress) && isset($shippingAddress->getStreet()[1])) {
                    $arr[$property] = $shippingAddress->getStreet()[1];
                }
            } elseif ($propertyKey == 'shipping_city' && !array_key_exists($property, $arr)) {
                if (!empty($shippingAddress) && $shippingAddress->getCity()) {
                    $arr[$property] = $shippingAddress->getCity();
                }
            } elseif ($propertyKey == 'shipping_state' && !array_key_exists($property, $arr)) {
                if (!empty($shippingAddress)) {
                    if ($shippingAddress->getRegion()) {
                        $arr[$property] = $shippingAddress->getRegion();
                    } elseif ($shippingAddress->getRegionCode()) {
                        $region = $this->regionFactory->create()
                            ->loadByCode($shippingAddress->getRegionCode(), $shippingAddress->getCountry())
                            ->getName();
                        $arr[$property] = $region;
                    }
                }
            } elseif ($propertyKey == 'shipping_postal_code' && !array_key_exists($property, $arr)) {
                if (!empty($shippingAddress) && $shippingAddress->getPostcode()) {
                    $arr[$property] = $shippingAddress->getPostcode();
                }
            } elseif ($propertyKey == 'shipping_country' && !array_key_exists($property, $arr)) {
                if (!empty($shippingAddress)) {
                    $countryCode = $shippingAddress->getCountry() ?: $shippingAddress->getCountryId();
                    if ($countryCode) {
                        $shippingCountry = $this->country->create()->loadByCode($countryCode)->getName();
                        $arr[$property] = $shippingCountry;
                    }
                }
            } elseif ($propertyKey == 'billing_address_line_1' && !array_key_exists($property, $arr)) {
                if (!empty($billingAddress) && isset($billingAddress->getStreet()[0])) {
                    $arr[$property] = $billingAddress->getStreet()[0];
                }
            } elseif ($propertyKey == 'billing_address_line_2' && !array_key_exists($property, $arr)) {
                if (!empty($billingAddress) && isset($billingAddress->getStreet()[1])) {
                    $arr[$property] = $billingAddress->getStreet()[1];
                }
            } elseif ($propertyKey == 'billing_city' && !array_key_exists($property, $arr)) {
                if (!empty($billingAddress) && $billingAddress->getCity()) {
                    $arr[$property] = $billingAddress->getCity();
                }
            } elseif ($propertyKey == 'billing_state' && !array_key_exists($property, $arr)) {
                if (!empty($billingAddress)) {
                    if ($billingAddress->getRegion()) {
                        $arr[$property] = $billingAddress->getRegion();
                    } elseif ($billingAddress->getRegionCode()) {
                        $region = $this->regionFactory->create()
                            ->loadByCode($billingAddress->getRegionCode(), $billingAddress->getCountry())->getName();
                        $arr[$property] = $region;
                    }
                }
            } elseif ($propertyKey == 'billing_postal_code' && !array_key_exists($property, $arr)) {
                if (!empty($billingAddress) && $billingAddress->getPostcode()) {
                    $arr[$property] = $billingAddress->getPostcode();
                }
            } elseif ($propertyKey == 'billing_country' && !array_key_exists($property, $arr)) {
                if (!empty($billingAddress)) {
                    $countryCode = $billingAddress->getCountry() ?: $billingAddress->getCountryId();
                    if ($countryCode) {
                        $billingCountry = $this->country->create()->loadByCode($countryCode)->getName();
                        $arr[$property] = $billingCountry;
                    }
                }
            } elseif ($propertyKey == 'last_product_bought' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['last'])) {
                    $arr[$property] =
                        $lastThreeProducts['last']['name'] . "-" . $lastThreeProducts['last']['product_id'];
                }
            } elseif ($propertyKey == 'last_product_types_bought' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['last'])) {
                    $arr[$property] = $lastThreeProducts['last']['product_type'];
                }
            } elseif ($propertyKey == 'last_products_bought' && !array_key_exists($property, $arr)) {
                if (!empty($lastProductsBought)) {
                    $arr[$property] = implode(';', $lastProductsBought);
                }
            } elseif ($propertyKey == 'last_total_number_of_products_bought' && !array_key_exists($property, $arr)) {
                if (isset($lastTotalProductsBought) && $lastTotalProductsBought > 0) {
                    $arr[$property] = $lastTotalProductsBought;
                }
            } elseif ($propertyKey == 'products_bought' && !array_key_exists($property, $arr)) {
                if (!empty($productsBought)) {
                    $arr[$property] = implode(';', array_unique($productsBought));
                }
            } elseif ($propertyKey == 'total_number_of_products_bought' && !array_key_exists($property, $arr)) {
                if (isset($totalProductsBought) && $totalProductsBought > 0) {
                    $arr[$property] = $totalProductsBought;
                }
            } elseif ($propertyKey == 'product_types_bought' && !array_key_exists($property, $arr)) {
                if (!empty($totalProductTypesBought)) {
                    $arr[$property] = implode(';', array_values($totalProductTypesBought));
                }
            } elseif ($propertyKey == 'last_products_bought_html' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$customer->getId()]) && isset($last_order_products)) {
                    $productHtml = $this->lastOrderHtml(
                        $last_order_products,
                        $customerLastOrder->getData('base_currency_code')
                    );
                    $arr[$property] = $productHtml;
                }
            } elseif ($propertyKey == 'last_products_bought_product_1_image_url' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['last'])) {
                    $imageUrl = $this->getImageUrl($lastProduct->getImage());
                    $arr[$property] = $imageUrl;
                }
            } elseif ($propertyKey == 'last_products_bought_product_1_name' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['last'])) {
                    $arr[$property] = $lastThreeProducts['last']['name'];
                }
            } elseif ($propertyKey == 'last_products_bought_product_1_price' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['last'])) {
                    $arr[$property] =
                        $lastThreeProducts['last']['base_row_total_incl_tax']-
                        $lastThreeProducts['last']['base_discount_amount'];
                }
            } elseif ($propertyKey == 'last_products_bought_product_1_url' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['last'])) {
                    if ($lastProduct->getData('visibility') ==1) {
                        $lastProductUrl = "Not Visible Individually";
                    } else {
                        $lastProductUrl = $lastProduct->getProductUrl();
                    }

                    $arr[$property] = $lastProductUrl;
                }
            } elseif ($propertyKey == 'last_products_bought_product_2_image_url' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['second_last'])) {
                    $imageUrl = $this->getImageUrl($secondLastProduct->getImage());
                    $arr[$property] = $imageUrl;
                }
            } elseif ($propertyKey == 'last_products_bought_product_2_name' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['second_last'])) {
                    $arr[$property] = $lastThreeProducts['second_last']['name'];
                }
            } elseif ($propertyKey == 'last_products_bought_product_2_price' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['second_last'])) {
                    $arr[$property] =
                        $lastThreeProducts['second_last']['base_row_total_incl_tax']-
                        $lastThreeProducts['second_last']['base_discount_amount'];
                }
            } elseif ($propertyKey == 'last_products_bought_product_2_url' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['second_last'])) {
                    if ($secondLastProduct->getData('visibility') ==1) {
                        $secondProductUrl = "Not Visible Individually";
                    } else {
                        $secondProductUrl = $secondLastProduct->getProductUrl();
                    }

                    $arr[$property] = $secondProductUrl;
                }
            } elseif ($propertyKey == 'last_products_bought_product_3_image_url' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['third_last'])) {
                    $imageUrl = $this->getImageUrl($thirdLastProduct->getImage());
                    $arr[$property] = $imageUrl;
                }
            } elseif ($propertyKey == 'last_products_bought_product_3_name' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['third_last'])) {
                    $arr[$property] = $lastThreeProducts['third_last']['name'];
                }
            } elseif ($propertyKey == 'last_products_bought_product_3_price' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['third_last'])) {
                    $arr[$property] =
                        $lastThreeProducts['third_last']['base_row_total_incl_tax']-
                        $lastThreeProducts['third_last']['base_discount_amount'];
                }
            } elseif ($propertyKey == 'last_products_bought_product_3_url' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['third_last'])) {
                    if ($thirdLastProduct->getData('visibility') ==1) {
                        $thirdProductUrl = "Not Visible Individually";
                    } else {
                        $thirdProductUrl = $thirdLastProduct->getProductUrl();
                    }

                    $arr[$property] = $thirdProductUrl;
                }
            } elseif ($propertyKey == 'last_order_status' && !array_key_exists($property, $arr)) {
                if ($lastOrderStatus != "") {
                    $arr[$property] = $lastOrderStatus;
                }
            } elseif ($propertyKey == 'last_order_fulfillment_status' && !array_key_exists($property, $arr)) {
                if ($lastOrderStatus != "") {
                    $arr[$property] = $lastOrderStatus;
                }
            } elseif ($propertyKey == 'last_order_tracking_number' && !array_key_exists($property, $arr)) {
                if (isset($trackingNumber)) {
                    $arr[$property] = $trackingNumber;
                }
            } elseif ($propertyKey == 'last_order_tracking_url' && !array_key_exists($property, $arr)) {
                if (isset($carrierCode) && $carrierCode != null) {
                    $arr[$property] = $carrierCode;
                }
            } elseif ($propertyKey == 'last_order_shipment_date' && !array_key_exists($property, $arr)) {
                if ($lastOrderShipDate != "") {
                    $arr[$property] = $this->getDateStamp($lastOrderShipDate);
                }
            } elseif ($propertyKey == 'last_order_order_number' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$customer->getId()])) {
                    $arr[$property] = (int)$customerLastOrder->getId();
                }
            } elseif ($propertyKey == 'total_number_of_current_orders' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$customer->getId()])) {
                    $arr[$property] = $totalOrders;
                }
            } elseif ($propertyKey == 'last_categories_bought' && !array_key_exists($property, $arr)) {
                if (!empty($lastCategoriesBought)) {
                    $arr[$property] = implode(';', array_values($lastCategoriesBought));
                }
            } elseif ($propertyKey == 'categories_bought' && !array_key_exists($property, $arr)) {
                if (!empty($categoriesBought)) {
                    $arr[$property] = implode(';', array_values($categoriesBought));
                }
            } elseif ($propertyKey == 'last_skus_bought' && !array_key_exists($property, $arr)) {
                if (!empty($lastSkusBought)) {
                    $arr[$property] = implode(';', array_values($lastSkusBought));
                }
            } elseif ($propertyKey == 'skus_bought' && !array_key_exists($property, $arr)) {
                if (!empty($skusBought)) {
                    $arr[$property] = implode(';', array_values($skusBought));
                }
            } elseif ($propertyKey == 'total_value_of_orders' && !array_key_exists($property, $arr)) {
                if ($totalOrderValues > 0) {
                    $arr[$property] = $totalOrderValues;
                }
            } elseif ($propertyKey == 'average_order_value' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$customer->getId()]) && $totalOrderValues > 0) {
                    $arr[$property] = $totalOrderValues / $totalOrders;
                }
            } elseif ($propertyKey == 'total_number_of_orders' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$customer->getId()])) {
                    $arr[$property] = $totalOrders;
                }
            } elseif ($propertyKey == 'first_order_value' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$customer->getId()])) {
                    $arr[$property] = (float)$customerFirstOrder->getData('grand_total');
                }
            } elseif ($propertyKey == 'first_order_date' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$customer->getId()])) {
                    $arr[$property] = $this->getDateStamp($customerFirstOrder->getCreatedAt());
                }
            } elseif ($propertyKey == 'last_order_value' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$customer->getId()])) {
                    $arr[$property] = (float)$customerLastOrder->getData('grand_total');
                }
            } elseif ($propertyKey == 'last_order_date' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$customer->getId()])) {
                    $arr[$property] = $this->getDateStamp($customerLastOrder->getCreatedAt());
                }
            } elseif ($propertyKey == 'average_days_between_orders' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$customer->getId()])) {
                    $avgDays = $this->getAvgDays($customerFirstOrder, $customerLastOrder, $totalOrders);

                    $arr[$property] = $avgDays;
                }
            } elseif ($propertyKey == 'order_recency_rating' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$customer->getId()])) {
                    $recencyDateDiff = $this->getRecencyDateDiff($customerLastOrder);
                    $recencyRating = $this->getRating('recency', $recencyDateDiff);
                    $arr[$property] = $recencyRating;
                } else {
                    $arr[$property] = 1;
                }
            } elseif ($propertyKey == 'order_frequency_rating' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$customer->getId()])) {
                    $frequencyRating = $this->getRating('frequency', $totalOrders);
                    $arr[$property] = $frequencyRating;
                } else {
                    $arr[$property] = 1;
                }
            } elseif ($propertyKey == 'monetary_rating' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$customer->getId()]) && $totalOrderValues > 0) {
                    $monetaryRating = $this->getRating('monetary', $totalOrderValues);
                    $arr[$property] = $monetaryRating;
                } else {
                    $arr['monetary_rating'] = 1;
                }
            } elseif ($propertyKey == 'account_creation_date' && !array_key_exists($property, $arr)) {
                $arr[$property] = $this->getDateStamp($customer->getCreatedAt());
            } else {
                if ($customer->getData($propertyKey) && !array_key_exists($property, $arr)) {
                    $arr[$property] = $customer->getData($propertyKey);
                }
            }
        }
        return $arr;
    }

    public function setOrderPropertiesData($deal, $dealStages) {

        $properties = $this->properties->getGroupProperty('DEAL');
        $arr =[];
        $pipeLine = $this->connectionManager->getHubConfig('hub_ecomm_pipeline_id');
        if(array_key_exists($deal->getStatus(), $dealStages)) {
            $deal->setdata('dealstage', $dealStages[$deal->getStatus()]);
        }else{
            $deal->setdata('dealstage', $dealStages['canceled']);
        }
        $shipmentIds = implode(
            ', ',
            $this->shipment->create()->addFieldToFilter('order_id', $deal->getId())->getAllIds()
        );

        $dealShippedDate = "";
        foreach ($deal->getShipmentsCollection() as $shipment) {
            $dealShippedDate = $this->getDateStamp($shipment->getCreatedAt());
        }

        $data = [
            "discount_amount" => $deal->getDiscountAmount(),
            "order_number" => $deal->getIncrementId(),
            "tax_amount" => $deal->getTaxAmount(),
            "shipment_ids" => $shipmentIds,
            "dealname" => "order_" . $deal->getId(),
            "pipeline" => $pipeLine,
            "amount" => $deal->getGrandTotal(),
            "order_creation_date" => $this->getDateStamp($deal->getCreatedAt()),
            "order_shipped_date" => $dealShippedDate,
            "contact_email" => $deal->getCustomerEmail(),
            "contact_is_guest" => $deal->getCustomerId() ? "No" : "Yes"
        ];
        $deal->addData($data);
        foreach ($properties as $propertyKey => $property) {
            if ($propertyKey == 'dealname' && !array_key_exists($property, $arr)) {
                $arr[$property] = $deal->getDealname();
            }
            else if ($propertyKey == 'dealstage' && !array_key_exists($property, $arr)) {
                $arr[$property] = $deal->getDealstage();
            }
            else if ($propertyKey == 'amount' && !array_key_exists($property, $arr)) {
                $arr[$property] = $deal->getAmount();
            }
            else if ($propertyKey == 'discount_amount' && !array_key_exists($property, $arr)) {
                $arr[$property] = $deal->getDiscountAmount();
            }
            else if ($propertyKey == 'order_number' && !array_key_exists($property, $arr)) {
                $arr[$property] = $deal->getOrderNumber();
            }
            else if ($propertyKey == 'tax_amount' && !array_key_exists($property, $arr)) {
                $arr[$property] = $deal->getTaxAmount();
            }
            else if ($propertyKey == 'shipment_ids' && !array_key_exists($property, $arr)) {
                $arr[$property] = $deal->getShipmentIds();
            }
            else if ($propertyKey == 'contact_ids' && !array_key_exists($property, $arr)) {
                $arr[$property] = $deal->getContactIds();
            }
            else if ($propertyKey == 'order_creation_date' && !array_key_exists($property, $arr)) {
                $arr[$property] = $deal->getOrderCreationDate();
            }
            else if ($propertyKey == 'order_shipped_date' && !array_key_exists($property, $arr)) {
                $arr[$property] = $deal->getOrderShippedDate();
            }
            else if ($propertyKey == 'contact_is_guest' && !array_key_exists($property, $arr)) {
                $arr[$property] = $deal->getContactIsGuest();
            }
             else if ($propertyKey == 'contact_email' && !array_key_exists($property, $arr)) {
                $arr[$property] = $deal->getContactEmail();
            }
            else{
                if ($deal->getData($propertyKey) && !array_key_exists($property, $arr)) {
                $arr[$property] = $deal->getData($propertyKey);
                }
            }
        }
        return $arr;
    }

    public function setQuotePropetiesData($cart, $dealStages){
        $pipeLine = $this->connectionManager->getHubConfig('hub_ecomm_pipeline_id');
        $abandonedCartUrl = $this->urlHelper->getUrl('checkout/cart', ['_secure' => true]);

        /** @var \Magento\Quote\Model\Quote $cart */
        $data = [
            "dealstage" => $dealStages->checkout_abandoned,//$dealStages['checkout_abandoned'],
            "dealname" => "quote_" . $cart->getId(),
            "pipeline" => $pipeLine ,
            "amount" => $cart->getGrandTotal(),
            "abandoned_cart_url" => $abandonedCartUrl,
            "hs_assoc__contact_ids" => self::PREFIX . $cart->getCustomerId(),
            "contact_email" => $cart->getCustomerEmail(),
            "contact_is_guest" => $cart->getCustomerId() ? "No" : "Yes"
        ];
        return $cart->addData($data);
    }

    public function setProductPropertiesData($product){
        $properties = $this->properties->getGroupProperty('PRODUCT');
        $product->setImage($this->prepareImages($product));
        $image = $this->prepareImages($product);
        $arr = [];
        foreach ($properties as $propertyKey => $property) {
            if ($propertyKey == 'hs_images' && !array_key_exists($property, $arr)) {
                $arr[$property] = $image;
            }
            else if ($propertyKey == 'hs_sku' && !array_key_exists($property, $arr)) {
                $arr[$property] = $product->getSku();
            }
            else if ($propertyKey == 'name' && !array_key_exists($property, $arr)) {
                $arr[$property] = $product->getName();
            }
            else if ($propertyKey == 'price' && !array_key_exists($property, $arr)) {
                $arr[$property] = $product->getPrice();
            }
            else if ($propertyKey == 'description' && !array_key_exists($property, $arr)) {
                $arr[$property] = $product->getDescription();
            }
            else{
                if ($product->getData($propertyKey) && !array_key_exists($property, $arr)) {
                $arr[$property] = $product->getData($propertyKey);
                }
            }
        }
        return $arr;
    }

    public function setGuestUserPropertiesData($deal){
        $hubContactId = '';
        $arr = [];
        $updateCustomer = [];
        $lastOrderShipDate = "";
        $lastOrderStatus = "";
        $totalProductsBought = 0;
        $lastTotalProductsBought = 0;
        $totalOrderValues = 0;
        $productsBought = [];
        $lastProductsBought = [];
        $lastSkusBought = [];
        $last_order_products = [];
        $key = 0;
        $trackingNumber = 0;
        $carrierCode = "";
        $lastThreeProducts['last'] = [];
        $lastThreeProducts['second_last'] = [];
        $lastThreeProducts['third_last'] = [];
        $email = $deal->getCustomerEmail();
        $firstname = $deal->getCustomerFirstname();
        $lastname = $deal->getCustomerLastname();
        $id = $deal->getId();
        $ordersList =  $this->getOrders($email);
        $totalOrders = '';
        $keys = '';
        $lastOrderId = '';
        $customerLastOrder = '';
        $firstOrderId = '';
        $customerFirstOrder = '';
        if(!empty($ordersList)){
            $totalOrders = count($ordersList[$email]);
            $keys = array_keys($ordersList[$email]);
            $lastOrderId = $keys[0];
            $customerLastOrder = $ordersList[$email][$lastOrderId];
            $firstOrderId = end($keys);
            $customerFirstOrder = $ordersList[$email][$firstOrderId];
        }
        $properties = $this->properties->getGroupProperty("CONTACT");
        $shippingAddress = '';
        $billingAddress = '';
        $customerGroupId = '';
        $customerIsGuest = '';
        if(!empty($ordersList)) {
            foreach ($ordersList[$email] as $order) {
                foreach ($order->getAllVisibleItems() as $orderItem) {
                    array_push(
                        $productsBought,
                        $orderItem->getData('name') . "-" . $orderItem->getData('product_id')
                    );
                    $totalProductsBought += $orderItem->getData('qty_ordered');
                    $totalProductTypesBought[$orderItem->getData('product_type')]
                        = $orderItem->getData('product_type');
                    $lastThreeProducts = $this->getThreeLastProducts($orderItem, $lastThreeProducts);
                    $product[$orderItem->getData('product_id')] = $this->getProductData($orderItem
                        ->getData('product_id'));
                    $tempCat = $product[$orderItem->getData('product_id')]->getCategoryIds();
                    if (!empty($tempCat) && is_array($tempCat)) {
                        $categoryId = $tempCat[0];
                        $category = $this->getCategoryName($categoryId);
                        $categoriesBought[$category] = $category;

                        $sku = $orderItem->getData('sku');
                        $skusBought[$sku] = $sku;
                        if ($order->getId() == $lastOrderId) {
                            $lastTotalProductsBought += $orderItem->getData('qty_ordered');
                            array_push(
                                $lastProductsBought,
                                $orderItem->getData('name') . "-" . $orderItem->getData('product_id')
                            );
                            $productId = $orderItem->getData('product_id');
                            $product[$productId] = $this->getProductData($productId);
                            $tempCat = $product[$productId]->getCategoryIds();
                            if (!empty($tempCat) && is_array($tempCat)) {
                                $categoryId = $tempCat[0];
                                $category = $this->getCategoryName($categoryId);
                                $lastCategoriesBought[$category] = $category;
                            }
                            $sku = $orderItem->getData('sku');
                            $lastSkusBought[$sku] = $sku;
                            $last_order_products = $this->getDataForProductHtml(
                                $product[$productId],
                                $orderItem,
                                $key,
                                $last_order_products
                            );
                            $key++;
                        }
                    }
                    if ($order->getId() == $lastOrderId) {
                        $break = false;
                        $shipmentCollection = $customerLastOrder->getData();
                        $lastOrderShipDate = $shipmentCollection['created_at'];
                        $tracksCollection = $customerLastOrder->getTracksCollection();
                        foreach ($tracksCollection->getItems() as $track) {
                            $trackingNumber = $track->getTrackNumber();
                            $carrierCode = $track->getCarrierCode();
                            $break = true;
                            break;
                        }
                        if ($break) {
                            break;
                        }
                        $lastOrderStatus = $customerLastOrder->getData('status');
                    }
                }
                $totalOrderValues += $order->getData('grand_total');
                $shippingAddress = $order->getShippingAddress();
                $billingAddress = $order->getBillingAddress();
                $customerGroupId =  $order->getData('customer_group_id');
                if($order->getData('customer_id') == null){
                    $customerIsGuest = 1;
                }
            }
        }
        if (!empty($lastThreeProducts['last'])) {
            $lastProduct = $this->getProductData($lastThreeProducts['last']['product_id']);
        }
        if (!empty($lastThreeProducts['second_last'])) {
            $secondLastProduct = $this->getProductData($lastThreeProducts['second_last']['product_id']);
        }
        if (!empty($lastThreeProducts['third_last'])) {
            $thirdLastProduct = $this->getProductData($lastThreeProducts['third_last']['product_id']);
        }

        $telephone = '';
        $street = '';
        $region = '';
        $city = '';
        $postcode = '';
        if ($shippingAddress && $shippingAddress->getId()) {
            $telephone = $shippingAddress->getTelephone();
            $street = $shippingAddress->getStreet();
            $street = implode(" ",$street);
            $region = $shippingAddress->getRegion();
            $city = $shippingAddress->getCity();
            $postcode = $shippingAddress->getPostCode();
            if($firstname == null && $lastname == null){
                $firstname = $shippingAddress->getFirstName();
                $lastname = $shippingAddress->getLastName();
            }
        } elseif ($billingAddress && $billingAddress->getId()) {
            $telephone = $billingAddress->getTelephone();
            $street = $billingAddress->getStreet();
            $street = implode(" ",$street);
            $region = $billingAddress->getRegion();
            $city = $billingAddress->getCity();
            $postcode = $billingAddress->getPostCode();
            if($firstname == null && $lastname == null){
                $firstname = $billingAddress->getFirstName();
                $lastname = $billingAddress->getLastName();
            }
        }

        /*   if firstname and lastname is null  */

        if($firstname == null && $lastname == null){
              $strpos = strpos($email,"@");
              $strarr = str_split($email,$strpos);
              $nameData = preg_replace("/[^a-zA-Z]/", "", $strarr[0]);
              $firstname = $nameData;
              $lastname = $nameData;
          }

        foreach ($properties as $propertyKey => $property) {
            if ($propertyKey == 'email' && !array_key_exists($property, $arr)) {
                if ($email) {
                    $arr[$property] = $email;
                }
            } elseif ($propertyKey == 'firstname' && !array_key_exists($property, $arr)) {
                if ($firstname) {
                    $arr[$property] = $firstname;
                }
            } elseif ($propertyKey == 'lastname' && !array_key_exists($property, $arr)) {
                if ($lastname) {
                    $arr[$property] = $lastname;
                }
            } elseif ($propertyKey == 'phone' && !array_key_exists($property, $arr)) {
                if ($telephone) {
                    $arr[$property] = $telephone;
                }
            } elseif ($propertyKey == 'address' && !array_key_exists($property, $arr)) {
                if ($street) {
                    $arr[$property] = $street;
                }
            } elseif ($propertyKey == 'city' && !array_key_exists($property, $arr)) {
                if ($city) {
                    $arr[$property] = $city;
                }
            } elseif ($propertyKey == 'state' && !array_key_exists($property, $arr)) {
                if ($region) {
                    $arr[$property] = $region;
                }
            } elseif ($propertyKey == 'zip' && !array_key_exists($property, $arr)) {
                if ($postcode) {
                    $arr[$property] = $postcode;
                }
            } elseif ($propertyKey == 'country' && !empty($billingAddress) && !array_key_exists($property, $arr)) {
                $arr[$property] = $this->country->create()->loadByCode($billingAddress->getCountryId())->getName();
            } elseif ($propertyKey == 'contact_stage' && !array_key_exists( $property, $arr)) {
                $arr[$property] = $this->stage($email);
            } elseif ($propertyKey == 'customer_group' && !array_key_exists($property, $arr)) {
                if (!empty($customerGroupId)) {
                    $arr[$property] = $this->groupRepository->getById($customerGroupId)->getCode();
                }
                if($customerIsGuest  == 1){
                    $arr[$property] = "Guest Customer";
                }
            } elseif ($propertyKey == 'newsletter_subscription' && !array_key_exists($property, $arr)) {
                if (isset($customer['email'])) {
                    $arr[$property] = $this->getNewsLetterSubscription($customer->getEmail());
                } else{
                    $arr[$property] = $this->getNewsLetterSubscription($email);

                }
            } elseif ($propertyKey == 'shipping_address_line_1' && !array_key_exists($property, $arr)) {
                if ($shippingAddress && $shippingAddress->getId() && isset($shippingAddress->getStreet()[0])) {
                    $arr[$property] = $shippingAddress->getStreet()[0];
                }
            } elseif ($propertyKey == 'shipping_address_line_2' && !array_key_exists($property, $arr)) {
                if (!empty($shippingAddress) && isset($shippingAddress->getStreet()[1])) {
                    $arr[$property] = $shippingAddress->getStreet()[1];
                }
            } elseif ($propertyKey == 'shipping_city' && !array_key_exists($property, $arr)) {
                if ($shippingAddress && $shippingAddress->getId() && $shippingAddress->getCity()) {
                    $arr[$property] = $shippingAddress->getCity();
                }
            } elseif ($propertyKey == 'shipping_state' && !array_key_exists($property, $arr)) {
                if ($shippingAddress && $shippingAddress->getId()) {
                    if ($shippingAddress->getRegion()) {
                        $arr[$property] = $shippingAddress->getRegion();
                    } elseif ($shippingAddress->getRegionCode()) {
                        $region = $this->regionFactory->create()
                            ->loadByCode($shippingAddress->getRegionCode(), $shippingAddress->getCountry())
                            ->getName();
                        $arr[$property] = $region;
                    }
                }
            } elseif ($propertyKey == 'shipping_postal_code' && !array_key_exists($property, $arr)) {
                if ($shippingAddress && $shippingAddress->getId() && $shippingAddress->getPostcode()) {
                    $arr[$property] = $shippingAddress->getPostcode();
                }
            } elseif ($propertyKey == 'shipping_country' && !array_key_exists($property, $arr)) {
                if ($shippingAddress && $shippingAddress->getId()) {
                    $countryCode = $shippingAddress->getCountry() ?: $shippingAddress->getCountryId();
                    if ($countryCode) {
                        $shippingCountry = $this->country->create()->loadByCode($countryCode)->getName();
                        $arr[$property] = $shippingCountry;
                    }
                }
            } elseif ($propertyKey == 'billing_address_line_1' && !array_key_exists($property, $arr)) {
                if (!empty($billingAddress) && isset($billingAddress->getStreet()[0])) {
                    $arr[$property] = $billingAddress->getStreet()[0];
                }
            } elseif ($propertyKey == 'billing_address_line_2' && !array_key_exists($property, $arr)) {
                if (!empty($billingAddress) && isset($billingAddress->getStreet()[1])) {
                    $arr[$property] = $billingAddress->getStreet()[1];
                }
            } elseif ($propertyKey == 'billing_city' && !array_key_exists($property, $arr)) {
                if (!empty($billingAddress) && $billingAddress->getCity()) {
                    $arr[$property] = $billingAddress->getCity();
                }
            } elseif ($propertyKey == 'billing_state' && !array_key_exists($property, $arr)) {
                if (!empty($billingAddress)) {
                    if ($billingAddress->getRegion()) {
                        $arr[$property] = $billingAddress->getRegion();
                    } elseif ($billingAddress->getRegionCode()) {
                        $region = $this->regionFactory->create()
                            ->loadByCode($billingAddress->getRegionCode(), $billingAddress->getCountry())->getName();
                        $arr[$property] = $region;

                    }
                }
            } elseif ($propertyKey == 'billing_postal_code' && !array_key_exists($property, $arr)) {
                if (!empty($billingAddress) && $billingAddress->getPostcode()) {
                    $arr[$property] = $billingAddress->getPostcode();
                }
            } elseif ($propertyKey == 'billing_country' && !array_key_exists($property, $arr)) {
                if (!empty($billingAddress)) {
                    $countryCode = $billingAddress->getCountry() ?: $billingAddress->getCountryId();
                    if ($countryCode) {
                        $billingCountry = $this->country->create()->loadByCode($countryCode)->getName();
                        $arr[$property] = $billingCountry;
                    }
                }
            } elseif ($propertyKey == 'last_product_bought' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['last'])) {
                    $arr[$property] =
                        $lastThreeProducts['last']['name'] . "-" . $lastThreeProducts['last']['product_id'];
                }
            } elseif ($propertyKey == 'last_product_types_bought' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['last'])) {
                    $arr[$property] = $lastThreeProducts['last']['product_type'];
                }
            } elseif ($propertyKey == 'last_products_bought' && !array_key_exists($property, $arr)) {
                if (!empty($lastProductsBought)) {
                    $arr[$property] = implode(';', $lastProductsBought);
                }
            } elseif ($propertyKey == 'last_total_number_of_products_bought' && !array_key_exists($property, $arr)) {
                if (isset($lastTotalProductsBought) && $lastTotalProductsBought > 0) {
                    $arr[$property] = $lastTotalProductsBought;
                }
            } elseif ($propertyKey == 'products_bought' && !array_key_exists($property, $arr)) {
                if (!empty($productsBought)) {
                    $arr[$property] = implode(';', array_unique($productsBought));
                }
            } elseif ($propertyKey == 'total_number_of_products_bought' && !array_key_exists($property, $arr)) {
                if (isset($totalProductsBought) && $totalProductsBought > 0) {
                    $arr[$property] = $totalProductsBought;
                }
            } elseif ($propertyKey == 'product_types_bought' && !array_key_exists($property, $arr)) {
                if (!empty($totalProductTypesBought)) {
                    $arr[$property] = implode(';', array_values($totalProductTypesBought));
                }
            } elseif ($propertyKey == 'last_products_bought_html' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$email]) && isset($last_order_products)) {
                    $productHtml = $this->lastOrderHtml(
                        $last_order_products,
                        $customerLastOrder->getData('base_currency_code')
                    );
                    $arr[$property] = $productHtml;
                }
            } elseif ($propertyKey == 'last_products_bought_product_1_image_url' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['last'])) {
                    $imageUrl = $this->getImageUrl($lastProduct->getImage());
                    $arr[$property] = $imageUrl;
                }
            } elseif ($propertyKey == 'last_products_bought_product_1_name' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['last'])) {
                    $arr[$property] = $lastThreeProducts['last']['name'];
                }
            } elseif ($propertyKey == 'last_products_bought_product_1_price' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['last'])) {
                    $arr[$property] =
                        $lastThreeProducts['last']['base_row_total_incl_tax']-
                        $lastThreeProducts['last']['base_discount_amount'];
                }
            } elseif ($propertyKey == 'last_products_bought_product_1_url' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['last'])) {
                    if ($lastProduct->getData('visibility') ==1) {
                        $lastProductUrl = "Not Visible Individually";
                    } else {
                        $lastProductUrl = $lastProduct->getProductUrl();
                    }
                    $arr[$property] = $lastProductUrl;
                }
            } elseif ($propertyKey == 'last_products_bought_product_2_image_url' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['second_last'])) {
                    $imageUrl = $this->getImageUrl($secondLastProduct->getImage());
                    $arr[$property] = $imageUrl;
                }
            } elseif ($propertyKey == 'last_products_bought_product_2_name' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['second_last'])) {
                    $arr[$property] = $lastThreeProducts['second_last']['name'];
                }
            } elseif ($propertyKey == 'last_products_bought_product_2_price' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['second_last'])) {
                    $arr[$property] =
                        $lastThreeProducts['second_last']['base_row_total_incl_tax']-
                        $lastThreeProducts['second_last']['base_discount_amount'];
                }
            } elseif ($propertyKey == 'last_products_bought_product_2_url' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['second_last'])) {
                    if ($secondLastProduct->getData('visibility') ==1) {
                        $secondProductUrl = "Not Visible Individually";
                    } else {
                        $secondProductUrl = $secondLastProduct->getProductUrl();
                    }
                    $arr[$property] = $secondProductUrl;
                }
            } elseif ($propertyKey == 'last_products_bought_product_3_image_url' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['third_last'])) {
                    $imageUrl = $this->getImageUrl($thirdLastProduct->getImage());
                    $arr[$property] = $imageUrl;
                }
            } elseif ($propertyKey == 'last_products_bought_product_3_name' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['third_last'])) {
                    $arr[$property] = $lastThreeProducts['third_last']['name'];
                }
            } elseif ($propertyKey == 'last_products_bought_product_3_price' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['third_last'])) {
                    $arr[$property] =
                        $lastThreeProducts['third_last']['base_row_total_incl_tax']-
                        $lastThreeProducts['third_last']['base_discount_amount'];
                }
            } elseif ($propertyKey == 'last_products_bought_product_3_url' && !array_key_exists($property, $arr)) {
                if (!empty($lastThreeProducts['third_last'])) {
                    if ($thirdLastProduct->getData('visibility') ==1) {
                        $thirdProductUrl = "Not Visible Individually";
                    } else {
                        $thirdProductUrl = $thirdLastProduct->getProductUrl();
                    }
                    $arr[$property] = $thirdProductUrl;
                }
            } elseif ($propertyKey == 'last_order_status' && !array_key_exists($property, $arr)) {
                if ($lastOrderStatus != "") {
                    $arr[$property] = $lastOrderStatus;
                }
            } elseif ($propertyKey == 'last_order_fulfillment_status' && !array_key_exists($property, $arr)) {
                if ($lastOrderStatus != "") {
                    $arr[$property] = $lastOrderStatus;
                }
            } elseif ($propertyKey == 'last_order_tracking_number' && !array_key_exists($property, $arr)) {
                if (isset($trackingNumber)) {
                    $arr[$property] = $trackingNumber;
                }
            } elseif ($propertyKey == 'last_order_tracking_url' && !array_key_exists($property, $arr)) {
                if (isset($carrierCode)) {
                    $arr[$property] = $carrierCode;
                }
            } elseif ($propertyKey == 'last_order_shipment_date' && !array_key_exists($property, $arr)) {
                if ($lastOrderShipDate != "") {
                    $arr[$property] = $this->getDateStamp($lastOrderShipDate);
                }
            } elseif ($propertyKey == 'last_order_order_number' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$email])) {
                    $arr[$property] = (int)$customerLastOrder->getId();
                }
            } elseif ($propertyKey == 'total_number_of_current_orders' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$email])) {
                    $arr[$property] = $totalOrders;
                }
            } elseif ($propertyKey == 'last_categories_bought' && !array_key_exists($property, $arr)) {
                if (!empty($lastCategoriesBought)) {
                    $arr[$property] = implode(';', array_values($lastCategoriesBought));
                }
            } elseif ($propertyKey == 'categories_bought' && !array_key_exists($property, $arr)) {
                if (!empty($categoriesBought)) {
                    $arr[$property] = implode(';', array_values($categoriesBought));
                }
            } elseif ($propertyKey == 'last_skus_bought' && !array_key_exists($property, $arr)) {
                if (!empty($lastSkusBought)) {
                    $arr[$property] = implode(';', array_values($lastSkusBought));
                }
            } elseif ($propertyKey == 'skus_bought' && !array_key_exists($property, $arr)) {
                if (!empty($skusBought)) {
                    $arr[$property] = implode(';', array_values($skusBought));
                }
            } elseif ($propertyKey == 'total_value_of_orders' && !array_key_exists($property, $arr)) {
                if ($totalOrderValues > 0) {
                    $arr[$property] = $totalOrderValues;
                }
            } elseif ($propertyKey == 'average_order_value' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$email]) && $totalOrderValues > 0) {
                    $arr[$property] = $totalOrderValues / $totalOrders;
                }
            } elseif ($propertyKey == 'total_number_of_orders' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$email])) {
                    $arr[$property] = $totalOrders;
                }
            } elseif ($propertyKey == 'first_order_value' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$email])) {
                    $arr[$property] = (float)$customerFirstOrder->getData('grand_total');
                }
            } elseif ($propertyKey == 'first_order_date' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$email])) {
                    $arr[$property] = $this->getDateStamp($customerFirstOrder->getCreatedAt());
                }
            } elseif ($propertyKey == 'last_order_value' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$email])) {
                    $arr[$property] = (float)$customerLastOrder->getData('grand_total');
                }
            } elseif ($propertyKey == 'last_order_date' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$email])) {
                    $arr[$property] = $this->getDateStamp($customerLastOrder->getCreatedAt());
                }
            } elseif ($propertyKey == 'average_days_between_orders' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$email])) {
                    $avgDays = $this->getAvgDays($customerFirstOrder, $customerLastOrder, $totalOrders);
                    $arr[$property] = $avgDays;
                }
            } elseif ($propertyKey == 'order_recency_rating' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$email])) {
                    $recencyDateDiff = $this->getRecencyDateDiff($customerLastOrder);
                    $recencyRating = $this->getRating('recency', $recencyDateDiff);
                    $arr[$property] = $recencyRating;
                } else {
                    $arr[$property] = 1;
                }
            } elseif ($propertyKey == 'order_frequency_rating' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$email])) {
                    $frequencyRating = $this->getRating('frequency', $totalOrders);
                    $arr[$property] = $frequencyRating;
                } else {
                    $arr[$property] = 1;
                }
            } elseif ($propertyKey == 'monetary_rating' && !array_key_exists($property, $arr)) {
                if (isset($ordersList[$email]) && $totalOrderValues > 0) {
                    $monetaryRating = $this->getRating('monetary', $totalOrderValues);
                    $arr[$property] = $monetaryRating;
                } else {
                    $arr[$property] = 1;
                }
            }
        }
        return $arr;
    }

    public function getOrders($email){
        $allOrders = $this->sale->create()->addAttributeToSelect('*')
            ->addAttributeToFilter('customer_email', $email)
            ->setOrder('created_at', 'desc');

        $ordersList = [];
        foreach ($allOrders as $order) {
            $ordersList[$order->getCustomerEmail()][$order->getId()] = $order;
        }

        return $ordersList;
    }

    public function getDealSearchData($deal){
        $searchData =
            ['filterGroups' =>
                [['filters' =>[
                    ['value' => 'order_'.$deal->getId(),
                        'propertyName' => 'dealname',
                        'operator' => 'EQ']
                ]]],
                [['filters' =>[
                    ['value' => $deal->getIncrementId(),
                        'propertyName' => 'order_number',
                        'operator' => 'EQ']
                ]]],
                [['filters' =>[
                    ['value' => $deal->getIncrementId(),
                        'propertyName' => 'ip__ecomm_bridge__order_number',
                        'operator' => 'EQ']
                ]]]
            ];
        return $searchData;
    }

    public function getQuoteSearchData($cart){
        $searchData =
            ['filterGroups' =>
                [['filters' => [
                    ['value' => "quote_" . $cart->getId(),
                        'propertyName' => 'dealname',
                        'operator' => 'EQ']
                ]]]
            ];
        return $searchData;
    }

    public function getCustomerSearchData($customer){
        $searchData =
            ['filterGroups' =>[
                ['filters' =>[
                    ['value' => $customer->getData('email'),
                        'propertyName' => 'email',
                        'operator' => 'EQ']
                ]]
            ]];
        return $searchData;
    }

    public function getProductSearchData($product){
        $searchData =
            ['filterGroups' =>[
                ['filters' =>[
                    ['value' => $product->getSku(),
                        'propertyName' => 'hs_sku',
                        'operator' => 'EQ']
                ]]
            ]];
        return $searchData;
    }

    public function getGuestUserSearchData($email){
        $searchData =
            ['filterGroups' =>[
                ['filters' =>[
                    ['value' => $email,
                        'propertyName' => 'email',
                        'operator' => 'EQ']
                ]]
            ]];
        return $searchData;
    }
}
