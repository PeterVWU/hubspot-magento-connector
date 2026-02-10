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
use Makewebbetter\HubIntegration\Model\ResourceModel\HubConfig;
use Magento\Framework\AuthorizationInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Backend\Block\Template\Context;
use  Magento\Framework\Module\ResourceInterface;
use Makewebbetter\HubIntegration\Helper\ConnectionManager As Connect;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Framework\App\AreaList as BackendHelper;

class General extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Template
     *
     * @var string
     */

    protected $_template = 'Makewebbetter_HubIntegration::details/tab/general.phtml';

    /**
     * @var Connect
     */
    public $connect;

    /**
     * @var AuthorizationInterface
     */

    private $authorization;

    /**
     * @var ResourceInterface
     */

    private $moduleResource;

    /**
     * @var StoreManager
     */
        public $storeManager;


    private $resourceConfig;
    public $orderCollectionFactory;
    public $productCollectionFactory;
    public $customerCollectionFactory;
    public $backendHelper;


    /**
     * Contact constructor.
     * @param HubConfig $resourceConfig
     * @param array $moduleResource
     * @param Context $context
     * @param array $data
     * @param array $orderCollectionFactory
     * @param array $customerCollectionFactory
     * @param array $productCollectionFactory
     * @param Connect $connect
     */

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        ResourceInterface $moduleResource,
        CollectionFactory $productCollectionFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        HubConfig $resourceConfig,
        Context $context,
        Connect $connect,
        StoreManager $storeManager,
        BackendHelper $backendHelper,
        array $data = [],
        ?AuthorizationInterface $authorization = null
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->moduleResource = $moduleResource;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->authorization = $authorization ?? ObjectManager::getInstance()->get(AuthorizationInterface::class);
        $this->resourceConfig = $resourceConfig;
        $this->connect = $connect;
        $this->storeManager = $storeManager;
        $this->backendHelper = $backendHelper;
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
        return __('General Details');
    }

    /**
     * @inheritdoc
     */

    public function getTabTitle()
    {
        return __('General Details');
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

    public function getHubintegrationVersion(){
        $HubIntegrationVersion = $this->connect->getHubIntegrationModuleVersion();
        return $HubIntegrationVersion;
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

    public function getCustomerStatus($type = ''){
        if ($type == 'all') {
            /** All Registered Customers on Magento **/
            return $this->customerCollectionFactory->create()->getSize();

        }else{
            /** Not Synced Customers **/
            return $this->customerCollectionFactory->create()->addAttributeToFilter('hub_contact_id',array('null' => true))->getSize();

        }
    }

    /**
     * @return array
     */

    public function getOrderStatus($type = ''){
        if ($type == 'all') {
            /** All Orders on Magento **/
            return $this->_orderCollectionFactory->create()->getSize();

        }else{
            /** Not Synced Orders **/
            return $this->_orderCollectionFactory->create()->addAttributeToFilter('hub_deal_id',array('null' => true))->getSize();

        }
    }

    /**
     * @return array
     */

    public function getProductStatus($type = ''){
        if ($type == 'all') {
            /** All Products on Magento **/
            return $this->productCollectionFactory->create()->getSize();
        }else{
            /** Not Synced Products  **/
            return $this->productCollectionFactory->create()->addAttributeToFilter('hub_product_id',array('null' => true))->getSize();
        }
    }
    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function baseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    public function backendUrl()
    {
        $adminPath = $this->backendHelper->getFrontName('adminhtml');
        $adminUrl = $this->storeManager->getStore()->getBaseUrl().$adminPath.'/';
        return $adminUrl;
    }


}
