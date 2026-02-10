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

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Mappingsaved extends Action
{
    /**
     * @var \Makewebbetter\HubIntegration\Helper\ConnectionManager
     */
    public $connectionManager;

    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Makewebbetter\HubIntegration\Helper\ConnectionManager $connectionManager
    ) {
        $this->connectionManager = $connectionManager;
        $this->resultPageFactory = $pageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $paramToSave = [];
        foreach($params as $key => $value){
            switch($key){
                case 'isAjax':
                    break;
                case 'query':
                    break;
                case 'form_key':
                    break;
                default: $paramToSave[$key] = $value;
                    break;
            }
        }

        $this->connectionManager->setHubConfig( 'hub_user_magento_deal_mapping', json_encode($paramToSave) );
        $this->userData();
    }
    public function userData(){
        $userData = json_decode($this->connectionManager->getHubConfig( 'hub_user_magento_deal_mapping'), true);
        $pipelinedetails = json_decode($this->connectionManager->getHubConfig( 'hub_all_pipeline_detail'), true);
        $allPipelineIds = json_decode($this->connectionManager->getHubConfig( 'hub_all_pipeline_id'), true);
        $hubUserRawDealStageMappingData = json_decode($this->connectionManager->getHubConfig( 'hub_user_raw_deal_stage_mapped'), true);
        $magentoOrderStatus =  $this->connectionManager->getmagentoOrderStatus();
        $hubUserRawDealStageMapping = [];
        $HubUserFinalMapping = [];
        $hubUserDealStageIds = [];
        $hubUserselectedPipeline = '';
        $hubUserAllRawDataMapped = [];

        foreach($userData as $key => $value){
            if($key == 'selected_pipeline') {
                $hubUserselectedPipeline = $value;
                $hubUserRawDealStageMapping[$hubUserselectedPipeline]['label'] = $value;
            }
            else{
                $HubUserFinalMapping[] = [
                    'status' => $key,
                    'deal_stage' => $value
                ];
                $hubUserDealStageIds[$key] = $value;
                if($allPipelineIds != null){
                    foreach($allPipelineIds as $pkey => $pvalue){
                        if($pvalue == $hubUserselectedPipeline){
                            $hubUserRawDealStageMapping[$hubUserselectedPipeline]['id'] = $pkey;
                        }
                    }
                }
                $hubUserRawDealStageMapping[$hubUserselectedPipeline]['mapped_stages'][$key] = $value;
            }
        }

        foreach($magentoOrderStatus as $statusCode => $statuslabel){
            foreach($userData as $mapedStatusCode => $mappedDealId){
                if($pipelinedetails != null){
                    foreach($pipelinedetails[$hubUserselectedPipeline] as $stageId => $stageLabel){
                        if($statusCode == $mapedStatusCode && $stageId ==  $mappedDealId){
                            $hubUserAllRawDataMapped[$hubUserselectedPipeline][] = [
                                'magento_order_status_label' => $statuslabel,
                                'magento_order_status_code' => $statusCode,
                                'hubspot_deal_stage_id' => $mappedDealId,
                                'hubspot_deal_stage_label' => $stageLabel
                            ];
                        }
                    }
                }
            }
        }

        if(!empty($hubUserRawDealStageMappingData)) {
            foreach ($hubUserRawDealStageMappingData as $key => $value) {
                if ($key == $hubUserselectedPipeline) {
                    $hubUserRawDealStageMappingData[$key] = $hubUserAllRawDataMapped[$hubUserselectedPipeline];
                } else {
                    $hubUserRawDealStageMappingData[$hubUserselectedPipeline] = $hubUserAllRawDataMapped[$hubUserselectedPipeline];
                }
            }
        }else{
            $hubUserRawDealStageMappingData = $hubUserAllRawDataMapped;
        }

        $userSavedPipelineId = '';
        if($allPipelineIds){
            foreach($allPipelineIds as $key => $value){
                if($hubUserselectedPipeline == $value){
                    $userSavedPipelineId = $key;
                }
            }
        }
        $this->connectionManager->setHubConfig( 'hub_user_final_mapping', json_encode($HubUserFinalMapping));
        $this->connectionManager->setHubConfig( 'hub_user_deal_stage_ids', json_encode($hubUserDealStageIds));
        $this->connectionManager->setHubConfig( 'hub_ecomm_deal_stage_ids', json_encode($hubUserDealStageIds));
        $this->connectionManager->setHubConfig( 'hub_user_selected_pipeline', $hubUserselectedPipeline);
        $this->connectionManager->setHubConfig( 'hub_user_raw_deal_stage_mapped', json_encode($hubUserRawDealStageMappingData));
        $this->connectionManager->setHubConfig( 'hub_ecomm_final_mapping', json_encode($HubUserFinalMapping));
        $this->connectionManager->setHubConfig( 'hub_ecomm_pipeline_id', $userSavedPipelineId);

    }
}
