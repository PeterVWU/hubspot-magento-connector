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

namespace Makewebbetter\HubIntegration\Cron;

use Makewebbetter\HubIntegration\Helper\Data as Helper;
use Makewebbetter\HubIntegration\Model\Source\RequestType;
use Makewebbetter\HubIntegration\Helper\ConnectionManager;
use Makewebbetter\HubIntegration\Model\DataSync;

/**
 * Class SyncStatus
 * @package Makewebbetter\HubIntegration\Cron
 */
class SyncStatus
{
    const PAGE_SIZE = 30;
    /**
     * @var ConnectionManager
     */
    private $connectionManager;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var DataSync
     */
    private $dataSync;
    public $product;
    public $customer;
    public $deal;
    public $date;

    /**
     * ExportToHubspot constructor.
     * @param DataSync $dataSync
     * @param ConnectionManager $connectionManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date,
     * @param Helper $helper
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productFactory,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $order,
        ConnectionManager $connectionManager,
        DataSync $dataSync,
        Helper  $helper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    ) {
        $this->product = $productFactory;
        $this->customer = $customerFactory;
        $this->deal = $order;
        $this->connectionManager = $connectionManager;
        $this->helper = $helper;
        $this->dataSync = $dataSync;
        $this->date = $date;
    }

    /**
     * @return bool
     */
    public function syncStatus()
    {

        if (!$this->helper->isModuleEnable() && !$this->helper->getConfigValue('hub_integration/hubspot_integration/connection_established'))
            return true;
        try {
            $checkStatus = $this->connectionManager->getLicenseStatusWithTrailFlag();
            $licenseStatusDb = $checkStatus['license_status'];
            $flag = $checkStatus['trail_status'];

            if ($licenseStatusDb === 'ACTIVATED' || $flag == 'Yes') {
                $customers = $this->customer->create()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('hub_contact_id', array('null' => true))
                    ->setCurPage(1)
                    ->setPageSize(self::PAGE_SIZE)
                    ->addFieldToSelect('entity_id');
                $customerArray = array_column($customers->getData(), 'entity_id');
                $this->dataSync->massExportToHubSpot("CONTACT", $customerArray);

                $order = $this->deal->create()
                    ->addFieldToFilter('hub_deal_id', array('null' => true))
                    ->setPageSize(self::PAGE_SIZE)
                    ->setCurPage(1)
                    ->addFieldToSelect('entity_id')
                    ->addFieldToSelect('quote_id');
                $this->dataSync->massExportToHubSpot("DEAL", array_column($order->getData(), 'entity_id'));

                $products = $this->product->create()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('hub_product_id', array('null' => true))
                    ->setCurPage(1)
                    ->setPageSize(self::PAGE_SIZE)
                    ->addFieldToSelect('entity_id');
                $productArray = array_column($products->getData(), 'entity_id');
                $this->dataSync->massExportToHubSpot("PRODUCT", $productArray);

                if (count($customerArray) > 0) {
                    foreach ($customerArray as $key => $custId) {
                        $this->dataSync->syncApi("CONTACT", $custId);
                    }
                }

                if (count($order->getData()) > 0) {
                    foreach ($order->getData() as $key) {
                        $this->dataSync->syncApiDeal($key);
                    }
                }

                if (count($productArray) > 0) {
                    foreach ($productArray as $key => $productId) {
                        $this->dataSync->syncApi("PRODUCT", $productId);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->connectionManager->createLog(
                400,
                'exception',
                [
                    'cron' => 'Sync Api Status',
                    'message' => $e->getMessage()
                ],
                RequestType::REQUEST_TYPE_EXCEPTION,
                []
            );
        }
        return true;
    }
}
