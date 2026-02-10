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
 * @author 		Makewebbetter Core Team <connect@makewebbetter.com>
 * @copyright   Copyright Makewebbetter (http://makewebbetter.com/)
 * @license     https://makewebbetter.com/license-agreement.txt
 */
namespace Makewebbetter\HubIntegration\Model\ResourceModel\Cron\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Makewebbetter\HubIntegration\Model\Source\JobCodes;

/**
 * Class Collection
 * @package Makewebbetter\HubIntegration\Model\ResourceModel\Cron\Grid
 */
class Collection extends SearchResult
{
    /**
     * @var JobCodes
     */
    private $jobCodes;

    /**
     * Initialize dependencies.
     *
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @param JobCodes $jobcodes
     */
    public function __construct(
        JobCodes $jobCodes,
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = 'cron_schedule',
        $resourceModel = \Magento\Cron\Model\ResourceModel\Schedule::class
    ) {
        $this->jobCodes = $jobCodes;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $options = $this->jobCodes->getOptionArray();
        $this->addFieldToFilter('job_code', ['in' => array_keys($options)]);
        return $this;
    }
}
