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
use Makewebbetter\HubIntegration\Model\ResourceModel\HubConfig;
use Magento\Framework\AuthorizationInterface;
use Magento\Customer\Model\Customer;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Backend\Block\Template\Context;

class Notification extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Template
     *
     * @var string
     */

    protected $_template = 'Makewebbetter_HubIntegration::details/tab/notification.phtml';

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
        Customer $customerCollection,
        HubConfig $resourceConfig,
        Context $context,
        array $data = [],
        ?AuthorizationInterface $authorization = null
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->customerCollection = $customerCollection;
        $this->authorization = $authorization ?? ObjectManager::getInstance()->get(AuthorizationInterface::class);
        $this->resourceConfig = $resourceConfig;
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
        return __('Notification Updates');
    }

    /**
     * @inheritdoc
     */

    public function getTabTitle()
    {
        return __('Notification Updates');
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
     * @return FOPRM status
     */

    public function getFormStatus()
    {
        return $this->resourceConfig->getConfigValue('hub_integration/hubspot_integration/formsubscription_status');
    }

    /**
     * @return protal id
     */

    public function getProtalId()
    {
        return $this->resourceConfig->getConfigValue('hub_integration/hubspot_integration/hub_id');
    }

    /**
     * @return string
     */
    public function gethsFormAction()
    {
        return $this->getUrl('*/hsform/index');
    }
}
