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

namespace Makewebbetter\HubIntegration\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template;
use Magento\Framework\UrlInterface;
use Makewebbetter\HubIntegration\Helper\ConnectionManager;
use Makewebbetter\HubIntegration\Helper\Data as HubHelper;
use Makewebbetter\HubIntegration\Model\ResourceModel\HubConfig;

/**
 * Class AuthorizeButton
 * @package Makewebbetter\HubIntegration\Block\Adminhtml\System\Config
 */
class AuthorizeButton extends Field
{
    const REDIRECT_URI = 'hubintegration/config/getcode';
    const REDIRECT_URI_NEW = 'https://auth.makewebbetter.com/integration/hubspot-auth/';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ConnectionManager
     */
    private $connectionManager;

    /**
     * @var HubConfig
     */
    private $resourceConfig;
    /**
     * @var HubHelper
     */
    public $hubHelper;

    public $date;

    /**
     * AuthorizeButton constructor.
     * @param Template\Context $context
     * @param ConnectionManager $connectionManager
     * @param HubConfig $resourceConfig
     * @param array $data
     * @param \Makewebbetter\HubIntegration\Helper\Data $hubHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date,
     */
    public function __construct(
        Template\Context $context,
        UrlInterface $urlBuilder,
        ConnectionManager $connectionManager,
        HubConfig $resourceConfig,
        HubHelper $hubHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        array $data = []

    ) {
        $this->urlBuilder = $urlBuilder;
        $this->connectionManager = $connectionManager;
        $this->resourceConfig = $resourceConfig;
        $this->hubHelper = $hubHelper;
        $this->date = $date;
        parent::__construct($context, $data);
    }

    protected $_template = 'Makewebbetter_HubIntegration::system/config/authorize.phtml';

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getAjaxSyncUrl()
    {
        return $this->hubspotFetchOauthCode();
    }

    /**
     * @return string
     */
    public function hubspotFetchOauthCode()
    {
        $url = 'https://app.hubspot.com/oauth/authorize';
        $hubSpotUrl = $this->connectionManager->addQueryArguments([
            'client_id' => \Makewebbetter\HubIntegration\Helper\ConnectionManager::DEVELOPER_CLIENT_ID,
            'scope' => 'automation%20oauth%20crm.lists.read%20crm.lists.write%20crm.objects.contacts.read%20crm.objects.contacts.write%20crm.objects.deals.read%20crm.objects.deals.write%20crm.objects.owners.read%20crm.schemas.contacts.read%20crm.schemas.contacts.write%20crm.schemas.deals.read%20crm.schemas.deals.write%20forms%20crm.objects.companies.write%20crm.objects.companies.read%20crm.schemas.companies.write%20crm.schemas.companies.read',
            'optional_scope' => 'files%20e-commerce%20crm.objects.custom.read%20crm.objects.custom.write',
            'redirect_uri' => self::REDIRECT_URI_NEW,
             'state'       => urlencode($this->redirectUrl()),
            'response_type' => 'code'
        ], $url);

        return $hubSpotUrl;
    }

    /**
     * @return string
     */
    public function redirectUrl()
    {
        if ($this->_storeManager->getStore()->isFrontUrlSecure()) {
            $baseUrl = $this->_storeManager->getStore()->getBaseUrl() . self::REDIRECT_URI;
        } else {
            $baseUrl =  $this->_storeManager->getStore()->getBaseUrl() .self::REDIRECT_URI;
            if (strpos($baseUrl, 'https') === false) {
                $baseUrl = str_replace("http", "https", $baseUrl);
            }
        }
        $this->resourceConfig->saveConfig(
            'hub_integration/hubspot_integration/redirect_url',
            $baseUrl
        );
        $admin_redirect_url = $baseUrl;
        $admin_redirect_url = $admin_redirect_url.'?mwb_source=hubspot&mwb_nonce=nonce';

        $this->resourceConfig->saveConfig(
            'hub_integration/hubspot_integration/redirect_url_new',
            $admin_redirect_url
        );
        $this->resourceConfig->saveConfig(
            'hub_integration/hubspot_integration/static_redirect_url_new',
            'yes'
        );
        return $admin_redirect_url;
    }

    /**
     * @return mixed
     */
    public function getConnectionStatus()
    {
        return $this->resourceConfig->getConfigValue('hub_integration/hubspot_integration/connection_established');
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getButtonHtml()
    {
        $path = \Makewebbetter\HubIntegration\Block\Extensions::LICENSE_STATUS_PATH_PREFIX . 'magento2_makewebbetter_hubintegration';
        $licenseStatusDb = $this->hubHelper->getStoreConfig($path);

        $trialVersionStartDate = $this->hubHelper->getStoreConfig(\Makewebbetter\HubIntegration\Block\Extensions::HASH_PATH_EXTENSION_TRIAL);
        $trialVersionEndDate = $this->date->date('Y-m-d');
        $flag = '';
        $datetime1 = date_create($trialVersionStartDate);
        $datetime2 = date_create($trialVersionEndDate);
        $interval = date_diff($datetime1, $datetime2);
        if($interval->days <= 15 && $interval->invert !=1){
            $flag = 'Yes';
        }else{
            $flag = 'No';
        }

        if ($licenseStatusDb === 'ACTIVATED' || $flag == 'Yes') {
            if ($this->getConnectionStatus() == true) {
                $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                    ->setData(
                        [
                            'id' => 'authorize_button',
                            'label' => __('Re-Authenticate'),
                        ]
                    );
            } else {
                $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                    ->setData(
                        [
                            'id' => 'authorize_button',
                            'label' => __('Authenticate'),
                        ]
                    );
            }
        } else {
            $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
                ->setData(
                    [
                        'id' => 'Deactivated',
                        'label' => __('Please Activate The License First!'),
                    ]
                );
        }
        return $button->toHtml();
    }

    public function getHubspotPortalId(){
        return $this->resourceConfig->getConfigValue('hub_integration/hubspot_integration/hub_id');
    }
}
