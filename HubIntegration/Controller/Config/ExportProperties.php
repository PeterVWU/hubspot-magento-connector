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

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Makewebbetter\HubIntegration\Helper\ConnectionManager;
use Makewebbetter\HubIntegration\Helper\Properties;
use Magento\Framework\Controller\Result\JsonFactory;

class ExportProperties extends Action
{
    /**
     * @var ConnectionManager
     */
    public $connectionManager;

    /**
     * @var Properties
     */
    public $properties;

    /**
     * @var JsonFactory
     */
    public $resultJsonFactory;

    /**
     * ExportProperties constructor.
     * @param Context $context
     * @param ConnectionManager $connectionManager
     * @param Properties $properties
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        ConnectionManager $connectionManager,
        Properties $properties,
        JsonFactory $resultJsonFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->connectionManager = $connectionManager;
        $this->properties = $properties;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $response = $this->resultJsonFactory->create();
        $params = $this->getRequest()->getParam('task');

        if ($params == 'group_property') {
            $result = $this->createGroup();
             $response->setData($result);
        } elseif ($params == 'list') {
            $result = $this->createList();
            $response->setData($result);
        } elseif ($params == 'workflow') {
            $result = $this->createWorkFlow();
            $response->setData($result);
        } else {
            $response->setData([
                'success' => false,
                'message' => 'Some problem occured.',
                'data'=> []
            ]);
        }
        return $response;
    }

    /**
     * @return array
     */
    public function createGroup()
    {
        $this->connectionManager->createUpdatePipeline();
        $groups = $this->properties->getContactGroups();
        foreach ($groups as $key => $value) {
            $this->connectionManager->createGroup($value);
            $properties = $this->properties->getContactProperty($value['name']);
            foreach ($properties as $key1 => $value1) {
                $value1['groupName'] = $value['name'];
                $this->connectionManager->createContactProperty($value1);
            }
        }

        $dealGroup = 'dealinformation';
        $dealProperties = $this->properties->getDealProperty($dealGroup);
        foreach ($dealProperties as $key2 => $value2) {
            $value2['groupName'] = $dealGroup;
            $this->connectionManager->createDealProperty($value2);
        }

        return [
            'success' => true,
            'message' => 'Contact Group & Property Created Successfully',
            'data'=> []
        ];
    }

    /**
     * @return array
     */
    private function createList()
    {
        $lists = $this->properties->getAllLists();
        $this->connectionManager->checkAndUpdateContactListIdsInConfig($lists);
        foreach ($lists as $key => $value) {
            $response = $this->connectionManager->createContactList($value);
            if (isset($response['status_code']) && ($response['status_code'] == 200)) {
                $response = isset($response['response']) ? $response['response'] : "";
                if (!empty($response)) {
                    $response = json_decode($response, true);
                    if ($response['listId']) {
                        $path = 'hubspot/lists/'.$value['name'];
                        $this->properties->setUserOption($path, $response['listId']);
                    }
                }
            }
        }
        return [
            'success' => true,
            'message' => 'Contact Lists created Successfully',
            'data'=> []
        ];
    }

    /**
     * @return array
     */

    private function createWorkFlow()
    {
        $workflow = $this->properties->getWorkFlowNames();
        foreach ($workflow as $key => $name) {
            $workFlowDetail = $this->properties->getAllWorkFlows($name);
            $response = $this->connectionManager->createWorkflow($workFlowDetail);
            if (isset($response['status_code']) && ($response['status_code'] == 200)) {
                $response = isset($response['response']) ? $response['response'] : "";
                if (!empty($response)) {
                    $response = json_decode($response, true);
                    if ($response['id']) {
                        $this->properties->setUserOption($key, $response['id']);
                    }
                }
            }
        }
        return [
            'success' => true,
            'message' => 'Work Flows created Successfully',
            'data'=> []
        ];
    }
}
