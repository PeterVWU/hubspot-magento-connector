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

namespace Makewebbetter\HubIntegration\Controller\Config;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Selectedpipeline extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Makewebbetter\HubIntegration\Helper\ConnectionManager
     */
    public $connectionManager;

    /**
     * @var PageFactory
     */
    public $resultPageFactory;
    public $resultJsonFactory;
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        \Makewebbetter\HubIntegration\Helper\ConnectionManager $connectionManager,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    )
    {
        $this->connectionManager = $connectionManager;
        $this->resultPageFactory = $pageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $selectedPipeline = $this->getRequest()->getpost('pipeline');
        $magentoOrderStatus = $this->connectionManager->getmagentoOrderStatus();
        $allpipelineDetails = json_decode($this->connectionManager->getHubConfig('hub_all_pipeline_detail'), true);
        $userData = json_decode($this->connectionManager->getHubConfig( 'hub_user_raw_deal_stage_mapped'), true);
        $userStageData = [];
        $defaultStage = [];

        $defaultStageCheck = json_decode($this->connectionManager->getHubConfig( 'hub_ecomm_default_mapping'), true);;

        if(!empty($userData)) {
            foreach ($userData as $key => $value) {
                if ($key == $selectedPipeline)
                    foreach ($value as $skey => $svalue) {
                        $userStageData[$svalue['magento_order_status_code']] = $svalue['hubspot_deal_stage_id'];
                    }
            }
        }
            foreach ($allpipelineDetails as $pkey => $pipeline) {
                foreach ($pipeline as $skey => $stage) {
                    if ($pkey == $selectedPipeline) {
                        $defaultStage[$skey] = $stage;
                    }
                }
            }

        if(!empty($userStageData)){
            $shtml = '<table><tbody><tr><th><label ><h3>Magento Order Status </h3></label></th><th><label ><h3>HubSpot Deal Status </h3></label></th></tr>';
            foreach ($magentoOrderStatus as $code => $mlabel) {
                $shtml = $shtml . '<tr><td><label>' . $mlabel . '</label></td><td><select name="' . $code . '" id="selected_pipeline_stage">';
                foreach ($defaultStage as $id => $hlabel) {
                    $selected = false;
                    foreach($userStageData as $uStatusCode => $uDealId) {
                        if ($code == $uStatusCode && $id == $uDealId) {
                            $shtml = $shtml . '<option selected="' . $hlabel . '" name="' . $id . '" value="' . $id . '">' . $hlabel . '</option>';
                            $selected = true;
                        }
                    }
                    if($selected == false){
                        $shtml = $shtml . '<option name="' . $id . '" value="' . $id . '">' . $hlabel . '</option>';
                    }
                }
                $shtml = $shtml . '</select></td></tr>';
            }
            $shtml = $shtml . '</tbody></table>';
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData([$shtml]);
        }
        else{
            $shtml = '<table><tbody><tr><th><label ><h3>Magento Order Status </h3></label></th><th><label ><h3>HubSpot Deal Status </h3></label></th></tr>';
            foreach ($magentoOrderStatus as $code => $mlabel) {
                $shtml = $shtml . '<tr><td><label>' . $mlabel . '</label></td><td><select name="' . $code . '" id="selected_pipeline_stage">';
                foreach ($defaultStage as $id => $hlabel) {
                    $selected = false;
                    foreach($defaultStageCheck as $uStatusCode => $uDealId) {
                        if ($code == $uStatusCode && $id == $uDealId) {
                            $shtml = $shtml . '<option selected="' . $hlabel . '" name="' . $id . '" value="' . $id . '">' . $hlabel . '</option>';
                            $selected = true;
                        }
                    }
                    if($selected == false){
                        $shtml = $shtml . '<option name="' . $id . '" value="' . $id . '">' . $hlabel . '</option>';
                    }
                }
                $shtml = $shtml . '</select></td></tr>';
            }
            $shtml = $shtml . '</tbody></table>';
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData([$shtml]);
        }
    }
}


