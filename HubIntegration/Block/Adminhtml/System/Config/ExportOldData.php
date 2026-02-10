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
 * @author      Makewebbetter Core Team <connect@mMakewebbetter.com>
 * @copyright   Copyright Makewebbetter (http://Makewebbetter.com/)
 * @license     https://makewebbetter.com/license-agreement.txt
 */

namespace Makewebbetter\HubIntegration\Block\Adminhtml\System\Config;

use Makewebbetter\HubIntegration\Helper\ConnectionManager;
use Makewebbetter\HubIntegration\Model\ResourceModel\HubConfig;
use Makewebbetter\HubIntegration\Model\ResourceModel\HubQueuedItem\Collection;
use Makewebbetter\HubIntegration\Model\ResourceModel\HubQueuedItem\CollectionFactory;
use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\LocalizedException;
use Zend_Db_Expr;

/**
 * Class ExportOldData
 * @package Makewebbetter\HubIntegration\Block\Adminhtml\System\Config
 */
class ExportOldData extends Container
{
    /**
     * @var CollectionFactory
     */
    public $queuedItemCollectionFactory;

    /**
     * @var ConnectionManager
     */
    public $connectionManager;

    /**
     * @var HubConfig
     */
    private $hubConfig;

    /**
     * ExportOldData constructor.
     * @param Context $context
     * @param CollectionFactory $queuedItemCollectionFactory
     * @param ConnectionManager $connectionManager
     * @param HubConfig $hubConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $queuedItemCollectionFactory,
        ConnectionManager $connectionManager,
        HubConfig $hubConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->queuedItemCollectionFactory = $queuedItemCollectionFactory;
        $this->connectionManager = $connectionManager;
        $this->hubConfig = $hubConfig;
        $this->_getAddButtonOptions();
    }

    /**
     *
     */
    public function _getAddButtonOptions()
    {
        if ($this->getConnectionStatus()) {
            $showExportButton = true;
            $exportButtonLabel = 'Export';
            if ($this->getBulkExportStartTime()) {
                if ($this->getBulkExportEndTime()) {
                    $exportButtonLabel = 'Re-Export';
                } else {
                    $showExportButton = false;
                }
            }
            if ($showExportButton) {
                $ExportButtonOptions = [
                    'label' => __($exportButtonLabel),
                    'class' => 'action-primary'
                ];
                $this->buttonList->add('hubspot-export', $ExportButtonOptions);
            }
        }
    }

    /**
     * @return mixed|null
     */
    public function getConnectionStatus()
    {
        return $this->connectionManager->getHubConfig('connection_established');
    }

    /**
     * @return mixed|null
     */
    public function getBulkExportStartTime()
    {
        return $this->connectionManager->getHubConfig(ConnectionManager::BULK_EXPORT_START_TIME);
    }

    /**
     * @return mixed|null
     */
    public function getBulkExportEndTime()
    {
        return $this->connectionManager->getHubConfig(ConnectionManager::BULK_EXPORT_END_TIME);
    }

    /**
     * @return string
     */
    public function getExportUrlWithoutAbandonedCart()
    {
        return $this->getUrl('*/config/exportOldData', ['start' => true]);
    }

    /**
     * @return string
     */
    public function getExportUrlWithAbandonedCart()
    {
        return $this->getUrl('*/config/exportOldData', ['start' => true, 'quote' => true]);
    }

    /**
     * @return array Abandoned Cart
     */
    public function getPercentage()
    {
        $result = ['CONTACT' => ['percent' => 100], 'PRODUCT' => ['percent' => 100],
            'DEAL' => ['percent' => 100]];
        $availableBatches = $this->getAvailableBatches();

        $availableBatches->getSelect()->columns(['batch_id_count' => new Zend_Db_Expr('SUM(batch_id_count)'),
            'total_count' => new Zend_Db_Expr('total_count')])->group('object_type');
        foreach ($availableBatches->getData() as $availBatch) {
            if (isset($availBatch['object_type']) &&
                (isset($availBatch['total_count']) && $availBatch['total_count'] > 0) && 
                isset($availBatch['batch_id_count'])) {
                $percentageValue = (($availBatch['total_count'] - $availBatch['batch_id_count']) /
                        $availBatch['total_count']) * 100;
                $percentageValue = round($percentageValue, 2);
                $result[$availBatch['object_type']] = [
                    'percent' => $percentageValue
                ];
            }
        }
        return $result;
    }

    /**
     * @return Collection
     */
    public function getAvailableBatches()
    {
        $availableBatches = $this->queuedItemCollectionFactory->create()
            ->addFieldToSelect(['batch_id_count', 'total_count', 'object_type']);
        return $availableBatches;
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    public function showProgressBar()
    {
        if ($this->getConnectionStatus()) {
            if ($this->getBulkExportStartTime()) {
                return true;
            }
        }
        return false;
    }
}
