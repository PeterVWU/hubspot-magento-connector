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

namespace Makewebbetter\HubIntegration\Block\Adminhtml\Details\Details\Tabs;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Makewebbetter\HubIntegration\Helper\ConnectionManager;
use Makewebbetter\HubIntegration\Model\ResourceModel\HubConfig;
use Magento\Framework\AuthorizationInterface;
use Magento\Customer\Model\Customer;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;

class Dashboardmapping extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var ConnectionManager
     */
    public $connectionManager;

    /**
     * Template
     *
     * @var string
     */

    protected $_template = 'Makewebbetter_HubIntegration::details/tab/dashboardmapping.phtml';

     /**
     * @var AuthorizationInterface
     */

    private $authorization;

    /**
     * @var HubConfig
     */

    private $resourceConfig;
    public $orderCollectionFactory;
    public $productCollectionFactory;
    public $customerCollection;
    protected $scopeConfig;
    protected $deploymentConfig;


     /**
     * Contact constructor.
     * @param HubConfig $resourceConfig
     * @param Context $context
     * @param array $data
     * @param array $orderCollectionFactory
     * @param array $customerCollection
     * @param array $productCollectionFactory
     */

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        CollectionFactory $productCollectionFactory,
        ConnectionManager $connectionManager,
        Customer $customerCollection,
        HubConfig $resourceConfig,
        ScopeConfigInterface $scopeConfig,
        DeploymentConfig $deploymentConfig,
        Context $context,
        array $data = [],
        ?AuthorizationInterface $authorization = null
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->connectionManager = $connectionManager;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->customerCollection = $customerCollection;
        $this->resourceConfig = $resourceConfig;
        $this->scopeConfig = $scopeConfig;
        $this->deploymentConfig = $deploymentConfig;
        $this->authorization = $authorization ?? ObjectManager::getInstance()->get(AuthorizationInterface::class);
        parent::__construct($context, $data);
    }

    /**
     * Add block to left container
     *
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @return $this
     */

    public function addLeft(View\Element\AbstractBlock $block)
    {
        return $this->moveBlockToContainer($block, 'left');
    }

    /**
     * Retrieve required options from parent
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();
    }

   /**
     * @inheritdoc
     */

    public function getTabLabel()
    {
        return __('Pipelines And Deal Stages');
    }

    /**
     * @inheritdoc
     */

    public function getTabTitle()
    {
        return __('Pipelines And Deal Stages');
    }

    /**
     * @inheritdoc
     */

    public function canShowTab()
    {
        return true;
    }

    /**
     * @inheritdoc
     */

    public function isHidden()
    {
        return false;
    }

    /**
     * @return status
     */

    public function getConnectionStatus()
    {
        return $this->resourceConfig->getConfigValue('hub_integration/hubspot_integration/connection_established');
    }

    /**
     * @return protal id
     */

    public function getProtalId()
    {
        return $this->resourceConfig->getConfigValue('hub_integration/hubspot_integration/hub_id');
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
    public function getHubintegrationVersion(){
        $HubIntegrationVersion = $this->connectionManager->getHubIntegrationModuleVersion();
        return $HubIntegrationVersion;
    }
     public function getFrontendBaseUrl()
    {
        return rtrim($this->scopeConfig->getValue('web/secure/base_url'), '/');
    }

    public function getAdminBaseUrl()
    {
        $customAdminUrl = $this->scopeConfig->getValue('admin/url/custom');
        if ($customAdminUrl) {
            return $customAdminUrl;
        }
        
        $frontName = $this->deploymentConfig->get('backend/frontName');
        $baseUrl = $this->getFrontendBaseUrl();
        return $baseUrl . '/' . $frontName;
    }
}
