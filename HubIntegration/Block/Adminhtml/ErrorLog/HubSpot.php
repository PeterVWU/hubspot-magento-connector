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
 * @copyright   Copyright Makewebbetter (https://makewebbetter.com/)
 * @license     https://makewebbetter.com/license-agreement.txt
 */

namespace Makewebbetter\HubIntegration\Block\Adminhtml\ErrorLog;

use Magento\Backend\Block\Template\Context;
use Makewebbetter\HubIntegration\Helper\ConnectionManager;
use Makewebbetter\HubIntegration\Model\Source\HubSpotObjectType;
use Makewebbetter\HubIntegration\Model\Source\HubSpotErrorType;

/**
 * Class HubSpot
 * @package Makewebbetter\HubIntegration\Block\Adminhtml\ErrorLog
 */
class HubSpot extends \Magento\Backend\Block\Template
{

    /**
     * @var ConnectionManager
     */
    private $connectionManager;

    /**
     * @var HubSpotErrorType
     */
    private $hubspotErrorType;

    /**
     * @var HubSpotObjectType
     */
    private $hubspotObjectType;

    /**
     * HubSpot constructor.
     * @param Context $context
     * @param ConnectionManager $connectionManager
     * @param HubSpotErrorType $hubSpotErrorType
     * @param HubSpotObjectType $hubSpotObjectType
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConnectionManager $connectionManager,
        HubSpotErrorType $hubSpotErrorType,
        HubSpotObjectType $hubSpotObjectType,
        array $data = []
    ) {
        $this->connectionManager = $connectionManager;
        $this->hubspotErrorType = $hubSpotErrorType;
        $this->hubspotObjectType = $hubSpotObjectType;
        parent::__construct($context);
    }

    /**
     * @return array
     */
    public function getHubSpotErrorType() {
        return $this->hubspotErrorType->getOptionArray();
    }

    /**
     * @return array
     */
    public function getHubSpotObjectType() {
        return $this->hubspotObjectType->getOptionArray();
    }

    /**
     * @param $filterParameters
     * @param $page
     * @param $limit
     * @return mixed|null
     */
    public function getHubSpotErrorLog($filterParameters, $page, $limit) {
        return $this->connectionManager->getSyncErrors($filterParameters, $page, $limit);
    }
}