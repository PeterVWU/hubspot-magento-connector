<?php
/**
 * makewebbetter
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
 * @copyright   Copyright makewebbetter (http://makewebbetter.com/)
 * @license     https://makewebbetter.com/license-agreement.txt
 */

namespace Makewebbetter\HubIntegration\Block\Authorize;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Makewebbetter\HubIntegration\Helper\ConnectionManager;
use Makewebbetter\HubIntegration\Model\ResourceModel\HubConfig;


class Mapping extends Template
{
    /**
     * @var ConnectionManager
     */
    public $connectionManager;
    /**
     * @var HubConfig
     */

    public $resourceConfig;

    /**
     * Config constructor.
     * @param ConnectionManager $connectionManager
     * @param HubConfig $resourceConfig
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        ConnectionManager $connectionManager,
        Template\Context $context,
        HubConfig $resourceConfig,
        array $data = []
    ) {
        $this->connectionManager = $connectionManager;
        $this->resourceConfig = $resourceConfig;
        parent::__construct($context, $data);
    }

    /**
     * @return status
     */

    public function getConnectionStatus()
    {
        return $this->resourceConfig->getConfigValue('hub_integration/hubspot_integration/connection_established');
    }
    /**
     * @return array
     */
    function getHubPipelineDetails(){
        $hubPipelineDetails = $this->connectionManager->fetchAllDealPipelines();
        return $hubPipelineDetails;
    }
    function setHubPipelineAndStageDetails()
    {
        $dealPipelinesresponse = $this->getHubPipelineDetails();
        $hubPipelineDetails = [];
        if(!empty($dealPipelinesresponse) && isset($dealPipelinesresponse['results'])){
            $hubPipelineDetails = $dealPipelinesresponse['results'];
        }
        $pipelines = [];
        $pipelinesWithStages = [];
        foreach ($hubPipelineDetails as $pipeline) {
            $pipelines[$pipeline['id']] = $pipeline['label'];
            foreach ($pipeline['stages'] as $key => $stage) {
                $pipelinesWithStages[$pipeline['label']][$stage['id']] = $stage['label'];
            }
        }
        $this->connectionManager->setHubConfig('hub_all_pipeline_detail', json_encode($pipelinesWithStages));
        $this->connectionManager->setHubConfig('hub_all_pipeline_id', json_encode($pipelines));
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function baseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}
