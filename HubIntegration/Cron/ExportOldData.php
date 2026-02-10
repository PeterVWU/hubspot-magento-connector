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

use Makewebbetter\HubIntegration\Helper\ConnectionManager;
use Makewebbetter\HubIntegration\Helper\Data as Helper;
use Makewebbetter\HubIntegration\Model\DataSync;
use Makewebbetter\HubIntegration\Model\ResourceModel\HubQueuedItem\CollectionFactory;
use Makewebbetter\HubIntegration\Model\Source\RequestType;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Class ExportOldData
 * @package Makewebbetter\HubIntegration\Cron
 */
class ExportOldData
{
    const EXPORT_BATCH = 10;

    /**
     * @var array
     */
    private $objectType = [0 => 'PRODUCT', 1 => 'CONTACT', 2 => 'DEAL'];

    /**
     * @var CollectionFactory
     */
    private $queuedCollectionFactory;

    /**
     * @var DataSync
     */
    private $dataSync;

    /**
     * @var ConnectionManager
     */
    private $connectionManager;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * ExportOldData constructor.
     * @param CollectionFactory $queuedCollectionFactory
     * @param DataSync $dataSync
     * @param ConnectionManager $connectionManager
     * @param DateTime $dateTime
     * @param Helper $helper
     */
    public function __construct(
        CollectionFactory $queuedCollectionFactory,
        DataSync $dataSync,
        ConnectionManager $connectionManager,
        DateTime $dateTime,
        Helper  $helper
    ) {
        $this->dataSync = $dataSync;
        $this->queuedCollectionFactory = $queuedCollectionFactory;
        $this->connectionManager = $connectionManager;
        $this->dateTime = $dateTime;
        $this->helper = $helper;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        try {
            if (!$this->helper->isModuleEnable()) {
                return true;
            }
            $checkStatus = $this->connectionManager->getLicenseStatusWithTrailFlag();
            $licenseStatusDb = $checkStatus['license_status'];
            $flag = $checkStatus['trail_status'];

            if ($licenseStatusDb === 'ACTIVATED' || $flag == 'Yes') {
                $batches = [];
                $batches = $this->retrieveBatches($batches, 0, self::EXPORT_BATCH);
                if (!empty($batches)) {
                    foreach ($batches as $job) {
                        try {
                            $this->dataSync->syncBulkData($job);
                        } catch (\Exception $e) {
                            $this->connectionManager->createLog(
                                400,
                                'exception',
                                [
                                    'cron' => 'Bulk Export',
                                    'message' => $e->getMessage(),
                                    'job' => $job
                                ],
                                RequestType::REQUEST_TYPE_EXCEPTION,
                                []
                            );
                        }
                    }
                }
                $queueBatches = $this->queuedCollectionFactory->create();
                if (!$queueBatches->getSize()) {
                    $endTime = $this->dateTime->Date('Y-m-d H:i:s');
                    $this->connectionManager->setHubConfig(ConnectionManager::BULK_EXPORT_INCLUDE_QUOTE, 1);
                    $this->connectionManager->setHubConfig(ConnectionManager::BULK_EXPORT_END_TIME, $endTime);
                }
            }
        } catch
            (\Exception $e) {
                $this->connectionManager->createLog(
                    400,
                    'exception',
                    [
                        'cron' => 'Bulk Export',
                        'message' => $e->getMessage()
                    ],
                    RequestType::REQUEST_TYPE_EXCEPTION,
                    []
                );
            }
        return true;
    }

    /**
     * @param $batches
     * @param $objectKey
     * @param $count
     * @return array
     */
    private function retrieveBatches($batches, $objectKey, $count)
    {
        $objectTypeSorting = "'" . implode("','", $this->objectType) . "'";
        $queueCollection = $this->queuedCollectionFactory->create()->setCurPage(1)->setPageSize($count)
            ->addFieldToFilter('object_type', ['eq' => $this->objectType[$objectKey]])
            ->setOrder('batch_id', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
        $queueCollection->getSelect()->order(new \Zend_Db_Expr("FIELD(main_table.object_type, " . $objectTypeSorting . ")"));
        if (empty($batches)) {
            $batches = $queueCollection->getData();
        } else {
            $batches = array_merge($batches, $queueCollection->getData());
        }
        $queueCollection->walk('delete');

        if (count($batches) < self::EXPORT_BATCH && $objectKey < 2) {
            $batches = $this->retrieveBatches($batches, ++$objectKey, self::EXPORT_BATCH - count($batches));
        }
        return $batches;
    }
}
