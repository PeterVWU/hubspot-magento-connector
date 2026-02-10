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

use Makewebbetter\HubIntegration\Model\ResourceModel\HubConfig;
use Makewebbetter\HubIntegration\Helper\Properties;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\App\ObjectManager;
use Makewebbetter\HubIntegration\Helper\ConnectionManager;

class Contactlists extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    /**
     * @var string
     */

    public $_template = 'Makewebbetter_HubIntegration::details/tab/contactlists.phtml';

    /**
     * @var AuthorizationInterface
     */

    private $authorization;

    /**
     * @var Properties
     */
    public $properties;

    /**
     * @var HubConfig
     */
    private $resourceConfig;

    /**
     * @var ConnectionManager
     */
    public $connectionManager;

    /**
     * Contact constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     * @param array $resourceConfig
     * @param array $properties
     * @param $authorization = null
     */

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        HubConfig $resourceConfig,
        Properties $properties,
        ConnectionManager $connectionManager,
        ?AuthorizationInterface $authorization = null,
        array $data = []
    ) {
        $this->authorization = $authorization ?? ObjectManager::getInstance()->get(AuthorizationInterface::class);
        $this->properties = $properties;
        $this->connectionManager = $connectionManager;
        $this->resourceConfig = $resourceConfig;
        parent::__construct($context, $data);
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
        return __('Contact Lists');
    }

    /**
     * @inheritdoc
     */

    public function getTabTitle()
    {
        return __('Contact Lists');
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
     * @return connection stauts
     */

    public function getConnectionStatus()
    {
        return $this->resourceConfig->getConfigValue('hub_integration/hubspot_integration/connection_established');
    }

    /**
     * @return array
     */

    public function createList()
    {
        $lists = $this->properties->getAllLists();
        return $lists;
    }
    public function getHubintegrationVersion(){
        $HubIntegrationVersion = $this->connectionManager->getHubIntegrationModuleVersion();
        return $HubIntegrationVersion;
    }
}
