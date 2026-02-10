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

/**
 * Class Delete
 * @package Makewebbetter\HubIntegration\Cron
 */
class Delete
{
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
     * @param Helper $helper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date,
     */
    public function __construct(
        ConnectionManager $connectionManager,
        Helper  $helper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    ) {
        $this->connectionManager = $connectionManager;
        $this->helper = $helper;
        $this->date = $date;
    }

    /**
     * @return bool
     */
    public function deleteErrorLog()
    {
        if (!$this->helper->isModuleEnable())
            return true;
        try {
            $checkStatus = $this->connectionManager->getLicenseStatusWithTrailFlag();
            $licenseStatusDb = $checkStatus['license_status'];
            $flag = $checkStatus['trail_status'];

            if ($licenseStatusDb === 'ACTIVATED' || $flag == 'Yes') {
                $days = $this->helper->getConfigValue(Helper::SYSTEM_CONFIG_DELETE_ERROR_LOG_TIME_IN_DAYS);
                if ($days) {
                    $this->connectionManager->deleteErrorLog($days);
                }
            }
        } catch (\Exception $e) {
            $this->connectionManager->createLog(
                400,
                'exception',
                [
                    'cron' => 'Delete cron',
                    'message' => $e->getMessage()
                ],
                RequestType::REQUEST_TYPE_EXCEPTION,
                []
            );
        }
        return true;
    }
}
