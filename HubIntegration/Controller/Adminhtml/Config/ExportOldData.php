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

namespace Makewebbetter\HubIntegration\Controller\Adminhtml\Config;

use Makewebbetter\HubIntegration\Helper\ConnectionManager;
use Makewebbetter\HubIntegration\Helper\Data as Helper;
use Makewebbetter\HubIntegration\Model\DataSync;
use Makewebbetter\HubIntegration\Model\ResourceModel\HubQueuedItem\CollectionFactory;
use Makewebbetter\HubIntegration\Model\Source\RequestType;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class ExportOldData
 * @package Makewebbetter\HubIntegration\Controller\Adminhtml\Config
 */
class ExportOldData extends Action
{
    const CHUNK_SIZE = 50;

    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Makewebbetter_HubIntegration::export';

    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    /**
     * @var ConnectionManager
     */
    public $connectionManager;

    /**
     * @var DataSync
     */
    public $dataSync;

    /**
     * @var ResourceConnection
     */
    public $resource;

    /**
     * @var CollectionFactory
     */
    public $queuedItemCollectionFactory;

    /**
     * @var DateTime
     */
    public $dateTime;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * ExportOldData constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ConnectionManager $connectionManager
     * @param DataSync $dataSync
     * @param ResourceConnection $resource
     * @param CollectionFactory $queuedItemCollectionFactory
     * @param DateTime $dateTime
     * @param Helper $helper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ConnectionManager $connectionManager,
        DataSync $dataSync,
        ResourceConnection $resource,
        CollectionFactory $queuedItemCollectionFactory,
        DateTime $dateTime,
        Helper $helper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->connectionManager = $connectionManager;
        $this->dataSync = $dataSync;
        $this->resource = $resource;
        $this->queuedItemCollectionFactory = $queuedItemCollectionFactory;
        $this->dateTime = $dateTime;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface|Page
     */
    public function execute()
    {
        if (!$this->helper->isModuleEnable()) {
            $this->messageManager->addWarningMessage(__('Please enable HubSpot Extension.'));
            return $this->resultRedirectFactory->create()->setPath('admin/dashboard/index');
        }
        $exportTitle = 'Export Old Data';
        $startTime = $this->connectionManager->getHubConfig(ConnectionManager::BULK_EXPORT_START_TIME);
        $endTime =  $this->connectionManager->getHubConfig(ConnectionManager::BULK_EXPORT_END_TIME);
        $request = $this->getRequest()->getParam('start');
        $getQueuedItems = $this->queuedItemCollectionFactory->create()->getItems();
        if ($request && empty($getQueuedItems)) {
            $includeQuote = $this->getRequest()->getParam('quote') ? 1 : 0;
            $this->connectionManager->setHubConfig(ConnectionManager::BULK_EXPORT_INCLUDE_QUOTE, $includeQuote);
            $insertMultiple = [];
            try {
                $products = $this->dataSync->getProductIds();
                $totalProductIdsCount = count($products);
                $productsArray = array_chunk($products, self::CHUNK_SIZE);
                foreach ($productsArray as $productBatchIds => $productIds) {
                    $insertMultiple[] = [
                        'object_type' => 'PRODUCT',
                        'batch_id' => $productBatchIds,
                        'batch_id_count' => count($productIds),
                        'total_count' => $totalProductIdsCount,
                        'id_json' => json_encode($productIds)
                    ];
                }
            } catch (\Exception $e) {
                $this->connectionManager->createLog(
                    400,
                    'exception',
                    $e->getMessage(),
                    RequestType::REQUEST_TYPE_EXCEPTION,
                    []
                );
            }

            try {
                $contacts = $this->dataSync->getContactIds();
                $totalConatctsCount = count($contacts);
                $contactsArray = array_chunk($contacts, self::CHUNK_SIZE);
                foreach ($contactsArray as $contactBatchIds => $contactIds) {
                    $insertMultiple[] = [
                        'object_type' => 'CONTACT',
                        'batch_id' => $contactBatchIds,
                        'batch_id_count' => count($contactIds),
                        'total_count' => $totalConatctsCount,
                        'id_json' => json_encode($contactIds)
                    ];
                }
            } catch (\Exception $e) {
                $this->connectionManager->createLog(
                    400,
                    'exception',
                    $e->getMessage(),
                    RequestType::REQUEST_TYPE_EXCEPTION,
                    []
                );
            }

            try {
                $deals = $this->dataSync->getDealIds();
                $totalDealsCount = 0;
                if (isset($deals['COUNT'])) {
                    $totalDealsCount = $deals['COUNT'];
                }
                $dealBatchIds = 0;
                if (isset($deals['ORDER'])) {
                    $dealsArray = array_chunk($deals['ORDER'], self::CHUNK_SIZE);
                    foreach ($dealsArray as $dealBatchIds => $dealIds) {
                        $insertMultiple[] = [
                            'object_type' => 'DEAL',
                            'batch_id' => $dealBatchIds++,
                            'batch_id_count' => count($dealIds),
                            'total_count' => $totalDealsCount,
                            'id_json' => json_encode(['ORDER' => $dealIds])
                        ];
                    }
                }
                if ($includeQuote) {
                    if (isset($deals['QUOTE'])) {
                        $quotesArray = array_chunk($deals['QUOTE'], self::CHUNK_SIZE);
                        foreach ($quotesArray as $quoteBatchIds => $quoteIds) {
                            $insertMultiple[] = [
                                'object_type' => 'DEAL',
                                'batch_id' => $dealBatchIds++,
                                'batch_id_count' => count($quoteIds),
                                'total_count' => $totalDealsCount,
                                'id_json' => json_encode(['QUOTE' => $quoteIds])
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->connectionManager->createLog(
                    400,
                    'exception',
                    $e->getMessage(),
                    RequestType::REQUEST_TYPE_EXCEPTION,
                    []
                );
            }

            try {
                $tableName = $this->resource->getTableName('hub_makewebbetter_queued_items');
                $this->resource->getConnection()->insertMultiple($tableName, $insertMultiple);
            } catch (\Exception $e) {
                $this->connectionManager->createLog(
                    400,
                    'exception',
                    $e->getMessage(),
                    RequestType::REQUEST_TYPE_EXCEPTION,
                    []
                );
            }

            $startTime = $this->dateTime->Date('Y-m-d H:i:s');
            $endTime = 0;
            $this->connectionManager->setHubConfig(ConnectionManager::BULK_EXPORT_START_TIME, $startTime);
            $this->connectionManager->setHubConfig(ConnectionManager::BULK_EXPORT_END_TIME, $endTime);
        }
        $message = '';
        if ($this->connectionManager->getHubConfig('connection_established')) {
            $message = __('Please click on \'Export\' button to start Export Process.');
        }
        if ($startTime) {
            if ($endTime) {
                $exportTitle = 'Re-Export Old Data';
                $message = __('Export started on %1 and completed on %2.', $startTime, $endTime);
            } else {
                $exportTitle = 'Export Status';
                $message = __('Export started on %1', $startTime);
            }
        }
        if ($request && empty($getQueuedItems)) {
            return $this->resultRedirectFactory->create()->setPath('hubintegration/config/exportOldData');
        }
        if ($startTime && !$endTime) {
            $this->messageManager->addNoticeMessage(
                __('Export Process will be done through cron, to see progress please reload the page.')
            );
        }
        if ($startTime && $endTime && $message) {
            $this->messageManager->addSuccessMessage($message);
        } elseif ($message) {
            $this->messageManager->addNoticeMessage($message);
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Makewebbetter_HubIntegration::hubspot');
        $resultPage->getConfig()->getTitle()->prepend(__($exportTitle));
        return $resultPage;
    }
}
