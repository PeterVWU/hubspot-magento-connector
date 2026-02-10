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
use Makewebbetter\HubIntegration\Model\DataSync;
use Makewebbetter\HubIntegration\Helper\ConnectionManager;
use Makewebbetter\HubIntegration\Model\Source\CronTime;
/**
 * Class ExportToHubspot
 * @package Makewebbetter\HubIntegration\Cron
 */
class ExportToHubspot
{

    const CHUNK_SIZE = 50;

    /**
     * @var DataSync
     */
    private $dataSync;

    /**
     * @var ConnectionManager
     */
    private $connectionManager;

    /**
     * @var Helper
     */
    private $helper;
    public $date;


    /**
     * ExportToHubspot constructor.
     * @param DataSync $dataSync
     * @param ConnectionManager $connectionManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date ,
     * @param Helper $helper
     */
    public function __construct(
        DataSync $dataSync,
        ConnectionManager $connectionManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        Helper  $helper
    ) {
        $this->dataSync = $dataSync;
        $this->connectionManager = $connectionManager;
        $this->date = $date;
        $this->helper = $helper;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        if (!$this->helper->isModuleEnable())
            return true;
        try {
            $checkStatus = $this->connectionManager->getLicenseStatusWithTrailFlag();
            $licenseStatusDb = $checkStatus['license_status'];
            $flag = $checkStatus['trail_status'];

            if ($licenseStatusDb === 'ACTIVATED' || $flag == 'Yes') {
                $cronTime = $this->helper->getConfigValue(Helper::SYSTEM_CONFIG_DATA_SYNC_CRON_TIME);
                if ($cronTime == CronTime::ONCE_IN_A_DAY) {
                    $time = 1440 + 20;
                } elseif ($cronTime == CronTime::TWICE_A_DAY) {
                    $time = 720 + 20;
                } elseif ($cronTime == CronTime::FOUR_TIMES_A_DAY) {
                    $time = 360 + 20;
                } elseif ($cronTime == CronTime::EVERY_HOUR) {
                    $time = 60 + 20;
                } elseif ($cronTime == CronTime::EVERY_FIVE_MINUTES) {
                    $time = 5 + 20;
                }
                $this->syncAllData($time);
            }
        } catch (\Exception $e) {
            $this->connectionManager->createLog(
                400,
                'exception',
                [
                    'cron' => 'Data Sync',
                    'message' => $e->getMessage()
                ],
                RequestType::REQUEST_TYPE_EXCEPTION,
                []
            );
        }
        return true;
    }

    /**
     * @param $time
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function syncAllData($time)
    {
        $products = $this->dataSync->getProduct($time);

        $contacts = $this->dataSync->getContact($time);

        $deals = $this->dataSync->getDeal($time);

        $deletedItems = $this->dataSync->getDeletedItem();

    }
}
