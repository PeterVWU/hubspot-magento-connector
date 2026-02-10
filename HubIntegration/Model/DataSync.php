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
use Makewebbetter\HubIntegration\Model\DataSet as DataSet;

use Makewebbetter\HubIntegration\Helper\Feed;
use Makewebbetter\HubIntegration\Controller\Adminhtml\Main\License;
use Makewebbetter\HubIntegration\Model\Source\RequestType;


class DataSync
{
    const PREFIX = 'HUB_';
    const PAGE_SIZE = 400;
    const PIPELINE = "75e28846-ad0d-4be2-a027-5e1da6590b98";
    const CHUNK_SIZE = 50;

    /**
     * @var DataSet
     */
    public $dataSet;

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
     * @var \Magento\Quote\Model\QuoteRepository
     */
    public $quoteRepo;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    public $quoteFactory;

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

    protected $feedHelper;
    protected $license;

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
     * @param DataSet $dataSet
     */
    public function __construct(
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
        HubHelper $hubHelper,
        DataSet $dataSet,
        Feed $feedHelper,
        License $license
    ) {
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
        $this->dataSet = $dataSet;
        $this->feedHelper = $feedHelper;
        $this->license = $license;
    }

    /**
     * Sync Recently updated or created products into hub
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProduct($time)
    {
        if ($time == 0) {
            $dateStart = date('Y-m-d H:i:s', 0);
        } else {
            $dateStart = $this->dateTime->Date('Y-m-d H:i:s', ' - ' . $time . ' minutes');
        }
        $dateEnd = $this->dateTime->Date('Y-m-d H:i:s');
        $products = $this->product->create()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToFilter('updated_at', ['from' => $dateStart, 'to' => $dateEnd])
            ->addMediaGalleryData();

        return $this->getProductDataToSync($products->getData());
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
     * Sync Recently updated or created Customer into hub
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getContact($time)
    {
        $allIds = $this->getAllIds($time);
        return $this->setContact($allIds);
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
        $checkSubscriber = $this->subscriberFactory->create()->loadByEmail($email);
        if ($checkSubscriber->isSubscribed()) {
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
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllIds($time)
    {
        if ($time == 0) {
            $dateStart = date('Y-m-d H:i:s', 0);
        } else {
            $dateStart = $this->dateTime->Date('Y-m-d H:i:s', ' - ' . $time . ' minutes');
        }
        $dateEnd = $this->dateTime->Date('Y-m-d H:i:s');

        $customers = $this->customer->create()
            ->addAttributeToFilter('updated_at', ['from' => $dateStart, 'to' => $dateEnd])->addAttributeToSelect('*');
        $allId = $customers->getAllIds();

        $updatedOrders = $this->sale->create()->addAttributeToSelect('*')
            ->addAttributeToFilter('updated_at', ['from' => $dateStart, 'to' => $dateEnd]);

        $ordersCustomerId = $updatedOrders->getColumnValues('customer_id');

        $allIds = array_merge($allId, $ordersCustomerId);
        return $allIds;
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
        $imageUrl = $url . "media/catalog/product" . $image;
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
                           </div><div data-slot="text"><table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tbody>
            <tr>

                <td style="border-bottom: 1px solid #f4f4f4;" width="225">

                    <strong>Image</strong>

                </td>

                <td style="border-bottom: 1px solid #f4f4f4;">

                    <strong>Item</strong>

                </td>

                <td style="border-bottom: 1px solid #f4f4f4;" width="60">

                   <strong>Qty</strong>

                </td>

                <td style="border-bottom: 1px solid #f4f4f4;" width="60">

                    <strong>Cost</strong>

                </td>
            </tr>';

            foreach ($last_order_products as $single_product) {
                $products_html .= '<tr>

                <td>

                    <img src="' . $single_product["image"] . '" height="100px" width="100px"/>
                </td>

                <td>

                    <a href="' . $single_product["url"] . '"><strong>' . $single_product["name"] . '</strong></a>
                </td>

                <td>

                    <strong>' . $single_product["qty"] . '</strong>
                </td>

                <td>

                   <strong>' . $currencySymbol . $single_product["price"] . '</strong>

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
     * Sync Recently updated or created Deal into hub
     * @return array
     */
    public function getDeal($time)
    {
        $objectType = 'DEAL';
        $synData = [];
        $syncLineItem = [];
        if (!$this->isEnabled()) {
            return $synData;
        }
        if ($time == 0) {
            $dateStart = date('Y-m-d H:i:s', 0);
        } else {
            $dateStart = $this->dateTime->Date('Y-m-d H:i:s', ' - ' . $time . ' minutes');
        }

        $dateEnd = $this->dateTime->Date('Y-m-d H:i:s');
        $deals = $this->sale->create()
            ->addFieldToFilter('updated_at', ['from' => $dateStart, 'to' => $dateEnd]);
        $properties = $this->properties->getGroupProperty($objectType);
        $lineProperty = $this->properties->getGroupProperty('LINE_ITEM');
        $orderId = [];
        foreach ($deals as $deal) {
            $orderId[] = $deal->getId();
        }
        $this->massExportToHubSpot("DEAL", $orderId);

        $abandonedCartTime = $this->getAbandonedCartTime();
        if ($time == 0) {
            $cartDateStart = date('Y-m-d H:i:s', 0);
        } else {
            $addedTime = (int)$time + (int)$abandonedCartTime;
            $cartDateStart = $this->dateTime->Date('Y-m-d H:i:s', '-' . $addedTime . ' minutes');
        }
        $carts = $this->quote->create()
            ->addFieldToFilter('is_active', ['eq' => 1])
            ->addFieldToFilter('items_count', ['gt' => 0])
            ->addFieldToFilter('customer_id', ['notnull' => true])
            ->addFieldToFilter('updated_at', [
                'from' => $cartDateStart,
                'to' => $this->dateTime->Date('Y-m-d H:i:s', '-' . $abandonedCartTime . ' minutes'),
            ]);

        $baseUrl = $this->urlHelper->getUrl('checkout/cart', ['_secure' => true]);
        $abandonedCartId = [];
        foreach ($carts as $cart) {
            $abandonedCartId[] = $cart->getId();
        }

        $this->massExportToHubSpot("QUOTE", $abandonedCartId);
        return true;
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
     * @param $allIds
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setContact($allIds)
    {
        $objectType = 'CONTACT';
        $synData = [];
        if (!$this->isEnabled()) {
            return $synData;
        }

        $checkStatus = $this->connectionManager->getLicenseStatusWithTrailFlag();
        $licenseStatusDb = $checkStatus['license_status'];
        $flag = $checkStatus['trail_status'];

        if ($licenseStatusDb === 'ACTIVATED' || $flag == 'Yes') {
            $updateCustomer = [];
            $arr = [];
            $allCustomers = $this->customer->create()->addAttributeToFilter('entity_id', ['in' => $allIds])->addAttributeToSelect('*');

            foreach ($allCustomers as $customer) {

                $arr = $this->dataSet->setContactPropertiesData($customer, $allIds);
                if ($customer->getData('hub_contact_id')) {
                    $data['id'] = $customer->getData('hub_contact_id');
                    $data["properties"] = $arr;
                    $updateCustomer[] = $data;
                } else {
                    $searchData = $this->dataSet->getCustomerSearchData($customer);

                    $getcustomer = $this->connectionManager->getObjectFromHubSpot(
                        "POST",
                        'crm/v3/objects/contacts/search',
                        $searchData
                    );
                    $response = json_decode($getcustomer['response'], true);
                    if (isset($response['results'][0]['properties']['email']) && $response['total'] == 1) {
                        $hubContactId = $response['results'][0]['id'];
                        $data['id'] = $hubContactId;
                        $data["properties"] = $arr;
                        $updateCustomer[] = $data;
                        $customerUpdate = $this->customerModel->load($customer->getId());
                        $custData = $customerUpdate->getDataModel()
                            ->setCustomAttribute('hub_contact_id', $hubContactId);
                        $customerUpdate->updateData($custData);
                        $this->customerResourceModelFactory->create()->saveAttribute($customerUpdate, 'hub_contact_id');
                    } else {
                        $createcustomer = $this->connectionManager->exportObjectToHubSpot(
                            "POST",
                            0,
                            'crm/v3/objects/contacts',
                            ["properties" => $arr]
                        );
                        $response = json_decode($createcustomer['response']);

                        if (!isset($response->status)) {
                            $customerUpdate = $this->customerModel->load($customer->getId());
                            $custData = $customerUpdate->getDataModel()
                                ->setCustomAttribute('hub_contact_id', $response->id);
                            $customerUpdate->updateData($custData);
                            $customerResourceModelFactory = $this->customerResourceModelFactory->create()->saveAttribute($customerUpdate, 'hub_contact_id');
                        }
                    }
                }
            }

            if (!empty($updateCustomer)) {
              
                $unique = [];
                foreach ($updateCustomer as $item) {
                    $unique[$item['id']] = $item; // overwrites duplicates
                }
                $inputs = array_values($unique);

                $batches = array_chunk($inputs, 10);
                foreach ($batches as $batch) {
                    $this->connectionManager->exportObjectToHubSpot("POST", 1, 'crm/v3/objects/contacts/batch/update', $batch);
                }
            }
        }
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
        // $connection = $this->resourceConnection->getConnection();
        // $productTableName = $this->resourceConnection->getTableName('catalog_product_entity');
        // $productSelect = $connection->select()->from(
        //     $productTableName,
        //     ['entity_id']
        // );

        // $productResult = $connection->fetchAll($productSelect);
        // $productIds = [];

        // if (is_array($productResult) && count($productResult) > 0) {
        //     $productIds = array_values(array_column($productResult, 'entity_id'));
        // }
        // return $productIds;
        // VWU: Skip product sync - only syncing customers
        return [];
    }

    public function getContactIds()
    {
        // VWU: Only sync customers from last year (2025-01-01+)
        $connection = $this->resourceConnection->getConnection();
        $customerTableName = $this->resourceConnection->getTableName('customer_entity');
        // $contactSelect = $connection->select()->from(
        //     $customerTableName,
        //     ['entity_id']
        // );
        $contactSelect = $connection->select()->from(
            $customerTableName,
            ['entity_id']
        )->where('created_at >= ?', '2025-01-01 00:00:00');

        $customersResult = $connection->fetchAll($contactSelect);
        $customerIds = [];

        if (is_array($customersResult) && count($customersResult) > 0) {
            $customerIds = array_values(array_column($customersResult, 'entity_id'));
        }

        //  $customers = $this->customer->create()->getAllIds();

        $orderTableName = $this->resourceConnection->getTableName('sales_order');
        // $orderSelect = $connection->select()->from(
        //     $orderTableName,
        //     ['customer_id']
        // );
        
        // Also include customers who placed orders in the last year
        $orderSelect = $connection->select()->from(
            $orderTableName,
            ['customer_id']
        )->where('created_at >= ?', '2025-01-01 00:00:00');

        $orderResult = $connection->fetchAll($orderSelect);

        $orderCustomerIds = [];

        if (is_array($orderResult) && count($orderResult) > 0) {
            $orderCustomerIds = array_values(array_column($orderResult, 'customer_id'));
        }
        $allIds = array_unique(array_merge($customerIds, $orderCustomerIds));
        return $allIds;
    }

    public function getDealIds()
    {
        // VWU: Skip deal/order sync - only syncing customers
        return ['ORDER' => [], 'QUOTE' => [], 'COUNT' => 0];
        
        // $connection = $this->resourceConnection->getConnection();
        // $orderTableName = $this->resourceConnection->getTableName('sales_order');
        // $orderSelect = $connection->select()->from(
        //     $orderTableName,
        //     ['entity_id']
        // );

        // $orderResult = $connection->fetchAll($orderSelect);

        // $orderIds = [];
        // $quoteIds = [];

        // if (is_array($orderResult) && count($orderResult) > 0) {
        //     $orderIds = array_values(array_column($orderResult, 'entity_id'));
        // }
        // if ($this->connectionManager->getHubConfig(ConnectionManager::BULK_EXPORT_INCLUDE_QUOTE)) {
        //     $quoteTableName = $this->resourceConnection->getTableName('quote');

        //     $quoteSelect = $connection->select()->from(
        //         $quoteTableName,
        //         ['entity_id']
        //     )->where("`is_active` = 1 AND `items_count` > 0 AND `customer_id` IS NOT NULL ");

        //     $quoteResult = $connection->fetchAll($quoteSelect);

        //     if (is_array($quoteResult) && count($quoteResult)) {
        //         $quoteIds = array_values(array_column($quoteResult, 'entity_id'));
        //     }
        // }

        // return ['ORDER' => $orderIds, 'QUOTE' => $quoteIds, 'COUNT' => count($orderIds) + count($quoteIds)];
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

    /**
     * @param array $ids
     * @param $exportType
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function massExportToHubSpot($exportType, $ids = []) {

        $checkStatus = $this->connectionManager->getLicenseStatusWithTrailFlag();
        $licenseStatusDb = $checkStatus['license_status'];
        $flag = $checkStatus['trail_status'];

        if ($licenseStatusDb === 'ACTIVATED' || $flag == 'Yes') {
            if ($this->hubHelper->isModuleEnable()) {
                if (count($ids) > 0 && $exportType) {
                    switch ($exportType) {
                        case "PRODUCT":
                            $products = $this->getProductDataToSync($ids);
                            break;

                        case "CONTACT":
                            $customerSyncData = $this->setContact($ids);
                            break;

                        case "DEAL":
                            $this->exportOrderCustomerProductAndLineItem($ids);
                            $this->setOrder($ids);
                            break;

                        case "QUOTE":
                            $this->exportCustomerProductAndLineItem($ids);
                            $this->setQuote($ids);
                            break;
                    }
                }
                return true;
            }
            return false;
        }
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

        $checkStatus = $this->connectionManager->getLicenseStatusWithTrailFlag();
        $licenseStatusDb = $checkStatus['license_status'];
        $flag = $checkStatus['trail_status'];

        if ($licenseStatusDb === 'ACTIVATED'  || $flag == 'Yes') {
            $updateProducts = [];
            $products = $this->product->create()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', ['in' => $productIds])
                ->addMediaGalleryData();

            foreach ($products as $product) {
                $arr = $this->dataSet->setProductPropertiesData($product);
                if ($product->getData('hub_product_id')) {
                    $data['id'] = $product->getData('hub_product_id');
                    $data["properties"] = $arr;
                    $updateProducts[] = $data;
                } else {
                    $searchData = $this->dataSet->getProductSearchData($product);

                    $getProduct = $this->connectionManager->getObjectFromHubSpot(
                        "POST",
                        'crm/v3/objects/products/search',
                        $searchData
                    );
                    $response = json_decode($getProduct['response'], true);
                    if (isset($response['results'][0]['id']) && $response['total'] == 1) {
                        $hubProductId = $response['results'][0]['id'];
                        $hubProductProperties = $response['results'][0]['properties'];
                        $data['id'] = $hubProductId;
                        $data["properties"] = $arr;
                        $updateProducts[] = $data;
                        $this->productaction->updateAttributes([$product->getId()], array('hub_product_id' => $hubProductId), 0);
                    } else {
                        $createProducts = $this->connectionManager->exportObjectToHubSpot(
                            "POST",
                            0,
                            'crm/v3/objects/products',
                            ["properties" => $arr]
                        );
                        $response = json_decode($createProducts['response']);
                        if (!isset($response->status)) {
                            $this->productaction->updateAttributes([$product->getId()], array('hub_product_id' => $response->id), 0);
                        }
                    }
                }
            }
           
            $unique = [];
            foreach ($updateProducts as $item) {
                $unique[$item['id']] = $item; // overwrites duplicates
            }
            $inputs = array_values($unique);

            $this->updateObjectsToHubSpot('crm/v3/objects/products/batch/update', $inputs);
        }
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

    public function setOrder($ids){
        if (!$this->isEnabled()) {
            return false;
        }
        $checkStatus = $this->connectionManager->getLicenseStatusWithTrailFlag();
        $licenseStatusDb = $checkStatus['license_status'];
        $flag = $checkStatus['trail_status'];

        if ($licenseStatusDb === 'ACTIVATED' || $flag == 'Yes') {
            $updateOrders = [];
            $lineItemIds = [];
            $deals = $this->sale->create()
                ->addFieldToFilter('entity_id', ['in' => $ids]);
            $dealStages = $this->getDealStages();
            $pipeLine = $this->connectionManager->getHubConfig('hub_ecomm_pipeline_id');
            foreach ($deals as $deal) {
                if (($deal->getData("hub_deal_id")) == null) {
                    $quoteData = '';
                    $quoteCheck = $this->quote->create()->addFieldToFilter('entity_id', ['eq' => $deal->getQuoteId()]);
                    if ($quoteCheck->getData() != null && $quoteCheck->getData()[0] != null) {
                        $quoteData = $quoteCheck->getData()[0];
                    }
                    if ($quoteData != null && $quoteData['hub_deal_id'] != null) {
                        $quote = $this->quoteRepository->get($deal->getQuoteId());
                        $this->removeContact($quote->getData("hub_deal_id"));
                        $this->removeLineItemFromQuotes($quote->getData("hub_deal_id"));
                        $this->connectionManager->delQuotesProductsHubSpot('crm/v3/objects/deals/' . $quote->getData('hub_deal_id'));
                        $quote->setData('hub_deal_id', null);
                        $this->quoteRepository->save($quote);
                    }
                }
                $arr = $this->dataSet->setOrderPropertiesData($deal, $dealStages);
                $orderdata = [];
                $customerAssociation = [];
                $customerAssociation = $this->getcustomerAssociation($deal);
                /**Create Order on HubSpot**/
                if ($deal->getData("hub_deal_id")) {
                    $orderdata['id'] = $deal->getData("hub_deal_id");
                    $orderdata["properties"] = $arr;
                    if ($customerAssociation) {
                        $orderdata["associations"][] = $customerAssociation;
                    }
                    $updateOrders[] = $orderdata;
                } else {
                    $searchData = $this->dataSet->getDealSearchData($deal);
                    $getHubOrder = $this->connectionManager->getObjectFromHubSpot(
                        "POST",
                        'crm/v3/objects/deals/search',
                        $searchData
                    );
                    $response = json_decode($getHubOrder['response'], true);
                    if (isset($response['results'][0]['id']) && $response['total'] == 1) {
                        $hubOrderId = $response['results'][0]['id'];
                        $orderdata['id'] = $hubOrderId;
                        $orderdata["properties"] = $arr;
                        if ($customerAssociation) {
                            $orderdata["associations"][] = $customerAssociation;
                        }
                        $updateOrders[] = $orderdata;
                        $this->OrderInterface->get($deal->getId())->setData('hub_deal_id', $hubOrderId)->save();
                        $deal->setData("hub_deal_id", $hubOrderId);
                    } else {
                        $NewOrder = [];
                        $NewOrder["properties"] = $arr;
                        if ($customerAssociation) {
                            $NewOrder["associations"][] = $customerAssociation;
                        }
                        $createOrders = $this->connectionManager->exportObjectToHubSpot(
                            "POST",
                            0,
                            'crm/v3/objects/deals',
                            $NewOrder
                        );
                        $response = json_decode($createOrders['response']);
                        if (!isset($response->status)) {
                            $this->OrderInterface->get($deal->getId())->setData('hub_deal_id', $response->id)->save();
                            /*AssociateCustomer to Deal*/
                            $deal->setData("hub_deal_id", $response->id);
                        }
                    }
                }
                $items = $this->OrderInterface->get($deal->getId())->getAllVisibleItems();
                foreach ($items as $item) {
                    if (!$item->isDeleted() && !$item->getParentItemId() && $item->getData('hub_line_items_id')) {
                        $lineItemIds[] = $this->setAssociationLineItemToDeal($item->getData('hub_line_items_id'), $deal->getData('hub_deal_id'));
                    }
                }
            }

            $unique = [];
            foreach ($updateOrders as $item) {
                $unique[$item['id']] = $item; // overwrites duplicates
            }
            $inputs = array_values($unique);             
            
            $this->updateObjectsToHubSpot('crm/v3/objects/deals/batch/update', $inputs);
            $this->updateObjectsToHubSpot('crm/v4/associations/line_items/deal/batch/create', $lineItemIds);
            return true;
        }
        return false;
    }

    public function getcustomerAssociation($deal){
        $customerAssociation = [];
        $customerHubID = '';
        if($deal->getCustomerId()){
            $customerHubID = $this->customerModel->load(modelId: $deal->getCustomerId())->getData('hub_contact_id');
            $customerEmail = $this->customerModel->load(modelId: $deal->getCustomerId())->getData('email');
            if($customerHubID){
                if($customerEmail == $deal->getContactEmail()){
                        $customerAssociation = [
                            "to"    => ["id" => $customerHubID],
                            "types" => [
                                [
                                    "associationCategory" => "HUBSPOT_DEFINED",
                                    "associationTypeId" => 3
                                ]
                            ]
                        ];
                }
                
            }
        }
        return $customerAssociation;
    }

    public function setQuote($ids) {
        if (!$this->isEnabled()) {
            return false;
        }
        $checkStatus = $this->connectionManager->getLicenseStatusWithTrailFlag();
        $licenseStatusDb = $checkStatus['license_status'];
        $flag = $checkStatus['trail_status'];

        if ($licenseStatusDb === 'ACTIVATED' || $flag == 'Yes') {
            $properties = $this->properties->getGroupProperty('DEAL');
            $carts = $this->quote->create()
                ->addFieldToFilter('is_active', ['eq' => 1])
                ->addFieldToFilter('items_count', ['gt' => 0])
                ->addFieldToFilter('customer_id', ['notnull' => true])
                ->addFieldToFilter('entity_id', [
                    'in' => $ids
                ]);
            $updateQuotes = [];
            $lineItemIds = [];
            $pipeLine = $this->connectionManager->getHubConfig('hub_ecomm_pipeline_id');
            $dealStages = json_decode($this->connectionManager->getHubConfig('hub_ecomm_deal_stage_ids'));
            $abandonedCartUrl = $this->urlHelper->getUrl('checkout/cart', ['_secure' => true]);
            $connection = $this->resourceConnection->getConnection();
            $quoteItemTableName = $this->resourceConnection->getTableName('quote_item');
            foreach ($carts as $cart) {
                $cart = $this->dataSet->setQuotePropetiesData($cart, $dealStages);
                $quotedata = [];
                $customerAssociation = [];
                $customerAssociation = $this->getcustomerAssociation($cart);
                /**Create/Update Order on HubSpot**/
                if ($cart->getData("hub_deal_id")) {
                    $quotedata['id'] = $cart->getData("hub_deal_id");
                    $quotedata["properties"] = $this->attributesNew("DEAL", $cart);
                    if ($customerAssociation) {
                        $quotedata["associations"][] = $customerAssociation;
                    }
                    $updateQuotes[] = $quotedata;
                } else {
                    $searchData = $this->dataSet->getQuoteSearchData($cart);

                    $getHubOrder = $this->connectionManager->getObjectFromHubSpot(
                        "POST",
                        'crm/v3/objects/deals/search',
                        $searchData
                    );
                    $response = json_decode($getHubOrder['response'], true);
                    if (isset($response['results'][0]['id']) && $response['total'] == 1) {
                        $hubDealId = $response['results'][0]['id'];
                        $quotedata['id'] = $hubDealId;
                        $quotedata["properties"] = $this->attributesNew("DEAL", $cart);
                        if ($customerAssociation) {
                            $quotedata["associations"][] = $customerAssociation;
                        }
                        $updateQuotes[] = $quotedata;
                        $updateQuote = $this->quoteRepository->get($cart->getId());
                        $updateQuote->setHubDealId($hubDealId);
                        $this->quoteRepository->save($updateQuote);
                        /*AssociateCustomer to Deal*/
                        $cart->setData(
                            "hub_deal_id",
                            $hubDealId
                        );
                    } else {
                        $newQuote = [];
                        $newQuote["properties"] = $this->attributesNew("DEAL", $cart);
                        if ($customerAssociation) {
                            $newQuote["associations"][] = $customerAssociation;
                        }
                        $createAbandonCart = $this->connectionManager->exportObjectToHubSpot(
                            "POST",
                            0,
                            'crm/v3/objects/deals',
                            $newQuote
                        );
                        $response = json_decode($createAbandonCart['response']);
                        if (!isset($response->status)) {
                            $updateQuote = $this->quoteRepository->get($cart->getId());
                            $updateQuote->setHubDealId($response->id);
                            $this->quoteRepository->save($updateQuote);
                            /*AssociateCustomer to Deal*/
                            $cart->setData(
                                "hub_deal_id",
                                $response->id
                            );
                        }
                    }
                }
                $this->removeLineItemFromQuotes($cart->getData("hub_deal_id"));
                $quoteItemSelect = $connection->select()->from(
                    $quoteItemTableName,
                    ['item_id']
                )->where("`quote_id` IN (" . implode(', ', [$cart->getId()]) . ")");
                $quoteItemResult = $connection->fetchAll($quoteItemSelect);
                if (is_array($quoteItemResult) && count($quoteItemResult) > 0) {
                    $quoteItemIds = array_values(array_column($quoteItemResult, 'item_id'));
                    $items = $this->quoteItemCollectionFactory->create()
                        ->addFieldToFilter('item_id', ['in' => $quoteItemIds]);
                    foreach ($items as $item) {
                        if (!$item->isDeleted() && !$item->getParentItemId() && $item->getData('hub_line_items_id')) {
                            $lineItemIds[] = $this->setAssociationLineItemToDeal($item->getData('hub_line_items_id'), $cart->getData('hub_deal_id'));
                        }
                    }
                }
            }
      
            $unique = [];
            foreach ($updateQuotes as $item) {
                $unique[$item['id']] = $item; // overwrites duplicates
            }
            $inputs = array_values($unique); 

            $this->updateObjectsToHubSpot('crm/v3/objects/deals/batch/update', $inputs);
            $this->updateObjectsToHubSpot('crm/v4/associations/line_items/deal/batch/create', $lineItemIds);
            return true;
        }
        return false;
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
                    "definitionId" => 3
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
}
